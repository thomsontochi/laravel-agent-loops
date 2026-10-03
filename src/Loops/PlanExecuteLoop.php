<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Loops;

use Developia\AgentLoops\Contracts\Loop;
use Developia\AgentLoops\Events\PlanningFailed;
use Developia\AgentLoops\Exceptions\PlanningFailedException;
use Developia\AgentLoops\LoopResult;
use Developia\AgentLoops\Planning\Planner;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

/**
 * Plan then execute: make the full plan first, then do each step.
 *
 * 1. Plan:    the Planner turns the task into a list of steps (structured output).
 * 2. Execute: the user's agent runs each step, seeing the results so far.
 * 3. Answer:  the user's agent writes the final answer from all step results.
 *
 * If planning fails, it degrades gracefully and reports loudly (see config).
 */
final class PlanExecuteLoop implements Loop
{
    private int $inputTokens = 0;

    private int $outputTokens = 0;

    public function name(): string
    {
        return 'plan-execute';
    }

    public function run(Agent $agent, string $task): LoopResult
    {
        $this->inputTokens = 0;
        $this->outputTokens = 0;
        $startedAt = hrtime(true);

        Log::debug('[agent-loops] plan-execute: start', ['agent' => $agent::class]);

        // 1. Plan
        try {
            $plan = $this->plan($agent, $task);
        } catch (FailoverableException $e) {
            // The provider is overloaded, rate limited or unreachable. That's an
            // outage, not a bad plan: falling back would hit the same provider.
            throw $e;
        } catch (Throwable $e) {
            return $this->handlePlanningFailure($agent, $task, $e->getMessage(), $startedAt, $e);
        }

        if ($plan === []) {
            return $this->handlePlanningFailure($agent, $task, 'empty plan', $startedAt);
        }

        $steps = [['type' => 'plan', 'content' => implode("\n", $plan)]];

        // 2. Execute each step, passing along the results so far
        $results = [];

        foreach ($plan as $index => $step) {
            $number = $index + 1;

            $response = $agent->prompt($this->stepPrompt($task, $plan, $results, $number, $step));
            $this->addUsage($response);

            $results[] = "Step {$number} ({$step}): {$response->text}";
            $steps[] = ['type' => 'step', 'content' => $response->text];

            Log::debug('[agent-loops] plan-execute: step done', ['step' => $number]);
        }

        // 3. Answer from all step results
        $final = $agent->prompt($this->answerPrompt($task, $results));
        $this->addUsage($final);
        $steps[] = ['type' => 'answer', 'content' => $final->text];

        Log::debug('[agent-loops] plan-execute: done', ['steps' => count($plan)]);

        return new LoopResult(
            loop: $this->name(),
            output: $final->text,
            steps: $steps,
            inputTokens: $this->inputTokens,
            outputTokens: $this->outputTokens,
            durationMs: $this->elapsedMs($startedAt),
        );
    }

    /**
     * Ask the Planner for steps, then clean and trim them to max_steps.
     *
     * @return list<string>
     */
    private function plan(Agent $agent, string $task): array
    {
        $maxSteps = max(1, (int) config('agent-loops.plan_execute.max_steps', 5));

        $response = (new Planner($agent->instructions(), $maxSteps))->prompt($task);
        $this->addUsage($response);

        $plan = array_values(array_filter(
            array_map(fn ($step): string => trim((string) $step), (array) ($response['steps'] ?? [])),
            fn (string $step): bool => $step !== '',
        ));

        if (count($plan) > $maxSteps) {
            Log::info('[agent-loops] plan-execute: plan trimmed', [
                'planned' => count($plan),
                'max_steps' => $maxSteps,
            ]);

            $plan = array_slice($plan, 0, $maxSteps);
        }

        return $plan;
    }

    /**
     * Degrade gracefully, report loudly: answer with ReAct, but log,
     * fire an event and flag the result. Or throw, if configured.
     */
    private function handlePlanningFailure(
        Agent $agent,
        string $task,
        string $reason,
        int $startedAt,
        ?Throwable $previous = null,
    ): LoopResult {
        if (config('agent-loops.plan_execute.on_planning_failure', 'fallback') === 'throw') {
            throw PlanningFailedException::for($task, $reason, $previous);
        }

        Log::warning('[agent-loops] plan-execute failed, fell back to react', [
            'agent' => $agent::class,
            'reason' => $reason,
        ]);

        PlanningFailed::dispatch($agent::class, $task, $reason);

        $fallback = (new ReActLoop)->run($agent, $task);

        return new LoopResult(
            loop: $this->name(),
            output: $fallback->output,
            steps: [['type' => 'fallback', 'content' => $reason], ...$fallback->steps],
            inputTokens: $this->inputTokens + $fallback->inputTokens,
            outputTokens: $this->outputTokens + $fallback->outputTokens,
            durationMs: $this->elapsedMs($startedAt),
        );
    }

    /**
     * @param  list<string>  $plan
     * @param  list<string>  $results
     */
    private function stepPrompt(string $task, array $plan, array $results, int $number, string $step): string
    {
        $planText = implode("\n", array_map(fn ($s, $i) => ($i + 1).". {$s}", $plan, array_keys($plan)));
        $resultsText = $results === [] ? 'None yet.' : implode("\n", $results);

        return <<<TEXT
        Overall task: {$task}

        Plan:
        {$planText}

        Results so far:
        {$resultsText}

        Now do step {$number} only: {$step}
        TEXT;
    }

    /**
     * @param  list<string>  $results
     */
    private function answerPrompt(string $task, array $results): string
    {
        $resultsText = implode("\n", $results);

        return <<<TEXT
        Overall task: {$task}

        Results of each step:
        {$resultsText}

        Using these results, give the final answer to the task.
        TEXT;
    }

    private function addUsage(AgentResponse $response): void
    {
        $this->inputTokens += $response->usage->inputTokens;
        $this->outputTokens += $response->usage->outputTokens;
    }

    private function elapsedMs(int $startedAt): float
    {
        return (hrtime(true) - $startedAt) / 1_000_000;
    }
}
