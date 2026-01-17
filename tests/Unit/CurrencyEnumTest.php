<?php

use App\Enums\Finance\Currency;

it('returns a label for each currency', function () {
    expect(Currency::USD->label())->toBe('US Dollar')
        ->and(Currency::EUR->label())->toBe('Euro')
        ->and(Currency::COP->label())->toBe('Colombian Peso')
        ->and(Currency::GBP->label())->toBe('British Pound')
        ->and(Currency::JPY->label())->toBe('Japanese Yen');
});

it('returns symbols for each currency', function () {
    expect(Currency::USD->symbol())->toBe('$')
        ->and(Currency::EUR->symbol())->toBe('€')
        ->and(Currency::COP->symbol())->toBe('$')
        ->and(Currency::GBP->symbol())->toBe('£')
        ->and(Currency::JPY->symbol())->toBe('¥');
});
