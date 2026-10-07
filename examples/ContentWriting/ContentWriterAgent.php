<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Example: a content writer for an online store's product pages.
 *
 * The test: write ONE product description that follows a strict brief.
 * We compare cost (tokens) against quality (judge score) to answer:
 * is paying 10x more for a "smarter" loop actually worth it?
 *
 * Compare it:
 *   php artisan agent-loops:compare "Write a product description for the AquaPure smart water bottle: tracks how much you drink, glows to remind you to sip, keeps water cold for 24 hours, battery lasts 2 weeks." --agent="App\Ai\Agents\ContentWriterAgent" --judge
 */
final class ContentWriterAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You write product descriptions for an online store.

        The brief (every rule must be followed):
        - Exactly ONE description, never options or variations.
        - 50 to 80 words.
        - Start with the customer's problem, not the product name.
        - Mention at least 3 features, each turned into a benefit.
        - Banned words: "revolutionary", "game-changer", "elevate", "unleash", "seamless".
        - End with a short call to action.
        - Plain text only: no headings, no bullet points, no emojis.
        TEXT;
    }
}
