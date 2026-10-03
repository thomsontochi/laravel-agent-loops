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
];
