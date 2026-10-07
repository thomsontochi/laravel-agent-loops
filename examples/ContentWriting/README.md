# Content writing: one description, a strict brief

**The problem:** you generate product copy with AI. Is a "smarter" loop worth 10x the tokens, and does any of them stick to the facts?

**The agent:** [`ContentWriterAgent.php`](ContentWriterAgent.php). The brief: exactly one description, 50 to 80 words, start with the customer's problem, at least 3 features turned into benefits, banned words ("revolutionary", "game-changer", "elevate", "unleash", "seamless"), end with a call to action, plain text.

**The task:** a made-up product, the AquaPure smart water bottle: *tracks how much you drink, glows to remind you to sip, keeps water cold for 24 hours, battery lasts 2 weeks.*

```bash
php artisan agent-loops:compare "Write a product description for the AquaPure smart water bottle: tracks how much you drink, glows to remind you to sip, keeps water cold for 24 hours, battery lasts 2 weeks." \
  --agent="App\Ai\Agents\ContentWriterAgent" --judge --full
```

## Results

| Loop | Tokens (in / out) | Time | Words | Met the brief? | Judge |
|---|---|---|---|---|---|
| `react` | 165 / 91 | 1.9s | 74 | Yes | 9 |
| `plan-execute` | 3,523 / 644 | 32.8s | 75 | Yes | **3** |
| `reflect-retry` | 541 / 128 | 22.0s | 76 | Yes | 8 |

Two things the scores don't show:

1. **The judge was wrong about `plan-execute`.** It said naming "AquaPure" in the second sentence broke "start with the customer's problem". It didn't: the first sentence is the problem.
2. **Every loop added facts the task never gave**: "double-wall insulation", "vacuum-insulated steel", and a health claim, "fatigue and headaches". On a real product page, that's a promise you might not be able to keep.

## After adding grounding (v0.2.0)

v0.2.0 tells every loop to answer only from the facts given. Same task, same agent:

| Loop | Tokens (in / out) | Time | Still invented |
|---|---|---|---|
| `react` | 202 / 90 | 13.0s | "double-wall insulation" |
| `plan-execute` | 3,458 / 571 | 117.1s | "vacuum insulation", "headaches" |
| `reflect-retry` | 608 / 128 | 26.0s | "headaches" (plain "insulation" is a fair inference) |

"Steel" was gone, but the rest stayed. Grounding is a nudge, not a lock.

## Verdict

**Use `react`, and put the rule in the brief:** *"Only use the product facts given in the task. Never add materials, specs, or health claims."* The agent's own instructions do the heavy lifting.
