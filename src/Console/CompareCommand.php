<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Console;

use Developia\AgentLoops\Agents\GeneralAssistant;
use Developia\AgentLoops\LoopManager;
use Developia\AgentLoops\LoopResult;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Agent;

/**
 * php artisan agent-loops:compare "Write a launch tweet" --loops=react,reflect-retry
 */
final class CompareCommand extends Command
{
    protected $signature = 'agent-loops:compare
        {task : The task every loop will run}
        {--loops=react,plan-execute,reflect-retry : Comma-separated loop names}
        {--agent= : Agent class to use (default: a built-in assistant)}
        {--judge : Also score each answer 1-10 with a Judge agent}';

    protected $description = 'Run one task through several loops and compare cost, speed and output';

    public function handle(LoopManager $loops): int
    {
        $agent = $this->resolveAgent();

        if ($agent === null) {
            return self::FAILURE;
        }

        $names = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('loops')))));

        $this->components->info('Running '.count($names).' loops with '.class_basename($agent).'. This makes real AI calls.');

        $report = $loops->compare($agent, (string) $this->argument('task'), $names, (bool) $this->option('judge'));

        // 1. Cost and speed
        $this->table(
            ['Loop', 'Steps', 'Tokens in', 'Tokens out', 'Time', 'Status'],
            collect($report->results)->map(fn (LoopResult $r, string $name): array => [
                $name,
                count($r->steps),
                number_format($r->inputTokens),
                number_format($r->outputTokens),
                number_format($r->durationMs / 1000, 1).'s',
                $this->status($r),
            ])->values()->all(),
        );

        $summary = "Cheapest: {$report->cheapest()}  ·  Fastest: {$report->fastest()}";

        if ($report->best() !== null) {
            $summary .= "  ·  Best (judge): {$report->best()}";
        }

        $this->line($summary);
        $this->newLine();

        // 2. Judge scores, only when asked for
        if ($report->scores !== []) {
            $this->table(
                ['Loop', 'Score', 'Reason'],
                collect($report->scores)->map(fn (array $s, string $name): array => [$name, $s['score'], $s['reason']])->values()->all(),
            );
        }

        // 3. The answers themselves, so a human can judge
        foreach ($report->results as $name => $result) {
            $this->line("<options=bold>{$name}</>  ".Str::limit(Str::squish($result->output), 100));
        }

        return self::SUCCESS;
    }

    /**
     * The --agent class, or the built-in assistant when none is given.
     */
    private function resolveAgent(): ?Agent
    {
        $class = $this->option('agent');

        if ($class === null || $class === '') {
            return $this->laravel->make(GeneralAssistant::class);
        }

        if (! class_exists($class) || ! is_a($class, Agent::class, true)) {
            $this->components->error("[{$class}] is not a Laravel AI agent class.");

            return null;
        }

        return $this->laravel->make($class);
    }

    private function status(LoopResult $result): string
    {
        return match (true) {
            $result->fellBack() => 'fell back',
            ! $result->approved() => 'not approved',
            default => 'ok',
        };
    }
}
