<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Tests\Fixtures;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * A tiny agent used only in tests. Its replies are faked.
 */
final class TestAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a helpful test agent.';
    }
}
