<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Comparison;

use Developia\AgentLoops\LoopResult;

/**
 * The results of running one task through several loops.
 *
 * Plain data plus a few easy questions: which loop was cheapest,
 * fastest, and (when judged) best.
 */
final readonly class ComparisonReport
{
    /**
     * @param  string  $task  The task every loop ran
     * @param  array<string, LoopResult>  $results  Loop name => its result
     * @param  array<string, array{score: int, reason: string}>  $scores  Loop name => judge score (empty if not judged)
     */
    public function __construct(
        public string $task,
        public array $results,
        public array $scores = [],
    ) {}

    /**
     * The loop that used the fewest tokens in total.
     */
    public function cheapest(): ?string
    {
        return $this->lowest(fn (LoopResult $r): float => $r->inputTokens + $r->outputTokens);
    }

    /**
     * The loop that finished first.
     */
    public function fastest(): ?string
    {
        return $this->lowest(fn (LoopResult $r): float => $r->durationMs);
    }

    /**
     * The loop the judge scored highest, or null when not judged.
     */
    public function best(): ?string
    {
        if ($this->scores === []) {
            return null;
        }

        $scores = array_map(fn (array $s): int => $s['score'], $this->scores);

        return array_search(max($scores), $scores, true) ?: null;
    }

    /**
     * @param  callable(LoopResult): float  $measure
     */
    private function lowest(callable $measure): ?string
    {
        if ($this->results === []) {
            return null;
        }

        $values = array_map($measure, $this->results);

        return array_search(min($values), $values, true) ?: null;
    }
}
