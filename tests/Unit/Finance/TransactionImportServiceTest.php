<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\User;
use App\Services\Finance\Parsers\PdfTextExtractor;
use App\Services\Finance\TransactionImportService;

it('throws when the PDF file is empty', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $service = app(TransactionImportService::class);

    $filePath = tempnam(sys_get_temp_dir(), 'pdf-test-');
    file_put_contents($filePath, 'placeholder');

    app()->instance(PdfTextExtractor::class, new class extends PdfTextExtractor
    {
        public function extract(string $filePath): string
        {
            return '';
        }
    });

    try {
        expect(fn () => $service->parsePDF($filePath, $account->id, 'santander'))
            ->toThrow(RuntimeException::class);
    } finally {
        unlink($filePath);
        app()->forgetInstance(PdfTextExtractor::class);
    }
});

it('throws when the PDF file cannot be read', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $service = app(TransactionImportService::class);

    expect(fn () => $service->parsePDF('/tmp/missing.pdf', $account->id, 'santander'))
        ->toThrow(InvalidArgumentException::class);
});
