<?php

use Developia\AgentLoops\Loops\ReActLoop;
use Illuminate\Support\Facades\Artisan;

it('lists every loop with what it does and marks the default', function () {
    $exitCode = Artisan::call('agent-loops:list');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('react')
        ->and($output)->toContain('plan-execute')
        ->and($output)->toContain('reflect-retry')
        ->and($output)->toContain('Answers in one go')
        ->and($output)->toContain('default');
});

it('shows a custom loop from the config too', function () {
    config(['agent-loops.loops.twice' => ReActLoop::class]);

    $this->artisan('agent-loops:list')
        ->expectsOutputToContain('twice')
        ->assertSuccessful();
});
