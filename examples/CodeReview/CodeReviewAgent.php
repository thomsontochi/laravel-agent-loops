<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Example: a code review agent for Laravel code.
 *
 * The test: BuggyOrderController.php (next to this file) has exactly
 * 3 hidden bugs. We count how many each loop finds:
 *   1. SQL injection
 *   2. N+1 query
 *   3. Crash on a missing record (null)
 *
 * Compare it (run from your app root):
 *   php artisan agent-loops:compare "Review this code: $(cat BuggyOrderController.php)" --agent="App\Ai\Agents\CodeReviewAgent" --judge
 */
final class CodeReviewAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are a senior Laravel code reviewer.

        Find real bugs only: security holes, performance problems, and crashes.
        Ignore style, naming, and formatting.

        For each bug, reply in this exact format:
        1. [Severity: high/medium/low] What is wrong (one line)
           Where: the method name
           Fix: one line of corrected code or a short explanation

        If you are not sure something is a bug, leave it out.
        End with: "Total bugs found: N"
        TEXT;
    }
}
