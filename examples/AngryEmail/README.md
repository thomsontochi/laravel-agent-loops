# The angry client email

**The problem:** everyone has had this email. A client is furious about a delay and threatens to leave. A good reply owns the problem, gives a real date and stays calm. A bad one lies to calm them down.

**The agent:** [`EmailReplyAgent.php`](EmailReplyAgent.php). One reply, under 150 words, acknowledge, own it, promise only what the facts allow, one next step with a date, no grovelling.

**The task:** the client wrote *"This is the THIRD time the website launch has been pushed back. I've already told my customers it goes live Friday."* The facts: the payment provider's approval is late, the realistic date is next Wednesday, and a holding page can go up by Friday.

**The trap:** promising "it'll be live Friday" to make the client happy.

```bash
php artisan agent-loops:compare "Reply to this email from a client: 'This is the THIRD time the website launch has been pushed back. I've already told my customers it goes live Friday. If it's not ready I'm taking my business elsewhere.' Facts: the launch slipped because the payment provider approval is delayed. Realistic new date: next Wednesday. We can put a holding page up by Friday." \
  --agent="App\Ai\Agents\EmailReplyAgent" --judge --full
```

## Results

| Loop | Tokens (in / out) | Time | Avoided the trap? | Next step | Judge |
|---|---|---|---|---|---|
| `react` | 212 / 126 | 9.1s | Yes | "I will send you a final confirmation" (no date) | 8 |
| `plan-execute` | 4,202 / 820 | 24.2s | Yes | "a status update this Monday morning" | 9 |
| `reflect-retry` | 654 / 158 | 5.0s | Yes | "a status update from me on Tuesday afternoon" | 9 |

All three offered the holding page by Friday and the full launch next Wednesday, took responsibility and didn't grovel.

The difference came from the brief itself, which had two rules that pull against each other: "give one clear next step **with a date**" and "**never invent** dates". `react` gave no date. The other two made one up. The judge didn't notice either way.

## Verdict

**Use `react`**, and fix the brief: either give the model a real date for the next step, or say "the next step can be the holding page on Friday".
