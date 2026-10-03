<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Reflection;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * A small agent whose only job is to judge an answer against its task.
 *
 * Structured output means the verdict is always
 * {"approved": true|false, "feedback": "..."}: a clear yes or no,
 * plus feedback the agent can use to fix its next attempt.
 */
final class Reviewer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly Stringable|string $agentInstructions = '',
    ) {}

    public function instructions(): string
    {
        return trim(<<<TEXT
        The answer you review was written by an agent with these instructions:
        {$this->agentInstructions}

        You are a strict reviewer. Check whether the answer fully and correctly
        completes the task. Approve only if it does. If not, give short,
        specific feedback the agent can act on.
        TEXT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'approved' => $schema->boolean()->required(),
            'feedback' => $schema->string()->required(),
        ];
    }
}
