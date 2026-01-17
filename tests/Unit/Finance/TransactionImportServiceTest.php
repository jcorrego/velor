<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\User;
use App\Services\Finance\TransactionImportService;

it('returns an empty set for PDF parsing until extraction is implemented', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $filePath = tempnam(sys_get_temp_dir(), 'pdf-test-');
    file_put_contents($filePath, '');

    $service = app(TransactionImportService::class);

    $result = $service->parsePDF($filePath, $account->id);

    expect($result)->toBe([]);
});

it('throws when the PDF file cannot be read', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $service = app(TransactionImportService::class);

    expect(fn () => $service->parsePDF('/tmp/missing.pdf', $account->id))
        ->toThrow(InvalidArgumentException::class);
});
