<?php

use App\Models\Account;
use App\Models\Document;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('uploads a document with tags and links', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $entity = Entity::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $account = Account::factory()->create([
        'entity_id' => $entity->id,
    ]);
    $transaction = Transaction::factory()->forAccount($account)->create();
    $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

    $this->actingAs($user);

    Livewire::test('management.documents')
        ->set('file', $file)
        ->set('title', 'Lease agreement')
        ->set('jurisdiction_id', $jurisdiction->id)
        ->set('tax_year_id', $taxYear->id)
        ->set('tagInput', 'legal, lease')
        ->set('entityIds', [$entity->id])
        ->set('transactionIds', [$transaction->id])
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::query()->first();

    expect($document)->not->toBeNull()
        ->and($document->title)->toBe('Lease agreement')
        ->and($document->jurisdiction_id)->toBe($jurisdiction->id)
        ->and($document->tax_year_id)->toBe($taxYear->id);

    Storage::disk('local')->assertExists($document->stored_path);

    expect($document->tags->pluck('name')->all())
        ->toContain('legal')
        ->toContain('lease');

    expect($document->entities()->where('entities.id', $entity->id)->exists())->toBeTrue();
    expect($document->transactions()->where('transactions.id', $transaction->id)->exists())->toBeTrue();
});
