<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Planning;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * A small agent whose only job is to turn a task into a list of steps.
 *
 * It borrows the user's agent instructions, so the plan fits that agent's role,
 * and uses structured output, so the reply is always {"steps": ["...", "..."]}.
 */
final class Planner implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly Stringable|string $agentInstructions = '',
        private readonly int $maxSteps = 5,
    ) {}

    public function instructions(): string
    {
        return trim(<<<TEXT
        {$this->agentInstructions}

        You are planning, not answering. Break the task into clear, ordered steps.
        Use at most {$this->maxSteps} steps. Each step is one short instruction.
        TEXT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'steps' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
