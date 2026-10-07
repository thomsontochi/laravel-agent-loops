<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Console;

use Illuminate\Console\Command;

/**
 * php artisan agent-loops:list
 *
 * Shows every registered loop, what it does, and which one is the default.
 * Custom loops added to config/agent-loops.php show up automatically.
 */
final class ListCommand extends Command
{
    protected $signature = 'agent-loops:list';

    protected $description = 'List the available loops and what each one does';

    /**
     * Plain-English descriptions for the loops that ship with the package.
     */
    private const DESCRIPTIONS = [
        'react' => 'Answers in one go. Cheapest and fastest.',
        'plan-execute' => 'Plans the steps first, then works through them one by one.',
        'reflect-retry' => 'Writes an answer, checks it against your rules, fixes it if needed.',
    ];

    public function handle(): int
    {
        $loops = (array) config('agent-loops.loops', []);
        $default = (string) config('agent-loops.default', 'react');

        $rows = [];

        foreach ($loops as $name => $class) {
            $rows[] = [
                $name,
                self::DESCRIPTIONS[$name] ?? 'Custom loop ('.class_basename((string) $class).')',
                $name === $default ? 'default' : '',
            ];
        }

        $this->table(['Loop', 'What it does', ''], $rows);

        $this->line('Pick one with AgentLoops::using(\'name\'), #[UseLoop(\'name\')], or AGENT_LOOPS_DEFAULT in .env.');

        return self::SUCCESS;
    }
}
