<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Tests;

use Laravel\Ai\AiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test class.
 *
 * Testbench boots a small Laravel app for every test,
 * so our package can use Laravel features (container, config, laravel/ai).
 */
abstract class TestCase extends Orchestra
{
    /**
     * Service providers to load in the test app.
     * laravel/ai must be registered so agents and fakes work.
     */
    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
        ];
    }
}
