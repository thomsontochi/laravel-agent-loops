<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Facades;

use Developia\AgentLoops\Contracts\Loop;
use Developia\AgentLoops\LoopManager;
use Developia\AgentLoops\LoopResult;
use Illuminate\Support\Facades\Facade;
use Laravel\Ai\Contracts\Agent;

/**
 * AgentLoops::run($agent, $task)
 * AgentLoops::using('plan-execute')->run($agent, $task)
 *
 * @method static Loop using(string $name)
 * @method static LoopResult run(Agent $agent, string $task)
 * @method static string loopNameFor(Agent $agent)
 *
 * @see LoopManager
 */
final class AgentLoops extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LoopManager::class;
    }
}
