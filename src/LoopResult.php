<?php

declare(strict_types=1);

namespace Developia\AgentLoops;

/**
 * Everything a loop run produced.
 *
 * Kept as plain data so two runs can be compared side by side (Stage 4).
 */
final readonly class LoopResult
{
    /**
     * @param  string  $loop  Name of the loop that ran, e.g. "react"
     * @param  string  $output  The final answer
     * @param  array<int, array{type: string, content: string}>  $steps  What happened, in order (plan, attempt, review...)
     * @param  int  $inputTokens  Total tokens sent to the AI
     * @param  int  $outputTokens  Total tokens the AI returned
     * @param  float  $durationMs  How long the run took, in milliseconds
     */
    public function __construct(
        public string $loop,
        public string $output,
        public array $steps,
        public int $inputTokens,
        public int $outputTokens,
        public float $durationMs,
    ) {}

    /**
     * True when the loop could not run its own style and fell back to ReAct.
     */
    public function fellBack(): bool
    {
        foreach ($this->steps as $step) {
            if ($step['type'] === 'fallback') {
                return true;
            }
        }

        return false;
    }

    /**
     * False when a reviewing loop gave up without approving the output.
     * Loops that don't review (react, plan-execute) are always true.
     */
    public function approved(): bool
    {
        foreach ($this->steps as $step) {
            if ($step['type'] === 'not_approved') {
                return false;
            }
        }

        return true;
    }
}
