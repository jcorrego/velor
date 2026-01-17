<?php

namespace Tests;

use App\Models\Currency;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get or create a currency by code.
     */
    protected function getCurrency(string $code): Currency
    {
        $currencies = [
            'EUR' => ['name' => 'Euro', 'symbol' => '€'],
            'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
            'COP' => ['name' => 'Colombian Peso', 'symbol' => '$'],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
        ];

        $data = $currencies[$code] ?? ['name' => $code, 'symbol' => '$'];

        return Currency::firstOrCreate(
            ['code' => $code],
            array_merge($data, ['is_active' => true])
        );
    }
}
