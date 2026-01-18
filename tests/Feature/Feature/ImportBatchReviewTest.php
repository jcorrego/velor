<?php

use App\Enums\Finance\ImportBatchStatus;
use App\Livewire\Finance\ImportReviewQueue;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\ImportBatch;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $entity = Entity::factory()->create();
    $currency = Currency::factory()->create();
    $this->account = Account::factory()
        ->for($entity)
        ->for($currency)
        ->create();
});

test('import review queue displays pending batches', function () {
    $batch = ImportBatch::factory()
        ->for($this->account)
        ->create([
            'status' => ImportBatchStatus::Pending,
            'transaction_count' => 5,
        ]);

    Livewire::test(ImportReviewQueue::class)
        ->assertSee($this->account->name)
        ->assertSee('5')
        ->assertSee('Pending Review');
});

test('user can approve a pending batch', function () {
    $batch = ImportBatch::factory()
        ->for($this->account)
        ->create([
            'status' => ImportBatchStatus::Pending,
        ]);

    Livewire::test(ImportReviewQueue::class)
        ->call('approveBatch', $batch->id)
        ->assertDispatched('batch-approved');

    $batch->refresh();

    expect($batch->status)->toBe(ImportBatchStatus::Applied);
    expect($batch->approved_by)->toBe($this->user->id);
    expect($batch->approved_at)->not->toBeNull();
});

test('user cannot approve a non-pending batch', function () {
    $batch = ImportBatch::factory()
        ->for($this->account)
        ->create([
            'status' => ImportBatchStatus::Applied,
        ]);

    Livewire::test(ImportReviewQueue::class)
        ->call('approveBatch', $batch->id)
        ->assertHasErrors('batch');
});

test('user can reject a pending batch with reason', function () {
    $batch = ImportBatch::factory()
        ->for($this->account)
        ->create([
            'status' => ImportBatchStatus::Pending,
        ]);

    $reason = 'Duplicate entries detected';

    Livewire::test(ImportReviewQueue::class)
        ->set('rejectionReason', $reason)
        ->call('rejectBatch', $batch->id)
        ->assertDispatched('batch-rejected');

    $batch->refresh();

    expect($batch->status)->toBe(ImportBatchStatus::Rejected);
    expect($batch->rejection_reason)->toBe($reason);
});

test('user cannot reject batch without providing reason', function () {
    $batch = ImportBatch::factory()
        ->for($this->account)
        ->create([
            'status' => ImportBatchStatus::Pending,
        ]);

    Livewire::test(ImportReviewQueue::class)
        ->call('rejectBatch', $batch->id)
        ->assertHasErrors('rejectionReason');

    $batch->refresh();
    expect($batch->status)->toBe(ImportBatchStatus::Pending);
});

test('user cannot reject a non-pending batch', function () {
    $batch = ImportBatch::factory()
        ->for($this->account)
        ->create([
            'status' => ImportBatchStatus::Applied,
        ]);

    Livewire::test(ImportReviewQueue::class)
        ->set('rejectionReason', 'Some reason')
        ->call('rejectBatch', $batch->id)
        ->assertHasErrors('batch');
});

test('batch can be selected for review details', function () {
    $batch = ImportBatch::factory()
        ->for($this->account)
        ->create([
            'status' => ImportBatchStatus::Pending,
            'transaction_count' => 10,
        ]);

    Livewire::test(ImportReviewQueue::class)
        ->call('selectBatch', $batch->id)
        ->assertSet('selectedBatchId', $batch->id)
        ->assertSee('Review Batch');
});

test('rejected batch shows rejection reason', function () {
    $reason = 'Invalid amounts detected';
    $batch = ImportBatch::factory()
        ->for($this->account)
        ->create([
            'status' => ImportBatchStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

    Livewire::test(ImportReviewQueue::class)
        ->call('selectBatch', $batch->id)
        ->assertSee('This batch has been rejected')
        ->assertSee($reason);
});
