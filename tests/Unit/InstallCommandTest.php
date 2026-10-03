<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    Process::fake();
    File::delete(config_path('agent-loops.php'));
});

afterEach(function () {
    File::delete(config_path('agent-loops.php'));
});

it('publishes the config and shows the welcome', function () {
    $this->artisan('agent-loops:install')
        ->expectsOutputToContain('Config published')
        ->expectsOutputToContain('Thanks for installing Laravel Agent Loops.')
        ->expectsConfirmation('Would you like to star the repo on GitHub?', 'no')
        ->assertSuccessful();

    expect(File::exists(config_path('agent-loops.php')))->toBeTrue();
});

it('never overwrites an existing config without --force', function () {
    File::put(config_path('agent-loops.php'), '<?php return ["mine" => true];');

    $this->artisan('agent-loops:install')
        ->expectsOutputToContain('kept as is')
        ->expectsConfirmation('Would you like to star the repo on GitHub?', 'no')
        ->assertSuccessful();

    expect(File::get(config_path('agent-loops.php')))->toContain('"mine" => true');
});

it('overwrites the config with --force', function () {
    File::put(config_path('agent-loops.php'), '<?php return ["mine" => true];');

    $this->artisan('agent-loops:install', ['--force' => true])
        ->expectsConfirmation('Would you like to star the repo on GitHub?', 'no')
        ->assertSuccessful();

    expect(File::get(config_path('agent-loops.php')))->toContain("'loops' =>");
});

it('opens the repo when the user agrees to star it', function () {
    $this->artisan('agent-loops:install')
        ->expectsConfirmation('Would you like to star the repo on GitHub?', 'yes')
        ->expectsOutputToContain('thank you!')
        ->assertSuccessful();

    Process::assertRan(fn ($process) => in_array('https://github.com/thomsontochi/laravel-agent-loops', (array) $process->command, true));
});

it('does not open anything when the user says no', function () {
    $this->artisan('agent-loops:install')
        ->expectsConfirmation('Would you like to star the repo on GitHub?', 'no')
        ->assertSuccessful();

    Process::assertNothingRan();
});

it('skips the star prompt when nobody is there to answer', function () {
    $this->artisan('agent-loops:install', ['--no-interaction' => true])
        ->assertSuccessful();

    Process::assertNothingRan();
});
