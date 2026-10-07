<?php

use Developia\AgentLoops\Events\PlanningFailed;
use Developia\AgentLoops\Exceptions\PlanningFailedException;
use Developia\AgentLoops\Grounding;
use Developia\AgentLoops\Loops\PlanExecuteLoop;
use Developia\AgentLoops\Planning\Planner;
use Developia\AgentLoops\Tests\Fixtures\TestAgent;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Laravel\Ai\Responses\TextResponse;

it('has the name plan-execute', function () {
    expect((new PlanExecuteLoop)->name())->toBe('plan-execute');
});

it('plans, runs each step, then answers', function () {
    Planner::fake([['steps' => ['List the features', 'Draft the tweet']]]);
    TestAgent::fake(['Fast, free, offline', 'Draft: try our app', 'Launch day! Try our app']);

    $result = (new PlanExecuteLoop)->run(new TestAgent, 'Write a launch tweet');

    expect($result->output)->toBe('Launch day! Try our app')
        ->and($result->fellBack())->toBeFalse()
        ->and(array_column($result->steps, 'type'))->toBe(['plan', 'step', 'step', 'answer']);

    // 2 steps + 1 final answer = 3 calls to the user's agent
    TestAgent::assertPromptedTimes(3);
});

it('passes earlier step results into later steps', function () {
    Planner::fake([['steps' => ['Step A', 'Step B']]]);
    TestAgent::fake(['result of A', 'result of B', 'final']);

    (new PlanExecuteLoop)->run(new TestAgent, 'Do the thing');

    TestAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'Now do step 2 only: Step B')
        && str_contains($prompt->prompt, 'result of A'));
});

it('trims a plan longer than max_steps', function () {
    config(['agent-loops.plan_execute.max_steps' => 2]);
    Planner::fake([['steps' => ['one', 'two', 'three', 'four']]]);
    TestAgent::fake(['1', '2', 'final']);

    $result = (new PlanExecuteLoop)->run(new TestAgent, 'Big task');

    expect(array_column($result->steps, 'type'))->toBe(['plan', 'step', 'step', 'answer']);
    TestAgent::assertPromptedTimes(3);
});

it('falls back to react and reports loudly when the plan is empty', function () {
    Event::fake([PlanningFailed::class]);
    Planner::fake([['steps' => []]]);
    TestAgent::fake(['Answer via react']);

    $result = (new PlanExecuteLoop)->run(new TestAgent, 'Write a launch tweet');

    expect($result->output)->toBe('Answer via react')
        ->and($result->fellBack())->toBeTrue()
        ->and($result->steps[0])->toBe(['type' => 'fallback', 'content' => 'empty plan']);

    Event::assertDispatched(PlanningFailed::class, fn ($e) => $e->reason === 'empty plan');
});

it('throws instead when on_planning_failure is throw', function () {
    config(['agent-loops.plan_execute.on_planning_failure' => 'throw']);
    Planner::fake([['steps' => []]]);
    TestAgent::fake();

    (new PlanExecuteLoop)->run(new TestAgent, 'Write a launch tweet');
})->throws(PlanningFailedException::class, 'empty plan');

it('does not treat a provider outage as a planning failure', function () {
    Event::fake([PlanningFailed::class]);
    Planner::fake(fn () => throw ProviderOverloadedException::forProvider('gemini'));
    TestAgent::fake();

    expect(fn () => (new PlanExecuteLoop)->run(new TestAgent, 'Write a launch tweet'))
        ->toThrow(ProviderOverloadedException::class);

    // No fallback to react, and no misleading "planning failed" alert.
    TestAgent::assertNeverPrompted();
    Event::assertNotDispatched(PlanningFailed::class);
});

it('grounds every step and the final answer in the given facts', function () {
    Planner::fake([['steps' => ['Step A', 'Step B']]]);
    TestAgent::fake(['result of A', 'result of B', 'final']);

    (new PlanExecuteLoop)->run(new TestAgent, 'Do the thing');

    // 2 steps + 1 final answer, and not one of them is missing the grounding rule
    TestAgent::assertPromptedTimes(3);
    TestAgent::assertNotPrompted(fn ($prompt): bool => ! str_contains($prompt->prompt, Grounding::TEXT));
});

it('tells the planner to plan only from the information given', function () {
    expect((new Planner('Be helpful.'))->instructions())
        ->toContain('Plan only from the information given');
});

it('adds up the cost of every call, each at its own model price', function () {
    config()->set('agent-loops.pricing', [
        'planner-model' => ['input' => 1.00, 'output' => 2.00],
        'test-model' => ['input' => 0.25, 'output' => 1.50],
    ]);

    // Planner: 1,000 in × $1 + 500 out × $2 (per 1M) = $0.002
    Planner::fake([new StructuredTextResponse(
        ['steps' => ['Step A', 'Step B']], '', new TextUsage(inputTokens: 1000, outputTokens: 500), new Meta(model: 'planner-model'),
    )]);

    // 2 steps + 1 answer, each 1,000 in × $0.25 + 2,000 out × $1.50 = $0.00325 → $0.00975
    $answer = fn (string $text) => new TextResponse($text, new TextUsage(inputTokens: 1000, outputTokens: 2000), new Meta(model: 'test-model'));
    TestAgent::fake([$answer('A done'), $answer('B done'), $answer('final')]);

    $result = (new PlanExecuteLoop)->run(new TestAgent, 'Do the thing');

    expect($result->cost)->toBe(0.01175);
});

it('has no cost when any call used a model with no price', function () {
    config()->set('agent-loops.pricing', ['test-model' => ['input' => 0.25, 'output' => 1.50]]);

    // The planner's model has no price, so the total can't be trusted.
    Planner::fake([new StructuredTextResponse(
        ['steps' => ['Step A']], '', new TextUsage(inputTokens: 1000, outputTokens: 500), new Meta(model: 'unknown-model'),
    )]);

    $answer = fn (string $text) => new TextResponse($text, new TextUsage(inputTokens: 1000, outputTokens: 2000), new Meta(model: 'test-model'));
    TestAgent::fake([$answer('A done'), $answer('final')]);

    $result = (new PlanExecuteLoop)->run(new TestAgent, 'Do the thing');

    expect($result->cost)->toBeNull();
});
