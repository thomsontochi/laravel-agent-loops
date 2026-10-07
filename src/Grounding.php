<?php

declare(strict_types=1);

namespace Developia\AgentLoops;

/**
 * The one rule every prompt in this package carries.
 *
 * LLMs follow instructions probabilistically, so we say it plainly in every
 * prompt: answer from the facts given, or say we do not know. Never invent
 * links, dates, offers, features or processes.
 */
final class Grounding
{
    public const TEXT = 'Answer only from the facts in the task and your instructions. If a fact is missing, say we do not know — never invent links, dates, offers, features or processes.';
}
