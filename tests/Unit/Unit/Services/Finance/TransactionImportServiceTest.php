<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\User;
use App\Services\Finance\TransactionImportService;

it('parses santander csv correctly', function () {
    $service = app(TransactionImportService::class);

    // Create a sample CSV file in Santander format (semicolon-delimited)
    $csvContent = <<<'CSV'
Fecha;Movimiento;Cantidad;Saldo;Referencia
17/01/2025;Transferencia salario;-1000.00;5000.00;REF001
15/01/2025;Compra tarjeta;-50.50;6000.50;REF002
CSV;

    $file = tempnam(sys_get_temp_dir(), 'test_csv_');
    file_put_contents($file, $csvContent);

    try {
        $parsed = $service->parseCSV($file, 'santander');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['description'])->toBe('Transferencia salario');
        expect($parsed[0]['amount'])->toBe(-1000.00);
        expect($parsed[0]['original_currency'])->toBe('EUR');
    } finally {
        unlink($file);
    }
});

it('parses mercury csv correctly', function () {
    $service = app(TransactionImportService::class);

    $csvContent = <<<'CSV'
Date,Description,Amount,Balance,Type
01/17/2025,Payment received,1500.00,7500.00,CREDIT
01/15/2025,Software subscription,-29.99,6000.00,DEBIT
CSV;

    $file = tempnam(sys_get_temp_dir(), 'test_csv_');
    file_put_contents($file, $csvContent);

    try {
        $parsed = $service->parseCSV($file, 'mercury');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['amount'])->toBe(1500.00);
    } finally {
        unlink($file);
    }
});

it('parses bancolombia csv correctly', function () {
    $service = app(TransactionImportService::class);

    $csvContent = <<<'CSV'
Fecha,Descripción,Débito,Crédito,Saldo
17/01/2025,Depósito salario,0.00,2000000.00,5000000.00
15/01/2025,Retiro ATM,150000.00,0.00,3000000.00
CSV;

    $file = tempnam(sys_get_temp_dir(), 'test_csv_');
    file_put_contents($file, $csvContent);

    try {
        $parsed = $service->parseCSV($file, 'bancolombia');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['amount'])->toBe(2000000.00);
        expect($parsed[0]['original_currency'])->toBe('COP');
        expect($parsed[1]['amount'])->toBe(-150000.00);
    } finally {
        unlink($file);
    }
});

it('parses santander pdf correctly', function () {
    $service = app(TransactionImportService::class);

    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $account = Account::factory()->create([
        'name' => 'Test Account',
        'currency_id' => $currency->id,
        'entity_id' => $entity->id,
    ]);

    $pdfContent = <<<'PDF'
2025-01-17 Payment received 1500.00
2025-01-16 Bank fee -5.00
PDF;

    $file = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($file, $pdfContent);

    try {
        $parsed = $service->parsePDF($file, $account->id, 'santander');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['amount'])->toBe(1500.00);
        expect($parsed[1]['amount'])->toBe(-5.00);
        expect($parsed[0]['original_currency'])->toBe('EUR');
    } finally {
        unlink($file);
    }
});

it('parses mercury pdf correctly', function () {
    $service = app(TransactionImportService::class);

    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $currency = Currency::factory()->create(['code' => 'USD']);
    $account = Account::factory()->create([
        'name' => 'Test Account',
        'currency_id' => $currency->id,
        'entity_id' => $entity->id,
    ]);

    $pdfContent = <<<'PDF'
2025-01-17 Payment received 1500.00
2025-01-16 Software subscription -29.99
PDF;

    $file = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($file, $pdfContent);

    try {
        $parsed = $service->parsePDF($file, $account->id, 'mercury');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['amount'])->toBe(1500.00);
        expect($parsed[1]['amount'])->toBe(-29.99);
        expect($parsed[0]['original_currency'])->toBe('USD');
    } finally {
        unlink($file);
    }
});

it('parses bancolombia pdf correctly', function () {
    $service = app(TransactionImportService::class);

    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $currency = Currency::factory()->create(['code' => 'COP']);
    $account = Account::factory()->create([
        'name' => 'Test Account',
        'currency_id' => $currency->id,
        'entity_id' => $entity->id,
    ]);

    $pdfContent = <<<'PDF'
2025-01-17 Deposit 2000000.00
2025-01-16 ATM Withdrawal -150000.00
PDF;

    $file = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($file, $pdfContent);

    try {
        $parsed = $service->parsePDF($file, $account->id, 'bancolombia');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['amount'])->toBe(2000000.00);
        expect($parsed[1]['amount'])->toBe(-150000.00);
        expect($parsed[0]['original_currency'])->toBe('COP');
    } finally {
        unlink($file);
    }
});

it('throws error for unknown pdf parser', function () {
    $service = app(TransactionImportService::class);

    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $file = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($file, '2025-01-17 Payment received 1500.00');

    try {
        $this->expectException(\InvalidArgumentException::class);
        $service->parsePDF($file, $account->id, 'unknown');
    } finally {
        unlink($file);
    }
});

it('detects duplicate transactions correctly', function () {
    $service = app(TransactionImportService::class);

    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $account = Account::factory()->create([
        'name' => 'Test Account',
        'currency_id' => $currency->id,
        'entity_id' => $entity->id,
    ]);

    // Create an existing transaction
    $account->transactions()->create([
        'transaction_date' => '2025-01-17',
        'description' => 'Existing transaction',
        'original_amount' => 100.00,
        'original_currency_id' => $currency->id,
        'converted_amount' => 100.00,
        'converted_currency_id' => $currency->id,
        'fx_rate' => 1.0,
        'fx_source' => 'manual',
        'type' => 'income',
    ]);

    $imported = [
        [
            'date' => '2025-01-17',
            'description' => 'Existing transaction',
            'amount' => 100.00,
            'original_currency' => 'EUR',
            'counterparty' => null,
            'tags' => [],
        ],
        [
            'date' => '2025-01-16',
            'description' => 'New transaction',
            'amount' => 50.00,
            'original_currency' => 'EUR',
            'counterparty' => null,
            'tags' => [],
        ],
    ];

    $result = $service->matchTransactions($imported, $account);

    expect($result['total'])->toBe(2);
    expect($result['duplicates'])->toBe(1);
    expect($result['new'])->toBe(1);
});

it('throws error for unknown parser', function () {
    $service = app(TransactionImportService::class);

    $this->expectException(\InvalidArgumentException::class);
    $service->parseCSV('/tmp/test.csv', 'unknown_bank');
});

it('returns available parsers', function () {
    $service = app(TransactionImportService::class);
    $parsers = $service->getAvailableParsers();

    expect($parsers)->toHaveKeys(['santander', 'mercury', 'bancolombia']);
});
