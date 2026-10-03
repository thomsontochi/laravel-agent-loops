<?php

return [

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

];
