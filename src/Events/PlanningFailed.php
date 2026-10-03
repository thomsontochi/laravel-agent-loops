<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when plan-then-execute could not get a plan from the agent.
 *
 * Listen for it to alert your team (Slack, Sentry, email).
 */
final class PlanningFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $agent,
        public readonly string $task,
        public readonly string $reason,
    ) {}
}
