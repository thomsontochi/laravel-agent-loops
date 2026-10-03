<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Tests\Fixtures;

use Developia\AgentLoops\Attributes\UseLoop;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * A test agent that asks for reflect-retry via the #[UseLoop] attribute.
 */
#[UseLoop('reflect-retry')]
final class CopywriterAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You write short marketing copy.';
    }
}
