# Code review: three hidden bugs

**The problem:** you want an AI reviewer on your pull requests. Does it find the real bugs, and does it stay quiet about things that aren't bugs?

**The agent:** [`CodeReviewAgent.php`](CodeReviewAgent.php), a senior Laravel reviewer told to report real bugs only, in a fixed format, ending with "Total bugs found: N".

**The code:** [`BuggyOrderController.php`](BuggyOrderController.php), a normal-looking controller with exactly 3 bugs and no hints in the file.

| # | Where | Bug | Fix |
|---|---|---|---|
| 1 | `index()` | SQL injection: `$status` pasted into raw SQL | `Order::where('status', $status)->get()` or parameter binding |
| 2 | `report()` | N+1 query: `$order->customer` in a loop | `->with('customer')` |
| 3 | `show()` | Crash on a missing order: `find()` returns null | `Order::findOrFail($id)` |

```bash
php artisan agent-loops:compare "Review this code: $(cat BuggyOrderController.php)" \
  --agent="App\Ai\Agents\CodeReviewAgent" --judge --full
```

## Results

| Loop | Bugs found | Tokens (in / out) | Time | Judge |
|---|---|---|---|---|
| `react` | 3 / 3 | 411 / 159 | 3.2s | 10 |
| `plan-execute` | 3 / 3 | 5,277 / 784 | 28.8s | 10 |
| `reflect-retry` | 3 / 3 | 1,108 / 184 | 6.2s | 9 |

No loop invented a fourth bug or padded the list with style notes. The fixes were nearly identical. The judge took a point off `reflect-retry` for calling the null crash "high" instead of "medium", which is a judgement call, not a mistake.

## Verdict

**Use `react`.** For textbook bugs, every loop finds them, and `plan-execute` spent 11x the tokens to write the same three lines. To see the loops pull apart, try subtler bugs: race conditions, a missing authorisation check, a timezone mistake.
