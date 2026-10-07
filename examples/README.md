# Examples: four everyday jobs, three loops, real results

Each folder has a copy-paste agent and the real results of racing all three loops on it with `agent-loops:compare`. Nothing here is made up: the numbers and quotes come from runs on Google Gemini 3.1 Flash-Lite in October 2026. Times swing a lot with provider load, so read them as rough.

| Use case | The test | What happened | Pick |
|---|---|---|---|
| [Customer support](CustomerSupport) | A refund the policy doesn't allow (day 45, limit 30) | With vague rules, `react` and `plan-execute` sent the customer to a portal that doesn't exist. Only `reflect-retry` got it right. With clear rules, all three did. | `react` once the rules are clear, `reflect-retry` while they aren't |
| [Code review](CodeReview) | A controller with 3 hidden bugs | All three found 3/3 and invented nothing | `react` (11x cheaper than `plan-execute`) |
| [Content writing](ContentWriting) | One product description with a strict brief | All three met the brief, and all three added specs the task never gave ("double-wall insulation") | `react`, and add "only use the facts given" to the brief |
| [Angry client email](AngryEmail) | A client threatens to leave over a late launch | All three avoided promising the launch date the client wanted | `react` |

## What we learned

1. **Unclear instructions get filled in.** When the rules don't say how returns work, the model writes what most stores do. Fix the instructions before you blame the loop.
2. **With clear instructions, the simplest loop was enough.** `react` matched the others at a fraction of the tokens in every use case.
3. **`reflect-retry` is the safety net.** It's the only loop that re-reads your rules before answering, and it was the only one that caught the invented portal.
4. **AI judges are a second opinion.** The judge gave 10/10 to a rule-breaking answer in one run and 3/10 to a correct one in another. Read the answers with `--full`.

## Run them yourself

1. Copy an agent into your app, e.g. `examples/CustomerSupport/SupportAgent.php` to `app/Ai/Agents/SupportAgent.php`.
2. Run the command from the docblock at the top of the file, adding `--full` to read every answer:

```bash
php artisan agent-loops:compare "Hi, I bought running shoes 45 days ago and they hurt my feet. I want my money back." \
  --agent="App\Ai\Agents\SupportAgent" --judge --full
```

These files are not part of the package download. They're here to copy from.
