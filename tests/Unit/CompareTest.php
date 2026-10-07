<?php

use Developia\AgentLoops\Agents\GeneralAssistant;
use Developia\AgentLoops\Comparison\Judge;
use Developia\AgentLoops\Facades\AgentLoops;
use Developia\AgentLoops\Reflection\Reviewer;
use Developia\AgentLoops\Tests\Fixtures\TestAgent;
use Illuminate\Support\Facades\Artisan;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\TextResponse;

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

it('prints answers in full with --full', function () {
    // An answer longer than the 100-character preview, with a unique ending
    $long = str_repeat('Store credit is available. ', 6).'THE-END-OF-THE-REPLY';

    GeneralAssistant::fake([$long, $long]);

    // Default: preview only, so the ending is cut off
    $this->artisan('agent-loops:compare', ['task' => 'Help', '--loops' => 'react'])
        ->doesntExpectOutputToContain('THE-END-OF-THE-REPLY')
        ->assertSuccessful();

    // --full: the whole reply is printed
    $this->artisan('agent-loops:compare', ['task' => 'Help', '--loops' => 'react', '--full' => true])
        ->expectsOutputToContain('THE-END-OF-THE-REPLY')
        ->assertSuccessful();
});

it('gives the judge the agent rules so it can check them', function () {
    TestAgent::fake(['react answer']);
    Judge::fake([['scores' => [
        ['loop' => 'react', 'score' => 8, 'reason' => 'Follows the rules.'],
    ]]]);

    AgentLoops::compare(new TestAgent, 'Write a tweet', ['react'], judge: true);

    // The judge's prompt must contain the agent's own instructions
    Judge::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('You are a helpful test agent.')
        && $prompt->contains('Write a tweet')
        && $prompt->contains('react answer'));
});

it('shows the estimated cost of each loop in the table and json', function () {
    config()->set('agent-loops.pricing', ['test-model' => ['input' => 0.25, 'output' => 1.50]]);

    // 1,000 in × $0.25 + 2,000 out × $1.50 (per 1M) = $0.00325
    $reply = fn () => new TextResponse('Launch day!', new TextUsage(inputTokens: 1000, outputTokens: 2000), new Meta(model: 'test-model'));

    GeneralAssistant::fake([$reply()]);
    Artisan::call('agent-loops:compare', ['task' => 'Write a tweet', '--loops' => 'react']);
    $table = Artisan::output();

    expect($table)->toContain('Cost (est.)')
        ->and($table)->toContain('$0.003250');

    GeneralAssistant::fake([$reply()]);
    Artisan::call('agent-loops:compare', ['task' => 'Write a tweet', '--loops' => 'react', '--json' => true]);
    $report = json_decode(Artisan::output(), associative: true);

    expect($report['results']['react']['cost'])->toBe(0.00325);
});
