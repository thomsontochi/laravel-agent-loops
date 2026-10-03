<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Attributes;

use Attribute;

/**
 * Choose which loop an agent runs with by default.
 *
 * #[UseLoop('reflect-retry')]
 * class CopywriterAgent implements Agent { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UseLoop
{
    public function __construct(
        public string $name,
    ) {}
}
