<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Loops;

use Developia\AgentLoops\Contracts\Loop;
use Developia\AgentLoops\LoopResult;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\Data\ToolCall;

/**
 * ReAct: think, act, look, repeat.
 *
 * laravel/ai already runs this cycle inside prompt(): the model thinks,
 * calls a tool, reads the result, and repeats until it answers.
 * So this loop's job is to run it once and record what happened.
 */
final class ReActLoop implements Loop
{
    public function name(): string
    {
        return 'react';
    }

    public function run(Agent $agent, string $task): LoopResult
    {
        Log::debug('[agent-loops] react: start', ['agent' => $agent::class]);

        $startedAt = hrtime(true);

        $response = $agent->prompt($task);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        // Record each tool the agent used, then the final answer.
        $steps = $response->toolCalls
            ->map(fn (ToolCall $call): array => ['type' => 'tool_call', 'content' => $call->name])
            ->push(['type' => 'answer', 'content' => $response->text])
            ->values()
            ->all();

        Log::debug('[agent-loops] react: done', [
            'tool_calls' => $response->toolCalls->count(),
            'duration_ms' => $durationMs,
        ]);

        return new LoopResult(
            loop: $this->name(),
            output: $response->text,
            steps: $steps,
            inputTokens: $response->usage->inputTokens,
            outputTokens: $response->usage->outputTokens,
            durationMs: $durationMs,
        );
    }
}
