# Contributing

Thanks for wanting to help. Issues, ideas and new loops are all welcome.

## Setup

```bash
git clone https://github.com/thomsontochi/laravel-agent-loops.git
cd laravel-agent-loops
composer install
composer test
```

`composer test` runs exactly what CI runs: the Pint style check, then Pest. Run `composer format` to fix style automatically.

## Ground rules

- **Tests never call a real AI.** Use Laravel AI's fakes (`YourAgent::fake([...])`, `Planner::fake()`, `Reviewer::fake()`, `Judge::fake()`).
- **Every change ships with a test.** For a bug, write the failing test first, then the fix.
- **Degrade gracefully, report loudly.** When something recoverable fails, still return an answer, but log a warning, fire an event or flag the result. Never fail silently.
- **Provider outages pass through.** Anything implementing `Laravel\Ai\Exceptions\FailoverableException` (overloaded, rate limited, unreachable) is not your loop's failure, so don't catch and hide it.
- **Real bugs must crash.** Catch `Exception` for runtime problems, never `Throwable`, so a `TypeError` still surfaces.

## Adding a new loop

1. Create `src/Loops/YourLoop.php` implementing `Developia\AgentLoops\Contracts\Loop`:
   - `name()` returns a short kebab-case name, e.g. `self-consistency`
   - `run()` returns a `LoopResult` with the output, the steps in order, the **total** tokens across every AI call (including helper agents), and the duration
2. If the loop needs a helper agent (like `Planner` or `Reviewer`), give it structured output so its replies are always valid JSON.
3. Register it in `config/agent-loops.php` under `loops`.
4. Add settings under their own key in the config (e.g. `'self_consistency' => [...]`), each with a sensible default and an env variable. The test `it ships every setting in the config file` must list them.
5. Add `tests/Unit/YourLoopTest.php` covering at least: its name, the happy path, how it records steps and tokens, and what happens when it fails.
6. Document it in the README's loops table and the "Which loop should I use?" section.

## Commits

We use [Conventional Commits](https://www.conventionalcommits.org): `feat:`, `fix:`, `docs:`, `test:`, `ci:`, `style:`, `chore:`.
