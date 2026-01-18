<?php

namespace App\Livewire\Finance;

use App\Enums\Finance\ImportBatchStatus;
use App\Models\ImportBatch;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ImportReviewQueue extends Component
{
    use WithPagination;

    public ?int $selectedBatchId = null;

    public string $rejectionReason = '';

    public function mount(): void
    {
        $this->resetPage();
    }

    public function selectBatch(int $batchId): void
    {
        $this->selectedBatchId = $batchId;
        $this->rejectionReason = '';
    }

    public function approveBatch(int $batchId): void
    {
        $batch = ImportBatch::findOrFail($batchId);

        if ($batch->status !== ImportBatchStatus::Pending) {
            $this->addError('batch', 'Only pending batches can be approved.');

            return;
        }

        $batch->update([
            'status' => ImportBatchStatus::Applied,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->resetErrorBag();
        $this->resetPage();
        $this->selectedBatchId = null;
        $this->dispatch('batch-approved');
    }

    public function rejectBatch(int $batchId): void
    {
        if (empty($this->rejectionReason)) {
            $this->addError('rejectionReason', 'Please provide a reason for rejection.');

            return;
        }

        $batch = ImportBatch::findOrFail($batchId);

        if ($batch->status !== ImportBatchStatus::Pending) {
            $this->addError('batch', 'Only pending batches can be rejected.');

            return;
        }

        $batch->update([
            'status' => ImportBatchStatus::Rejected,
            'rejection_reason' => $this->rejectionReason,
        ]);

        $this->resetErrorBag();
        $this->resetPage();
        $this->selectedBatchId = null;
        $this->rejectionReason = '';
        $this->dispatch('batch-rejected');
    }

    public function render(): View
    {
        $batches = ImportBatch::query()
            ->with('account')
            ->orderByDesc('created_at')
            ->paginate(10);

        $selectedBatch = $this->selectedBatchId
            ? ImportBatch::find($this->selectedBatchId)
            : null;

        return view('livewire.finance.import-review-queue', [
            'batches' => $batches,
            'selectedBatch' => $selectedBatch,
        ])->layout('layouts.app', [
            'title' => __('Import Review Queue'),
        ]);
    }
}
