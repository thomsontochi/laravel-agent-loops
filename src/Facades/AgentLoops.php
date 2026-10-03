<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Facades;

use Developia\AgentLoops\Comparison\ComparisonReport;
use Developia\AgentLoops\Contracts\Loop;
use Developia\AgentLoops\LoopManager;
use Developia\AgentLoops\LoopResult;
use Illuminate\Support\Facades\Facade;
use Laravel\Ai\Contracts\Agent;

/**
 * @method static string loopNameFor(Agent $agent)
 * @method static ComparisonReport compare(Agent $agent, string $task, array $loops, bool $judge = false)
 *

 * AgentLoops::run($agent, $task)
 * AgentLoops::using('plan-execute')->run($agent, $task)
 * @method static Loop using(string $name)
 * @method static LoopResult run(Agent $agent, string $task)
 * @method static string loopNameFor(Agent $agent)
 * @method static ComparisonReport compare(Agent $agent, string $task, array $loops, bool $judge = false)
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
