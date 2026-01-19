<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->entity = Entity::factory()->create(['user_id' => $this->user->id]);
    $this->currency = Currency::factory()->create(['code' => 'EUR']);
    $this->account = Account::factory()->create([
        'name' => 'Test Account',
        'currency_id' => $this->currency->id,
        'entity_id' => $this->entity->id,
    ]);
});

it('can preview santander csv import', function () {
    Storage::fake('local');

    $csvContent = "Fecha;Movimiento;Cantidad;Saldo;Referencia\n17/01/2025;Payment received;1500,00;7500,00;REF123";
    $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

    actingAs($this->user)
        ->postJson("/api/import/preview/{$this->account->id}", [
            'file' => $file,
            'parser_type' => 'santander',
        ])
        ->assertOk()
        ->assertJsonStructure([
            'matched',
            'unmatched',
            'total',
            'duplicates',
            'new',
        ])
        ->assertJson([
            'total' => 1,
            'new' => 1,
            'duplicates' => 0,
        ]);
});

it('can preview mercury csv import', function () {
    Storage::fake('local');

    $csvContent = "Date,Description,Amount,Balance,Type\n01/17/2025,Payment received,1500.00,7500.00,CREDIT";
    $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

    actingAs($this->user)
        ->postJson("/api/import/preview/{$this->account->id}", [
            'file' => $file,
            'parser_type' => 'mercury',
        ])
        ->assertOk()
        ->assertJson([
            'total' => 1,
            'new' => 1,
            'duplicates' => 0,
        ]);
});

it('can preview bancolombia csv import', function () {
    Storage::fake('local');

    $csvContent = "Fecha,Descripcion,Debitos,Creditos,Saldo\n17/01/2025,Payment received,,1500000,7500000";
    $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

    actingAs($this->user)
        ->postJson("/api/import/preview/{$this->account->id}", [
            'file' => $file,
            'parser_type' => 'bancolombia',
        ])
        ->assertOk()
        ->assertJson([
            'total' => 1,
            'new' => 1,
            'duplicates' => 0,
        ]);
});

it('detects duplicate transactions in preview', function () {
    Storage::fake('local');

    // Create existing transaction
    $this->account->transactions()->create([
        'transaction_date' => '2025-01-17',
        'description' => 'Payment received',
        'original_amount' => 1500.00,
        'original_currency_id' => $this->currency->id,
        'converted_amount' => 1500.00,
        'converted_currency_id' => $this->currency->id,
        'fx_rate' => 1.0,
        'fx_source' => 'manual',
        'type' => 'income',
    ]);

    $csvContent = "Fecha;Movimiento;Cantidad;Saldo;Referencia\n17/01/2025;Payment received;1500,00;7500,00;REF123\n16/01/2025;New payment;500,00;6000,00;REF456";
    $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

    actingAs($this->user)
        ->postJson("/api/import/preview/{$this->account->id}", [
            'file' => $file,
            'parser_type' => 'santander',
        ])
        ->assertOk()
        ->assertJson([
            'total' => 2,
            'new' => 1,
            'duplicates' => 1,
        ]);
});

it('can confirm and create import batch', function () {
    Storage::fake('local');

    $csvContent = "Fecha;Movimiento;Cantidad;Saldo;Referencia\n17/01/2025;Payment received;1500,00;7500,00;REF123\n16/01/2025;Software subscription;-29,99;6000,00;REF456";
    $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

    actingAs($this->user)
        ->postJson("/api/import/confirm/{$this->account->id}", [
            'file' => $file,
            'parser_type' => 'santander',
        ])
        ->assertOk()
        ->assertJsonStructure([
            'batch_id',
            'transaction_count',
            'duplicates',
            'message',
        ])
        ->assertJson([
            'transaction_count' => 2,
            'duplicates' => 0,
        ]);

    // Batch should be created but transactions not yet imported
    expect(\App\Models\ImportBatch::count())->toBe(1);
    expect(Transaction::count())->toBe(0);

    $batch = \App\Models\ImportBatch::first();
    expect($batch->status->value)->toBe('pending');
    expect($batch->proposed_transactions)->toHaveCount(2);
});

it('validates file upload', function () {
    actingAs($this->user)
        ->postJson("/api/import/preview/{$this->account->id}", [
            'parser_type' => 'santander',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('validates parser type', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('transactions.csv', 100);

    actingAs($this->user)
        ->postJson("/api/import/preview/{$this->account->id}", [
            'file' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parser_type']);
});

it('validates file type', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('document.exe', 100);

    actingAs($this->user)
        ->postJson("/api/import/preview/{$this->account->id}", [
            'file' => $file,
            'parser_type' => 'santander',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('validates file size', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('large.csv', 10240); // 10MB

    actingAs($this->user)
        ->postJson("/api/import/preview/{$this->account->id}", [
            'file' => $file,
            'parser_type' => 'santander',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('returns list of available parsers', function () {
    actingAs($this->user)
        ->getJson('/api/import/parsers')
        ->assertOk()
        ->assertJsonStructure([
            'parsers' => [
                'santander',
                'mercury',
                'bancolombia',
            ],
        ]);
});
