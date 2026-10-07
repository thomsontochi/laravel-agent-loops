<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Example: replying to an angry email (the everyday one).
 *
 * The test: a client is furious that a project is late. A good reply
 * owns the problem, gives a real new date, and stays calm. A bad reply
 * over-apologises, gets defensive, or promises what it can't deliver.
 *
 * Compare it:
 *   php artisan agent-loops:compare "Reply to this email from a client: 'This is the THIRD time the website launch has been pushed back. I've already told my customers it goes live Friday. If it's not ready I'm taking my business elsewhere.' Facts: the launch slipped because the payment provider approval is delayed. Realistic new date: next Wednesday. We can put a holding page up by Friday." --agent="App\Ai\Agents\EmailReplyAgent" --judge
 */
final class EmailReplyAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You write replies to difficult emails on behalf of a professional.

        Rules for every reply:
        - Exactly ONE reply, ready to send. Never options or variations.
        - Under 150 words.
        - Acknowledge their frustration in one sentence, then move on.
        - Own the problem. No blaming others and no excuses beyond one short reason.
        - Only promise what the facts given allow. Never invent dates or offers.
        - Give one clear next step with a date.
        - Calm, warm, professional. No grovelling, no "I deeply apologise for any inconvenience".
        - Plain text, no subject line.
        TEXT;
    }
}
