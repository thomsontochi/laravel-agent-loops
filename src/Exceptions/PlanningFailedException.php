<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when planning fails and on_planning_failure is set to "throw".
 */
final class PlanningFailedException extends RuntimeException
{
    public static function for(string $task, string $reason, ?Throwable $previous = null): self
    {
        return new self("Planning failed for task [{$task}]: {$reason}", previous: $previous);
    }
}
