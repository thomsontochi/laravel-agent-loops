<?php

use Developia\AgentLoops\Agents\GeneralAssistant;
use Developia\AgentLoops\Comparison\ComparisonReport;
use Developia\AgentLoops\Facades\AgentLoops;
use Developia\AgentLoops\LoopResult;
use Developia\AgentLoops\Planning\Planner;
use Developia\AgentLoops\Reflection\Reviewer;
use Developia\AgentLoops\Tests\Fixtures\TestAgent;
use Illuminate\Support\Facades\Artisan;
use Laravel\Ai\Exceptions\ProviderOverloadedException;

it('records a failed loop and keeps running the others', function () {
    TestAgent::fake(['react answer', 'reflect answer']);
    Planner::fake(fn () => throw ProviderOverloadedException::forProvider('gemini'));
    Reviewer::fake([['approved' => true, 'feedback' => 'Good.']]);

    $report = AgentLoops::compare(new TestAgent, 'Write a tweet', ['react', 'plan-execute', 'reflect-retry']);

    // The loops before AND after the failure both kept their results.
    expect(array_keys($report->results))->toBe(['react', 'reflect-retry'])
        ->and($report->failures)->toBe(['plan-execute' => 'AI provider [gemini] is overloaded.'])
        ->and($report->hasFailures())->toBeTrue();
});

it('ignores failed loops when picking cheapest and fastest', function () {
    $report = new ComparisonReport(
        'task',
        ['react' => new LoopResult('react', 'ok', [], 300, 0, 900.0)],
        failures: ['plan-execute' => 'overloaded'],
    );

    expect($report->cheapest())->toBe('react')
        ->and($report->fastest())->toBe('react');
});

it('lets real code bugs crash instead of recording them', function () {
    TestAgent::fake(fn () => throw new TypeError('a real bug'));

    AgentLoops::compare(new TestAgent, 'x', ['react']);
})->throws(TypeError::class, 'a real bug');

it('shows a failed row, a warning and exits 1 from the command', function () {
    GeneralAssistant::fake(['react answer']);
    Planner::fake(fn () => throw ProviderOverloadedException::forProvider('gemini'));

    $this->artisan('agent-loops:compare', ['task' => 'x', '--loops' => 'react,plan-execute'])
        ->expectsOutputToContain('failed')
        ->expectsOutputToContain('plan-execute: AI provider [gemini] is overloaded.')
        ->expectsOutputToContain('Cheapest: react')
        ->assertFailed();
});

it('includes failures in the json output', function () {
    GeneralAssistant::fake(['react answer']);
    Planner::fake(fn () => throw ProviderOverloadedException::forProvider('gemini'));

    $exitCode = Artisan::call('agent-loops:compare', ['task' => 'x', '--loops' => 'react,plan-execute', '--json' => true]);

    $report = json_decode(Artisan::output(), associative: true);

    expect($exitCode)->toBe(1)
        ->and(array_keys($report['results']))->toBe(['react'])
        ->and($report['failures'])->toBe(['plan-execute' => 'AI provider [gemini] is overloaded.']);
});
