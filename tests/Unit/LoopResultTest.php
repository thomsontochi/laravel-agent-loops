<?php

use Developia\AgentLoops\LoopResult;

it('keeps everything a loop run produced', function () {
    $result = new LoopResult(
        loop: 'react',
        output: 'Paris',
        steps: [['type' => 'answer', 'content' => 'Paris']],
        inputTokens: 120,
        outputTokens: 8,
        durationMs: 350.5,
    );

    expect($result->loop)->toBe('react')
        ->and($result->output)->toBe('Paris')
        ->and($result->steps)->toHaveCount(1)
        ->and($result->inputTokens)->toBe(120)
        ->and($result->outputTokens)->toBe(8)
        ->and($result->durationMs)->toBe(350.5);
});

it('cannot be changed after it is created', function () {
    $result = new LoopResult('react', 'Paris', [], 0, 0, 0.0);

    // readonly: changing a value must throw an Error
    $result->output = 'London';
})->throws(Error::class);

it('knows when a run was not approved', function () {
    $approved = new LoopResult('reflect-retry', 'ok', [['type' => 'review_approved', 'content' => 'Good.']], 0, 0, 0.0);
    $rejected = new LoopResult('reflect-retry', 'ok', [['type' => 'not_approved', 'content' => 'Not approved']], 0, 0, 0.0);

    expect($approved->approved())->toBeTrue()
        ->and($rejected->approved())->toBeFalse();
});
