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
];
