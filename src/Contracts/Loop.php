<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Contracts;

use Developia\AgentLoops\LoopResult;
use Laravel\Ai\Contracts\Agent;

/**
 * A Loop decides HOW an agent works through a task.
 *
 * The agent (from laravel/ai) knows WHAT it can do: its instructions and tools.
 * The loop decides the thinking style: act step by step, plan first,
 * or review its own work and retry.
 */
interface Loop
{
    /**
     * Short unique name, used in config and comparison reports.
     * Example: "react", "plan-execute", "reflect-retry".
     */
    public function name(): string;

    /**
     * Run the task using the given agent and return the final result.
     */
    public function run(Agent $agent, string $task): LoopResult;
}
