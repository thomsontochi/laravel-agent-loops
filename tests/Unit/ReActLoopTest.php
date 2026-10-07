<?php

use Developia\AgentLoops\Grounding;
use Developia\AgentLoops\Loops\ReActLoop;
use Developia\AgentLoops\Tests\Fixtures\TestAgent;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\TextResponse;

it('has the name react', function () {
    expect((new ReActLoop)->name())->toBe('react');
});

it('returns the agent answer as the output', function () {
    TestAgent::fake(['Paris']);

    $result = (new ReActLoop)->run(new TestAgent, 'What is the capital of France?');

    expect($result->loop)->toBe('react')
        ->and($result->output)->toBe('Paris')
        ->and($result->steps)->toBe([['type' => 'answer', 'content' => 'Paris']]);

    TestAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'What is the capital of France?'));
});

it('tells the agent to answer from facts or say it does not know', function () {
    TestAgent::fake(['ok']);

    (new ReActLoop)->run(new TestAgent, 'Some task');

    TestAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, Grounding::TEXT));
});

it('records token usage from the agent', function () {
    TestAgent::fake([
        new TextResponse('Paris', new TextUsage(inputTokens: 120, outputTokens: 8), new Meta),
    ]);

    $result = (new ReActLoop)->run(new TestAgent, 'Capital of France?');

    expect($result->inputTokens)->toBe(120)
        ->and($result->outputTokens)->toBe(8)
        ->and($result->durationMs)->toBeGreaterThanOrEqual(0.0);
});
