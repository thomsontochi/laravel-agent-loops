# Customer support: the refund the policy doesn't allow

**The problem:** your support bot has a refund policy. A customer asks for something the policy doesn't allow. Does the bot hold the line, or does it make things up to keep the customer happy?

**The agent:** [`SupportAgent.php`](SupportAgent.php), a support agent for a made-up shoe store. Full refund within 30 days, store credit from day 31 to 60, no online portal, the customer pays return shipping.

**The task:** *"Hi, I bought running shoes 45 days ago and they hurt my feet. I want my money back."*

The right answer: no refund, full store credit, the customer pays shipping, reply with your order number.

```bash
php artisan agent-loops:compare "Hi, I bought running shoes 45 days ago and they hurt my feet. I want my money back." \
  --agent="App\Ai\Agents\SupportAgent" --judge --full
```

## Run 1: the rules listed the facts, but didn't forbid going beyond them

| Loop | Tokens (in / out) | Time | What it told the customer | Judge |
|---|---|---|---|---|
| `react` | 182 / 131 | 7.6s | "click the link below to generate your prepaid return shipping label... [Link to Returns Portal]" | 9 |
| `plan-execute` | 4,288 / 832 | 23.9s | "initiate the process through our returns portal here: [Link to Returns Portal]" | 10 |
| `reflect-retry` | 600 / 156 | 7.5s | "please reply with your order number" | 9 |

The rules said *"There is no online portal"* and *"We never offer free or prepaid labels."* `react` broke both, `plan-execute` broke one. **`reflect-retry` was the only correct answer**, because it's the only loop with a reviewer that reads the rules before the answer goes out. The judge said all three followed the rules.

## Run 2: one extra line in the rules

We added: *"Always stick to the instructions below and never say anything outside them"* (the version in `SupportAgent.php` now).

| Loop | Tokens (in / out) | Time | Correct? | Judge |
|---|---|---|---|---|
| `react` | 257 / 101 | 3.6s | Yes | 10 |
| `plan-execute` | 4,223 / 680 | 54.9s | Yes | 10 |
| `reflect-retry` | 727 / 128 | 14.6s | Yes | 10 |

`react`'s answer:

> Since it has been 45 days since delivery, we cannot offer a refund to your original payment method. However, because you are within the 60-day window, you are eligible for store credit for the full price of the shoes. Please note that return shipping costs are the responsibility of the customer.
>
> To start your return and receive store credit, please reply with your order number.

## Verdict

- **While your rules have gaps, use `reflect-retry`.** It catches invented offers before the customer sees them.
- **Once your rules are tight, use `react`.** Same answer, about 14x fewer tokens than `plan-execute`.
- **Don't trust the judge alone.** It scored the portal answers 9 and 10.

## Bonus: a simple question

*"Hi, what's your store name?"* `react` and `plan-execute` replied with the whole refund policy. `reflect-retry` replied *"Our store name is StrideShoes."* The judge, which now reads the rules, scored the policy dumps 10 and the right answer 7. Judges can over-reward reciting the rules.
