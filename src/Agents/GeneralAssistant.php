<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * A plain assistant used by agent-loops:compare when no --agent is given,
 * so the command works right after install.
 */
final class GeneralAssistant implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a helpful, concise assistant.';
    }
}
