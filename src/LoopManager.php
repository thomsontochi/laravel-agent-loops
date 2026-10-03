<?php

declare(strict_types=1);

namespace Developia\AgentLoops;

use Developia\AgentLoops\Attributes\UseLoop;
use Developia\AgentLoops\Comparison\ComparisonReport;
use Developia\AgentLoops\Comparison\Judge;
use Developia\AgentLoops\Contracts\Loop;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Agent;
use ReflectionClass;

/**
 * The front door: picks a loop by name and runs it.
 *
 * Which loop runs, most specific first:
 *   1. AgentLoops::using('name') at the call site
 *   2. #[UseLoop('name')] on the agent class
 *   3. the 'default' in config/agent-loops.php
 */
final class LoopManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Get a loop by its name, e.g. "plan-execute".
     */
    public function using(string $name): Loop
    {
        $class = config("agent-loops.loops.{$name}");

        if (! is_string($class) || ! is_a($class, Loop::class, true)) {
            $available = implode(', ', array_keys((array) config('agent-loops.loops', [])));

            throw new InvalidArgumentException("Loop [{$name}] is not defined. Available loops: {$available}.");
        }

        // Resolved through the container, so loops can use dependency injection.
        return $this->container->make($class);
    }

    /**
     * Run the task with the agent's loop: its #[UseLoop] attribute, or the config default.
     */
    public function run(Agent $agent, string $task): LoopResult
    {
        return $this->using($this->loopNameFor($agent))->run($agent, $task);
    }

    /**
     * The loop name for this agent: its attribute if it has one, else the default.
     */
    public function loopNameFor(Agent $agent): string
    {
        $attributes = (new ReflectionClass($agent))->getAttributes(UseLoop::class);

        if ($attributes !== []) {
            return $attributes[0]->newInstance()->name;
        }

        return (string) config('agent-loops.default', 'react');
    }

    /**
     * Run the same task through several loops and report how each did.
     *
     * @param  list<string>  $loops  Loop names, e.g. ['react', 'reflect-retry']
     * @param  bool  $judge  Also ask a Judge agent to score each answer (extra AI call)
     */
    public function compare(Agent $agent, string $task, array $loops, bool $judge = false): ComparisonReport
    {
        if ($loops === []) {
            throw new InvalidArgumentException('Give at least one loop to compare.');
        }

        $results = [];

        foreach (array_unique($loops) as $name) {
            Log::debug('[agent-loops] compare: running loop', ['loop' => $name]);

            $results[$name] = $this->using($name)->run($agent, $task);
        }

        $scores = $judge ? $this->judge($task, $results) : [];

        return new ComparisonReport($task, $results, $scores);
    }

    /**
     * Ask the Judge to score each answer.
     *
     * @param  array<string, LoopResult>  $results
     * @return array<string, array{score: int, reason: string}>
     */
    private function judge(string $task, array $results): array
    {
        $answers = collect($results)
            ->map(fn (LoopResult $result, string $name): string => "[{$name}]\n{$result->output}")
            ->implode("\n\n");

        $response = (new Judge)->prompt("Task: {$task}\n\nAnswers:\n\n{$answers}");

        $scores = [];

        foreach ((array) ($response['scores'] ?? []) as $row) {
            $loop = (string) ($row['loop'] ?? '');

            // Ignore scores for loops we didn't run (the judge can mislabel).
            if (isset($results[$loop])) {
                $scores[$loop] = [
                    'score' => (int) ($row['score'] ?? 0),
                    'reason' => (string) ($row['reason'] ?? ''),
                ];
            }
        }

        return $scores;
    }
}
