<?php

use Developia\AgentLoops\Events\PlanningFailed;
use Developia\AgentLoops\Exceptions\PlanningFailedException;
use Developia\AgentLoops\Loops\PlanExecuteLoop;
use Developia\AgentLoops\Planning\Planner;
use Developia\AgentLoops\Tests\Fixtures\TestAgent;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Exceptions\ProviderOverloadedException;

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
