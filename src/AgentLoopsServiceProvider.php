<?php

declare(strict_types=1);

namespace Developia\AgentLoops;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the package with Laravel.
 *
 * Loads our default config, and lets apps publish it so they can change it.
 */
final class AgentLoopsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Our defaults, which an app's own config/agent-loops.php overrides.
        $this->mergeConfigFrom(__DIR__.'/../config/agent-loops.php', 'agent-loops');
        // One shared LoopManager per app, used by the AgentLoops facade.
        $this->app->singleton(LoopManager::class);
    }

    public function boot(): void
    {
        // php artisan vendor:publish --tag=agent-loops-config
        $this->publishes([
            __DIR__.'/../config/agent-loops.php' => config_path('agent-loops.php'),
        ], 'agent-loops-config');
    }
}
