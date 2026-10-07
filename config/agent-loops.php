<?php

use Developia\AgentLoops\Loops\PlanExecuteLoop;
use Developia\AgentLoops\Loops\ReActLoop;
use Developia\AgentLoops\Loops\ReflectRetryLoop;

return [
    /*
    |--------------------------------------------------------------------------
    | Default loop
    |--------------------------------------------------------------------------
    |
    | The loop AgentLoops::run() uses when nothing more specific is chosen.
    | Order of priority: AgentLoops::using('name') at the call site, then a
    | #[UseLoop('name')] attribute on the agent, then this default.
    |
    */

    'default' => env('AGENT_LOOPS_DEFAULT', 'react'),

    /*
    |--------------------------------------------------------------------------
    | Available loops
    |--------------------------------------------------------------------------
    |
    | Name => class. Add your own class implementing
    | Developia\AgentLoops\Contracts\Loop to use a custom loop by name.
    |
    */

    'loops' => [
        'react' => ReActLoop::class,
        'plan-execute' => PlanExecuteLoop::class,
        'reflect-retry' => ReflectRetryLoop::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan then execute
    |--------------------------------------------------------------------------
    |
    | max_steps: the most steps a plan may have. Longer plans are trimmed,
    | which stops a runaway plan from burning tokens.
    |
    | on_planning_failure: what to do when the agent can't produce a plan.
    |   "fallback" (default): run the task with ReAct, still return an answer,
    |                         and report it (warning log, event, result flag).
    |   "throw":              throw a PlanningFailedException instead.
    |
    */

    'plan_execute' => [
        'max_steps' => (int) env('AGENT_LOOPS_MAX_STEPS', 5),
        'on_planning_failure' => env('AGENT_LOOPS_ON_PLANNING_FAILURE', 'fallback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reflect and retry
    |--------------------------------------------------------------------------
    |
    | max_retries: how many times the agent may redo its answer after a
    | rejected review. Each retry is another AI call, so keep it small.
    | When retries run out, the last attempt is returned flagged
    | "not_approved" (LoopResult::approved() is false).
    |
    */

    'reflect_retry' => [
        'max_retries' => (int) env('AGENT_LOOPS_MAX_RETRIES', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | Dollars per 1 million tokens, standard paid rate. Used to show an
    | estimated cost per run. The model name is read from each AI response,
    | so the key must match the provider's model ID exactly.
    |
    | Not counted: cached-token discounts, batch pricing, free tiers, and
    | higher rates for very long prompts. A model missing here shows "-".
    | Add your own models or change any price as you need.
    |
    | Checked 7 Oct 2026 against:
    |   https://ai.google.dev/gemini-api/docs/pricing
    |   https://developers.openai.com/api/docs/pricing
    |   https://platform.claude.com/docs/en/about-claude/pricing
    |
    */

    'pricing' => [
        // Google Gemini (output price includes thinking tokens)
        'gemini-3.1-flash-lite' => ['input' => 0.25, 'output' => 1.50],
        'gemini-3-flash-preview' => ['input' => 0.50, 'output' => 3.00],
        'gemini-3.5-flash-lite' => ['input' => 0.30, 'output' => 2.50],
        'gemini-3.5-flash' => ['input' => 1.50, 'output' => 9.00],
        // 3.6 to 3.8 Flash: these prices hold until 31 Dec 2026, then 1.50 / 7.50
        'gemini-3.8-flash' => ['input' => 0.75, 'output' => 3.75],
        'gemini-3.7-flash' => ['input' => 0.75, 'output' => 3.75],
        'gemini-3.6-flash' => ['input' => 0.75, 'output' => 3.75],
        'gemini-3.1-pro-preview' => ['input' => 2.00, 'output' => 12.00],
        'gemini-2.5-pro' => ['input' => 1.25, 'output' => 10.00],
        'gemini-2.5-flash' => ['input' => 0.30, 'output' => 2.50],
        'gemini-2.5-flash-lite' => ['input' => 0.10, 'output' => 0.40],

        // OpenAI
        'gpt-6-astra' => ['input' => 10.00, 'output' => 50.00],
        'gpt-6.1-sol' => ['input' => 2.00, 'output' => 10.00],
        'gpt-6-luna' => ['input' => 0.10, 'output' => 0.50],
        'gpt-5.6-sol' => ['input' => 4.00, 'output' => 20.00],
        'chat-latest' => ['input' => 5.00, 'output' => 30.00],
        'gpt-5.3-codex' => ['input' => 1.75, 'output' => 14.00],

        // Anthropic
        'claude-fable-5-1' => ['input' => 10.00, 'output' => 50.00],
        'claude-opus-5-5' => ['input' => 4.00, 'output' => 20.00],
        'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00],
        'claude-haiku-4-5-20251001' => ['input' => 1.00, 'output' => 5.00],
    ],
];
