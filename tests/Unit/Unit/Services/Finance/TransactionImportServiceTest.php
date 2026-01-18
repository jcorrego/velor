<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\User;
use App\Services\Finance\Parsers\OcrTextExtractor;
use App\Services\Finance\Parsers\PdfTextExtractor;
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
Date,Description,Amount,Balance,Type,Mercury Category
01/17/2025,Payment received,1500.00,7500.00,CREDIT,Income
01/15/2025,Software subscription,-29.99,6000.00,DEBIT,Software
CSV;

    $file = tempnam(sys_get_temp_dir(), 'test_csv_');
    file_put_contents($file, $csvContent);

    try {
        $parsed = $service->parseCSV($file, 'mercury');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['amount'])->toBe(1500.00);
        expect($parsed[0]['description'])->toBe("Payment received\nCategory: Income");
        expect($parsed[1]['description'])->toBe("Software subscription\nCategory: Software");
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

    $filePath = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($filePath, 'placeholder');

    app()->instance(PdfTextExtractor::class, new class($pdfContent) extends PdfTextExtractor
    {
        public function __construct(private string $text) {}

        public function extract(string $filePath): string
        {
            return $this->text;
        }
    });

    try {
        $parsed = $service->parsePDF($filePath, $account->id, 'santander');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['amount'])->toBe(1500.00);
        expect($parsed[1]['amount'])->toBe(-5.00);
        expect($parsed[0]['original_currency'])->toBe('EUR');
    } finally {
        unlink($filePath);
        app()->forgetInstance(PdfTextExtractor::class);
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

    $filePath = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($filePath, 'placeholder');

    app()->instance(PdfTextExtractor::class, new class($pdfContent) extends PdfTextExtractor
    {
        public function __construct(private string $text) {}

        public function extract(string $filePath): string
        {
            return $this->text;
        }
    });

    try {
        $parsed = $service->parsePDF($filePath, $account->id, 'mercury');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['amount'])->toBe(1500.00);
        expect($parsed[1]['amount'])->toBe(-29.99);
        expect($parsed[0]['original_currency'])->toBe('USD');
    } finally {
        unlink($filePath);
        app()->forgetInstance(PdfTextExtractor::class);
    }
});

it('parses mercury pdf from OCR column layout', function () {
    $service = app(TransactionImportService::class);

    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $currency = Currency::factory()->create(['code' => 'USD']);
    $account = Account::factory()->create([
        'name' => 'Test Account',
        'currency_id' => $currency->id,
        'entity_id' => $entity->id,
    ]);

    $pdfContent = 'Statement header without transactions';
    $ocrContent = <<<'PDF'
All Transactions /

Date (UTC)
Jan 01
Jan 02

Description
PayPal
DigitalOcean

Type
$ -6561
$ 4644
PDF;

    $filePath = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($filePath, 'placeholder');

    app()->instance(PdfTextExtractor::class, new class($pdfContent) extends PdfTextExtractor
    {
        public function __construct(private string $text) {}

        public function extract(string $filePath): string
        {
            return $this->text;
        }
    });

    app()->instance(OcrTextExtractor::class, new class($ocrContent) extends OcrTextExtractor
    {
        public function __construct(private string $text) {}

        public function extract(string $filePath): string
        {
            return $this->text;
        }
    });

    try {
        $parsed = $service->parsePDF($filePath, $account->id, 'mercury');

        $year = now()->year;

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe("{$year}-01-01");
        expect($parsed[0]['amount'])->toBe(-65.61);
        expect($parsed[1]['amount'])->toBe(46.44);
    } finally {
        unlink($filePath);
        app()->forgetInstance(PdfTextExtractor::class);
        app()->forgetInstance(OcrTextExtractor::class);
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
DESDE:2025/09/30 HASTA:2025/12/31
FECHA	DESCRIPCIÓN	SUCURSAL DCTO. VALOR	SALDO
1/10 PAGO DE PROV CONINSA S.A.S	2,294,492.00 3,425,444.83
2/10 PAGO AUTOM TC VISA	-78,180.00 3,291,810.90
ESTADO DE CUENTA
FECHA	DESCRIPCIÓN	SUCURSAL DCTO. VALOR	SALDO
11/11
15/11
TRANSFERENCIA CTA SUC VIRTUAL
ABONO INTERESES AHORROS
FIN ESTADO DE CUENTA
ANTIGUO COUNTRY
-125,000.00
380.53
5,567,076.99
5,567,457.52
PDF;

    $filePath = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($filePath, 'placeholder');

    app()->instance(PdfTextExtractor::class, new class($pdfContent) extends PdfTextExtractor
    {
        public function __construct(private string $text) {}

        public function extract(string $filePath): string
        {
            return $this->text;
        }
    });

    try {
        $parsed = $service->parsePDF($filePath, $account->id, 'bancolombia');

        expect($parsed)->toHaveCount(4);
        expect($parsed[0]['date'])->toBe('2025-10-01');
        expect($parsed[0]['amount'])->toBe(2294492.00);
        expect($parsed[1]['date'])->toBe('2025-10-02');
        expect($parsed[1]['amount'])->toBe(-78180.00);
        expect($parsed[2]['date'])->toBe('2025-11-11');
        expect($parsed[2]['amount'])->toBe(-125000.00);
        expect($parsed[3]['date'])->toBe('2025-11-15');
        expect($parsed[3]['amount'])->toBe(380.53);
        expect($parsed[0]['original_currency'])->toBe('COP');
    } finally {
        unlink($filePath);
        app()->forgetInstance(PdfTextExtractor::class);
    }
});

it('falls back to OCR when PDF text has no transactions', function () {
    $service = app(TransactionImportService::class);

    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $currency = Currency::factory()->create(['code' => 'USD']);
    $account = Account::factory()->create([
        'name' => 'Test Account',
        'currency_id' => $currency->id,
        'entity_id' => $entity->id,
    ]);

    $pdfContent = 'Statement header without transactions';
    $ocrContent = <<<'PDF'
2025-01-17 Payment received 1500.00
2025-01-16 Software subscription -29.99
PDF;

    $filePath = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($filePath, 'placeholder');

    app()->instance(PdfTextExtractor::class, new class($pdfContent) extends PdfTextExtractor
    {
        public function __construct(private string $text) {}

        public function extract(string $filePath): string
        {
            return $this->text;
        }
    });

    app()->instance(OcrTextExtractor::class, new class($ocrContent) extends OcrTextExtractor
    {
        public function __construct(private string $text) {}

        public function extract(string $filePath): string
        {
            return $this->text;
        }
    });

    try {
        $parsed = $service->parsePDF($filePath, $account->id, 'mercury');

        expect($parsed)->toHaveCount(2);
        expect($parsed[0]['date'])->toBe('2025-01-17');
        expect($parsed[0]['amount'])->toBe(1500.00);
    } finally {
        unlink($filePath);
        app()->forgetInstance(PdfTextExtractor::class);
        app()->forgetInstance(OcrTextExtractor::class);
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
