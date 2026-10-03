<?php

declare(strict_types=1);

namespace Developia\AgentLoops\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * php artisan agent-loops:install
 *
 * Publishes the config, says hello, and shows how to get started.
 * Purely cosmetic on purpose: it never touches .env or creates classes.
 */
final class InstallCommand extends Command
{
    private const REPO_URL = 'https://github.com/thomsontochi/laravel-agent-loops';

    protected $signature = 'agent-loops:install
        {--force : Overwrite an existing config/agent-loops.php}';

    protected $description = 'Publish the config and get started with Laravel Agent Loops';

    public function handle(): int
    {
        $this->publishConfig();
        $this->welcome();
        $this->askForStar();

        return self::SUCCESS;
    }

    /**
     * Copy our config into the app, but never overwrite the user's changes
     * unless they ask with --force.
     */
    private function publishConfig(): void
    {
        if (file_exists(config_path('agent-loops.php')) && ! $this->option('force')) {
            $this->components->info('Config already exists at config/agent-loops.php, kept as is. Use --force to overwrite.');

            return;
        }

        $this->callSilently('vendor:publish', ['--tag' => 'agent-loops-config', '--force' => true]);

        $this->components->info('Config published to config/agent-loops.php.');
    }

    private function welcome(): void
    {
        $this->line('  <options=bold>Thanks for installing Laravel Agent Loops.</>');
        $this->newLine();
        $this->line("  I'm Austin (Developia), a Laravel engineer in Lagos. I built this");
        $this->line('  because every agent package picked the thinking style for me, and I');
        $this->line('  wanted to choose it, switch it, and measure which one actually works.');
        $this->newLine();
        $this->line('  <fg=yellow>Pick a loop three ways:</>');
        $this->line('    <fg=gray>.env</>       AGENT_LOOPS_DEFAULT=reflect-retry');
        $this->line("    <fg=gray>attribute</>  #[UseLoop('plan-execute')]");
        $this->line("    <fg=gray>call site</>  AgentLoops::using('react')->run(\$agent, \$task)");
        $this->newLine();
        $this->line('  <fg=yellow>Then see which one wins for your task:</>');
        $this->line('    php artisan agent-loops:compare "Summarise this ticket"');
        $this->newLine();
        $this->line('  Built in public. Issues, ideas and loops welcome:');
        $this->line('  <fg=cyan>'.self::REPO_URL.'</>');
        $this->newLine();
    }

    /**
     * Ask for a GitHub star, Pest-style. Defaults to no, and is skipped
     * entirely when nobody is there to answer (CI, --no-interaction).
     */
    private function askForStar(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        if (! $this->confirm('Would you like to star the repo on GitHub?', false)) {
            return;
        }

        $this->openInBrowser(self::REPO_URL);

        $this->line('  <fg=green>Opening '.self::REPO_URL.'... thank you!</>');
    }

    /**
     * Best effort: if the browser can't open (e.g. a server with no desktop),
     * nothing breaks, because the link is printed anyway.
     */
    private function openInBrowser(string $url): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => ['open', $url],
            'Windows' => ['cmd', '/c', 'start', '', $url],
            default => ['xdg-open', $url],
        };

        Process::run($command);
    }
}
