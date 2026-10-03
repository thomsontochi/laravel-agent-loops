<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Comparison;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * An optional agent that scores each loop's answer from 1 to 10.
 *
 * Only used with --judge. AI judges can be biased, so treat the
 * score as a second opinion, not the truth.
 */
final class Judge implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You compare several answers to the same task.
        Score each answer from 1 (poor) to 10 (excellent) on how well it
        completes the task. Judge the answer only, not how long it is.
        Give a short reason for each score.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scores' => $schema->array()->items($schema->object(fn (JsonSchema $schema) => [
                'loop' => $schema->string()->required(),
                'score' => $schema->integer()->min(1)->max(10)->required(),
                'reason' => $schema->string()->required(),
            ]))->required(),
        ];
    }
}
