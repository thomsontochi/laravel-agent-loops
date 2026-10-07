<?php

use Developia\AgentLoops\Grounding;
use Developia\AgentLoops\Loops\ReflectRetryLoop;
use Developia\AgentLoops\Reflection\Reviewer;
use Developia\AgentLoops\Tests\Fixtures\TestAgent;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Laravel\Ai\Responses\TextResponse;

it('has the name reflect-retry', function () {
    expect((new ReflectRetryLoop)->name())->toBe('reflect-retry');
});

it('returns the first attempt when the review approves it', function () {
    TestAgent::fake(['Launch day! Try our app']);
    Reviewer::fake([['approved' => true, 'feedback' => 'Clear and under the limit.']]);

    $result = (new ReflectRetryLoop)->run(new TestAgent, 'Write a launch tweet');

    expect($result->output)->toBe('Launch day! Try our app')
        ->and($result->approved())->toBeTrue()
        ->and(array_column($result->steps, 'type'))->toBe(['attempt', 'review_approved']);

    TestAgent::assertPromptedTimes(1);
});

it('retries with the feedback until approved', function () {
    TestAgent::fake(['A very long first draft', 'Short second draft']);
    Reviewer::fake([
        ['approved' => false, 'feedback' => 'Too long, cut it down.'],
        ['approved' => true, 'feedback' => 'Good.'],
    ]);

    $result = (new ReflectRetryLoop)->run(new TestAgent, 'Write a launch tweet');

    expect($result->output)->toBe('Short second draft')
        ->and($result->approved())->toBeTrue()
        ->and(array_column($result->steps, 'type'))
        ->toBe(['attempt', 'review_rejected', 'attempt', 'review_approved']);

    // The retry prompt must carry the reviewer's feedback
    TestAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'Too long, cut it down.'));
});

it('returns the last attempt flagged when retries run out', function () {
    config(['agent-loops.reflect_retry.max_retries' => 1]);
    TestAgent::fake(['Draft 1', 'Draft 2']);
    Reviewer::fake([
        ['approved' => false, 'feedback' => 'Still wrong.'],
        ['approved' => false, 'feedback' => 'Still wrong.'],
    ]);

    $result = (new ReflectRetryLoop)->run(new TestAgent, 'Write a launch tweet');

    expect($result->output)->toBe('Draft 2')
        ->and($result->approved())->toBeFalse()
        ->and($result->steps[array_key_last($result->steps)]['type'])->toBe('not_approved');

    // 1 retry = 2 attempts in total
    TestAgent::assertPromptedTimes(2);
});

it('grounds the first attempt and every retry in the given facts', function () {
    TestAgent::fake(['Draft with an invented portal', 'Fixed draft']);
    Reviewer::fake([
        ['approved' => false, 'feedback' => 'There is no portal.'],
        ['approved' => true, 'feedback' => 'Good.'],
    ]);

    (new ReflectRetryLoop)->run(new TestAgent, 'Help the customer');

    // first attempt + 1 retry, and both carry the grounding rule
    TestAgent::assertPromptedTimes(2);
    TestAgent::assertNotPrompted(fn ($prompt): bool => ! str_contains($prompt->prompt, Grounding::TEXT));
});

it('tells the reviewer to reject invented facts', function () {
    expect((new Reviewer('Be helpful.'))->instructions())
        ->toContain('Reject if the answer breaks any of these instructions');
});

it('adds up the cost of every attempt and review, each at its own model price', function () {
    config()->set('agent-loops.pricing', [
        'reviewer-model' => ['input' => 1.00, 'output' => 2.00],
        'test-model' => ['input' => 0.25, 'output' => 1.50],
    ]);

    // 2 attempts, each 1,000 in × $0.25 + 2,000 out × $1.50 (per 1M) = $0.00325 → $0.0065
    $attempt = fn (string $text) => new TextResponse($text, new TextUsage(inputTokens: 1000, outputTokens: 2000), new Meta(model: 'test-model'));
    TestAgent::fake([$attempt('Draft 1'), $attempt('Draft 2')]);

    // 2 reviews, each 1,000 in × $1 + 500 out × $2 = $0.002 → $0.004
    $review = fn (bool $approved) => new StructuredTextResponse(
        ['approved' => $approved, 'feedback' => 'ok'], '', new TextUsage(inputTokens: 1000, outputTokens: 500), new Meta(model: 'reviewer-model'),
    );
    Reviewer::fake([$review(false), $review(true)]);

    $result = (new ReflectRetryLoop)->run(new TestAgent, 'Write a launch tweet');

    // $0.0065 + $0.004
    expect($result->cost)->toBe(0.0105);
});

it('has no cost when the reviewer model has no price', function () {
    config()->set('agent-loops.pricing', ['test-model' => ['input' => 0.25, 'output' => 1.50]]);

    TestAgent::fake([new TextResponse('Draft 1', new TextUsage(inputTokens: 1000, outputTokens: 2000), new Meta(model: 'test-model'))]);
    Reviewer::fake([new StructuredTextResponse(
        ['approved' => true, 'feedback' => 'ok'], '', new TextUsage(inputTokens: 1000, outputTokens: 500), new Meta(model: 'unknown-model'),
    )]);

    $result = (new ReflectRetryLoop)->run(new TestAgent, 'Write a launch tweet');

    expect($result->cost)->toBeNull();
});
