<?php

use Developia\AgentLoops\Comparison\ComparisonReport;
use Developia\AgentLoops\LoopResult;

function fakeResult(string $loop, int $tokens, float $ms): LoopResult
{
    return new LoopResult($loop, 'answer', [], $tokens, 0, $ms);
}

it('finds the cheapest and fastest loop', function () {
    $report = new ComparisonReport('task', [
        'react' => fakeResult('react', 200, 900.0),
        'plan-execute' => fakeResult('plan-execute', 1800, 600.0),
    ]);

    expect($report->cheapest())->toBe('react')
        ->and($report->fastest())->toBe('plan-execute');
});

it('finds the best loop only when judged', function () {
    $results = ['react' => fakeResult('react', 1, 1.0), 'reflect-retry' => fakeResult('reflect-retry', 1, 1.0)];

    $unjudged = new ComparisonReport('task', $results);
    $judged = new ComparisonReport('task', $results, [
        'react' => ['score' => 6, 'reason' => 'Generic.'],
        'reflect-retry' => ['score' => 9, 'reason' => 'Clear hook.'],
    ]);

    expect($unjudged->best())->toBeNull()
        ->and($judged->best())->toBe('reflect-retry');
});
