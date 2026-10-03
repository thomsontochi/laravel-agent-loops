<?php

declare(strict_types=1);

namespace Developia\AgentLoops;

use Developia\AgentLoops\Attributes\UseLoop;
use Developia\AgentLoops\Contracts\Loop;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Agent;
use ReflectionClass;

/**
 * The front door: picks a loop by name and runs it.
 *
 * Which loop runs, most specific first:
 *   1. AgentLoops::using('name') at the call site
 *   2. #[UseLoop('name')] on the agent class
 *   3. the 'default' in config/agent-loops.php
 */
final class LoopManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Get a loop by its name, e.g. "plan-execute".
     */
    public function using(string $name): Loop
    {
        $class = config("agent-loops.loops.{$name}");

        if (! is_string($class) || ! is_a($class, Loop::class, true)) {
            $available = implode(', ', array_keys((array) config('agent-loops.loops', [])));

            throw new InvalidArgumentException("Loop [{$name}] is not defined. Available loops: {$available}.");
        }

        // Resolved through the container, so loops can use dependency injection.
        return $this->container->make($class);
    }

    /**
     * Run the task with the agent's loop: its #[UseLoop] attribute, or the config default.
     */
    public function run(Agent $agent, string $task): LoopResult
    {
        return $this->using($this->loopNameFor($agent))->run($agent, $task);
    }

    /**
     * The loop name for this agent: its attribute if it has one, else the default.
     */
    public function loopNameFor(Agent $agent): string
    {
        $attributes = (new ReflectionClass($agent))->getAttributes(UseLoop::class);

        if ($attributes !== []) {
            return $attributes[0]->newInstance()->name;
        }

        return (string) config('agent-loops.default', 'react');
    }
}
