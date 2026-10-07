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

        You are given the rules the answers had to follow, the task, and the answers.

        Score each answer from 1 (poor) to 10 (excellent):
        - First check every rule. Breaking a rule matters more than tone or style.
        - If an answer breaks any rule, score it 4 or lower.
        - If an answer promises or states something the rules and task do not
          support (an invented offer, date, feature, or process), that counts
          as breaking a rule.
        - Only then compare the rule-following answers on how well they do the task.
        - Judge the content, not how long it is.

        For each answer give a short reason. If a rule was broken, name it.
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
