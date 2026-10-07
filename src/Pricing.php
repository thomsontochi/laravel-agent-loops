<?php

declare(strict_types=1);

namespace Developia\AgentLoops;

/**
 * Turns token counts into an estimated dollar cost.
 *
 * Prices come from config('agent-loops.pricing'), in dollars per 1 million
 * tokens. It's an estimate: cached-token discounts, batch pricing and free
 * tiers are not counted.
 */
final class Pricing
{
    /**
     * @return float|null Cost in dollars, or null when we have no price for the model
     */
    public static function estimate(?string $model, int $inputTokens, int $outputTokens): ?float
    {
        // Index the list directly. Model IDs contain dots
        // ("gemini-3.1-flash-lite"), which config()'s dot syntax would split.
        $price = self::prices()[$model ?? ''] ?? null;

        // No model reported, or a model we don't have a price for: don't guess.
        if (! is_array($price)) {
            return null;
        }

        $cost = ($inputTokens * $price['input'] + $outputTokens * $price['output']) / 1_000_000;

        // Rounded so tiny float errors (0.0032500000001) don't leak into the table.
        return round($cost, 6);
    }

    /**
     * Built-in prices with the app's prices on top, model by model.
     *
     * Laravel's mergeConfigFrom() only merges top-level keys, so an app that
     * adds one model under 'pricing' would lose every built-in price. This
     * keeps them: the app adds models or overrides a price, per model.
     *
     * @return array<string, array{input: float|int, output: float|int}>
     */
    private static function prices(): array
    {
        $builtIn = (require __DIR__.'/../config/agent-loops.php')['pricing'];

        return array_merge($builtIn, config('agent-loops.pricing', []));
    }

    /**
     * Add one call's cost to a running total.
     *
     * Once any call is unpriced the total stays null: a total that quietly
     * leaves out a call would look cheaper than it really was.
     */
    public static function add(?float $total, ?float $cost): ?float
    {
        if ($total === null || $cost === null) {
            return null;
        }

        return round($total + $cost, 6);
    }
}
