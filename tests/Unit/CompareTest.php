<?php

use Developia\AgentLoops\Agents\GeneralAssistant;
use Developia\AgentLoops\Comparison\Judge;
use Developia\AgentLoops\Facades\AgentLoops;
use Developia\AgentLoops\Reflection\Reviewer;
use Developia\AgentLoops\Tests\Fixtures\TestAgent;
use Illuminate\Support\Facades\Artisan;

it('runs the task through every loop given', function () {
    TestAgent::fake(['react answer', 'reflect answer']);
    Reviewer::fake([['approved' => true, 'feedback' => 'Good.']]);

    $report = AgentLoops::compare(new TestAgent, 'Write a tweet', ['react', 'reflect-retry']);

    expect(array_keys($report->results))->toBe(['react', 'reflect-retry'])
        ->and($report->results['react']->output)->toBe('react answer')
        ->and($report->results['reflect-retry']->output)->toBe('reflect answer')
        ->and($report->scores)->toBe([]);

    Judge::assertNeverPrompted();
});

it('scores each answer when judging', function () {
    TestAgent::fake(['react answer']);
    Judge::fake([['scores' => [
        ['loop' => 'react', 'score' => 7, 'reason' => 'Fine.'],
        ['loop' => 'made-up-loop', 'score' => 10, 'reason' => 'Ignored.'],
    ]]]);

    $report = AgentLoops::compare(new TestAgent, 'Write a tweet', ['react'], judge: true);

    expect($report->scores)->toBe(['react' => ['score' => 7, 'reason' => 'Fine.']])
        ->and($report->best())->toBe('react');
});

it('prints a comparison table from the command', function () {
    GeneralAssistant::fake(['Launch day!']);

    $this->artisan('agent-loops:compare', ['task' => 'Write a tweet', '--loops' => 'react'])
        ->expectsOutputToContain('Cheapest: react')
        ->expectsOutputToContain('Launch day!')
        ->assertSuccessful();
});

it('rejects an --agent that is not an agent class', function () {
    $this->artisan('agent-loops:compare', ['task' => 'x', '--agent' => 'App\\Nope'])
        ->expectsOutputToContain('is not a Laravel AI agent class')
        ->assertFailed();
});

it('checks every loop name before running any loop', function () {
    TestAgent::fake();

    expect(fn () => AgentLoops::compare(new TestAgent, 'x', ['react', 'reflect-retyr']))
        ->toThrow(InvalidArgumentException::class, 'Loop [reflect-retyr] is not defined');

    // react was listed first, but nothing ran: no money spent on a typo.
    TestAgent::assertNeverPrompted();
});

it('fails nicely for an unknown loop name', function () {
    $this->artisan('agent-loops:compare', ['task' => 'x', '--loops' => 'typo'])
        ->expectsOutputToContain('Loop [typo] is not defined')
        ->assertFailed();
});

it('prints the report as json when --json is given', function () {
    GeneralAssistant::fake(['Launch day!']);

    Artisan::call('agent-loops:compare', ['task' => 'Write a tweet', '--loops' => 'react', '--json' => true]);

    $report = json_decode(Artisan::output(), associative: true);

    expect($report['task'])->toBe('Write a tweet')
        ->and($report['results']['react']['output'])->toBe('Launch day!')
        ->and($report['summary']['cheapest'])->toBe('react')
        ->and($report['summary']['best'])->toBeNull();
});
