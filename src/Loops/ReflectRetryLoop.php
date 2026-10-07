<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Loops;

use Developia\AgentLoops\Contracts\Loop;
use Developia\AgentLoops\Grounding;
use Developia\AgentLoops\LoopResult;
use Developia\AgentLoops\Reflection\Reviewer;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Reflect and retry: do the task, review the work, fix it if needed.
 *
 * 1. Attempt: the user's agent does the task.
 * 2. Review:  the Reviewer approves it, or rejects it with feedback.
 * 3. Retry:   if rejected, the agent redoes it using the feedback,
 *             up to max_retries times.
 *
 * If it's still not approved, it returns the last attempt and reports loudly.
 */
final class ReflectRetryLoop implements Loop
{
    private int $inputTokens = 0;

    private int $outputTokens = 0;

    public function name(): string
    {
        return 'reflect-retry';
    }

    public function run(Agent $agent, string $task): LoopResult
    {
        $this->inputTokens = 0;
        $this->outputTokens = 0;
        $startedAt = hrtime(true);

        $maxRetries = max(0, (int) config('agent-loops.reflect_retry.max_retries', 2));
        $reviewer = new Reviewer($agent->instructions());

        Log::debug('[agent-loops] reflect-retry: start', ['agent' => $agent::class, 'max_retries' => $maxRetries]);

        // 1. First attempt
        $answer = $this->ask($agent, $task."\n\n".Grounding::TEXT);
        $steps = [['type' => 'attempt', 'content' => $answer]];

        for ($retry = 0; $retry <= $maxRetries; $retry++) {
            // 2. Review the latest attempt
            $review = $reviewer->prompt($this->reviewPrompt($task, $answer));
            $this->addUsage($review);

            $approved = (bool) ($review['approved'] ?? false);
            $feedback = trim((string) ($review['feedback'] ?? ''));

            $steps[] = ['type' => $approved ? 'review_approved' : 'review_rejected', 'content' => $feedback];

            if ($approved) {
                Log::debug('[agent-loops] reflect-retry: approved', ['retries' => $retry]);

                return $this->result($answer, $steps, $startedAt);
            }

            // 3. Retry with the feedback, unless we're out of retries
            if ($retry === $maxRetries) {
                break;
            }

            $answer = $this->ask($agent, $this->retryPrompt($task, $answer, $feedback));
            $steps[] = ['type' => 'attempt', 'content' => $answer];
        }

        // Out of retries: degrade gracefully, report loudly
        Log::warning('[agent-loops] reflect-retry: not approved, returning last attempt', [
            'agent' => $agent::class,
            'max_retries' => $maxRetries,
        ]);

        $steps[] = ['type' => 'not_approved', 'content' => "Not approved after {$maxRetries} retries"];

        return $this->result($answer, $steps, $startedAt);
    }

    /**
     * Prompt the user's agent, count its tokens, return its text.
     */
    private function ask(Agent $agent, string $prompt): string
    {
        $response = $agent->prompt($prompt);
        $this->addUsage($response);

        return $response->text;
    }

    private function reviewPrompt(string $task, string $answer): string
    {
        return <<<TEXT
        Task:
        {$task}

        Answer to review:
        {$answer}
        TEXT;
    }

    private function retryPrompt(string $task, string $previousAnswer, string $feedback): string
    {
        return <<<TEXT
        Task: {$task}

        Your previous answer:
        {$previousAnswer}

        A reviewer rejected it with this feedback:
        {$feedback}

        Write an improved answer that fixes these problems.
        TEXT."\n\n".Grounding::TEXT;
    }

    /**
     * @param  array<int, array{type: string, content: string}>  $steps
     */
    private function result(string $answer, array $steps, int $startedAt): LoopResult
    {
        return new LoopResult(
            loop: $this->name(),
            output: $answer,
            steps: $steps,
            inputTokens: $this->inputTokens,
            outputTokens: $this->outputTokens,
            durationMs: (hrtime(true) - $startedAt) / 1_000_000,
        );
    }

    private function addUsage(AgentResponse $response): void
    {
        $this->inputTokens += $response->usage->inputTokens;
        $this->outputTokens += $response->usage->outputTokens;
    }
}
