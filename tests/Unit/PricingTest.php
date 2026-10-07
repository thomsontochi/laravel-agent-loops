<?php

declare(strict_types=1);

use Developia\AgentLoops\Pricing;

beforeEach(function () {
    // Our own small price list, so the tests don't depend on real prices changing.
    config()->set('agent-loops.pricing', [
        'test-model' => ['input' => 0.25, 'output' => 1.50],
        'test-3.1-model' => ['input' => 2.00, 'output' => 10.00],
    ]);
});

it('works out the cost from tokens and the price per million', function () {
    // 1,000 input × $0.25/1M + 2,000 output × $1.50/1M = 0.00025 + 0.003
    expect(Pricing::estimate('test-model', 1000, 2000))->toBe(0.00325);
});

it('handles model names that contain dots', function () {
    // 1,000,000 input × $2/1M + 0 output = $2
    expect(Pricing::estimate('test-3.1-model', 1_000_000, 0))->toBe(2.0);
});

it('returns null for a model that is not in the price list', function () {
    expect(Pricing::estimate('unknown-model', 1000, 2000))->toBeNull();
});

it('returns null when the model is not known at all', function () {
    expect(Pricing::estimate(null, 1000, 2000))->toBeNull();
});

it('ships a price list with an input and output price for every model', function () {
    // Read the file itself, so this checks what users actually get on install.
    $pricing = (require __DIR__.'/../../config/agent-loops.php')['pricing'] ?? [];

    // Our playground model must be there, by its exact name.
    expect(array_keys($pricing))->toContain('gemini-3.1-flash-lite');

    foreach ($pricing as $model => $price) {
        expect($price)->toHaveKeys(['input', 'output'], "{$model} is missing a price")
            ->and($price['input'])->toBeNumeric()
            ->and($price['output'])->toBeNumeric();
    }
});

it('adds the app prices to the built-in list instead of replacing it', function () {
    // The app only lists its own model and changes one built-in price.
    config()->set('agent-loops.pricing', [
        'my-model' => ['input' => 3.00, 'output' => 0.00],
        'gemini-3.1-flash-lite' => ['input' => 9.00, 'output' => 0.00],
    ]);

    expect(Pricing::estimate('my-model', 1_000_000, 0))->toBe(3.0)       // app's own model
        ->and(Pricing::estimate('gemini-3.1-flash-lite', 1_000_000, 0))->toBe(9.0) // app's price wins
        ->and(Pricing::estimate('claude-sonnet-5-5', 1_000_000, 0))->toBe(2.0);    // built-in still there
});
