<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Console;

use Developia\AgentLoops\Agents\GeneralAssistant;
use Developia\AgentLoops\Comparison\ComparisonReport;
use Developia\AgentLoops\LoopManager;
use Developia\AgentLoops\LoopResult;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
        {--judge : Also score each answer 1-10 with a Judge agent}
        {--json : Output the report as JSON instead of tables}';

    protected $description = 'Run one task through several loops and compare cost, speed and output';

    public function handle(LoopManager $loops): int
    {
        $agent = $this->resolveAgent();

        if ($agent === null) {
            return self::FAILURE;
        }

        $names = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $this->option('loops'))))));

        $json = (bool) $this->option('json');

        if (! $json) {
            $count = count($names);
            $this->components->info("Running {$count} ".Str::plural('loop', $count).' with '.class_basename($agent).'. This makes real AI calls.');
        }

        try {
            $report = $loops->compare($agent, (string) $this->argument('task'), $names, (bool) $this->option('judge'));
        } catch (InvalidArgumentException $e) {
            // Unknown loop name etc.: report it like the --agent error, not a stack trace.
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        // Exit code 1 if any loop failed, so scripts and CI notice.
        $exitCode = $report->hasFailures() ? self::FAILURE : self::SUCCESS;

        // Machine-readable output: JSON on stdout, nothing else.
        if ($json) {
            $this->line(json_encode([
                'task' => $report->task,
                'results' => $report->results,
                'failures' => (object) $report->failures,
                'judgeFailure' => $report->judgeFailure,
                'scores' => (object) $report->scores,
                'summary' => [
                    'cheapest' => $report->cheapest(),
                    'fastest' => $report->fastest(),
                    'best' => $report->best(),
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $exitCode;
        }

        // 1. Cost and speed, in the order the loops were asked for
        $this->table(
            ['Loop', 'Steps', 'Tokens in', 'Tokens out', 'Time', 'Status'],
            array_map(fn (string $name): array => $this->row($report, $name), $names),
        );

        foreach ($report->failures as $name => $message) {
            $this->components->warn("{$name}: {$message}");
        }

        if ($report->judgeFailure !== null) {
            $this->components->warn("judge: {$report->judgeFailure}");
        }

        if ($report->results !== []) {
            $summary = "Cheapest: {$report->cheapest()}  ·  Fastest: {$report->fastest()}";

            if ($report->best() !== null) {
                $summary .= "  ·  Best (judge): {$report->best()}";
            }

            $this->line($summary);
            $this->newLine();
        }

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

        return $exitCode;
    }

    /**
     * One table row: the loop's numbers, or dashes if it failed.
     *
     * @return list<string|int>
     */
    private function row(ComparisonReport $report, string $name): array
    {
        $result = $report->results[$name] ?? null;

        if ($result === null) {
            return [$name, '-', '-', '-', '-', 'failed'];
        }

        return [
            $name,
            count($result->steps),
            number_format($result->inputTokens),
            number_format($result->outputTokens),
            number_format($result->durationMs / 1000, 1).'s',
            $this->status($result),
        ];
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
