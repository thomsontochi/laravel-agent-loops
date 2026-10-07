# Changelog

All notable changes to this package are documented here. This project follows [Semantic Versioning](https://semver.org).

## v0.2.1 - 2026-10-07

### Added

- `agent-loops:list` shows every registered loop, what it does, and which one is the default. Custom loops from `config/agent-loops.php` are listed too.

## v0.2.0 - 2026-10-07

Everything in this release came out of running the package on real tasks and reading what each loop actually said.

### Added

- `--full` option on `agent-loops:compare` prints every answer in full instead of a 100-character preview.
- Grounding: every loop now adds one line to the prompts it sends, telling the model to answer only from the facts in the task and the agent's instructions, and to say when a fact is missing instead of inventing it. Lives in `Developia\AgentLoops\Grounding` so the wording is tuned in one place.
- `examples/` folder with four real use cases (customer support, code review, content writing, an angry client email), each with a copy-paste agent and the real results.

### Changed

- The judge now receives the agent's instructions as the rules to check, and is told to score any answer that breaks a rule or invents an offer, date, link, feature or process at 4 or lower.
- The reflect-retry reviewer now rejects answers that break the agent's instructions or state things they don't support.
- The plan-execute planner is told to plan only from the information given.

### Notes

- AI judges are a second opinion, not the truth. In our runs the judge missed a rule violation and, in another run, punished an answer that broke no rule. Read the answers with `--full`.

## v0.1.0 - 2026-10-03

First release: `react`, `plan-execute` and `reflect-retry` loops, three ways to pick a loop (`using()`, `#[UseLoop]`, config), `agent-loops:compare` with an optional judge and JSON output, `agent-loops:install`, and the `PlanningFailed` event.
