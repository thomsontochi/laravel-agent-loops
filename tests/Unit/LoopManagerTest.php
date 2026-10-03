<?php

use Developia\AgentLoops\Facades\AgentLoops;
use Developia\AgentLoops\Loops\PlanExecuteLoop;
use Developia\AgentLoops\Loops\ReActLoop;
use Developia\AgentLoops\Reflection\Reviewer;
use Developia\AgentLoops\Tests\Fixtures\CopywriterAgent;
use Developia\AgentLoops\Tests\Fixtures\TestAgent;

it('picks a loop by name', function () {
    expect(AgentLoops::using('react'))->toBeInstanceOf(ReActLoop::class)
        ->and(AgentLoops::using('plan-execute'))->toBeInstanceOf(PlanExecuteLoop::class);
});

it('throws a helpful error for an unknown loop name', function () {
    AgentLoops::using('telepathy');
})->throws(InvalidArgumentException::class, 'Loop [telepathy] is not defined. Available loops: react, plan-execute, reflect-retry.');

it('uses the config default when nothing else is chosen', function () {
    TestAgent::fake(['Paris']);

    $result = AgentLoops::run(new TestAgent, 'Capital of France?');

    expect($result->loop)->toBe('react');
});

it('lets the config default be changed', function () {
    config(['agent-loops.default' => 'plan-execute']);

    expect(AgentLoops::loopNameFor(new TestAgent))->toBe('plan-execute');
});

it('lets the UseLoop attribute beat the config default', function () {
    config(['agent-loops.default' => 'plan-execute']);
    CopywriterAgent::fake(['Launch day!']);
    Reviewer::fake([['approved' => true, 'feedback' => 'Good.']]);

    $result = AgentLoops::run(new CopywriterAgent, 'Write a launch tweet');

    expect($result->loop)->toBe('reflect-retry');
});

it('lets using() beat the UseLoop attribute', function () {
    CopywriterAgent::fake(['Launch day!']);

    $result = AgentLoops::using('react')->run(new CopywriterAgent, 'Write a launch tweet');

    expect($result->loop)->toBe('react');
});

it('ships every setting in the config file', function () {
    // Read the file itself, not config(), so code fallbacks can't hide a missing section.
    $config = require __DIR__.'/../../config/agent-loops.php';

    expect($config)->toHaveKeys([
        'default',
        'loops',
        'plan_execute.max_steps',
        'plan_execute.on_planning_failure',
        'reflect_retry.max_retries',
    ]);
});
