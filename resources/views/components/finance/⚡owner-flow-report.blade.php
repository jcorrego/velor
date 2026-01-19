<?php

use App\Finance\Services\UsTaxReportingService;
use Livewire\Component;

new class extends Component
{
    public $year;

    public function mount(): void
    {
        $this->year = now()->year;
    }

    public function with(): array
    {
        $service = app(UsTaxReportingService::class);
        $user = auth()->user();
        $year = (int) $this->year;

        $summary = $service->getOwnerFlowSummary($user, $year);

        return [
            'summary' => $summary,
        ];
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Owner-Flow Summary (Form 5472)') }}</flux:heading>
        <flux:subheading>{{ __('Owner contributions, draws, and related-party transaction totals.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-1">
        <flux:input
            wire:model.live="year"
            label="{{ __('Tax Year') }}"
            type="number"
            min="1900"
            max="{{ now()->year + 1 }}"
        />
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Owner Contributions') }}</div>
            <div class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                ${{ number_format($summary['contributions'] ?? 0, 2) }}
            </div>
            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Capital invested') }}
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Owner Draws') }}</div>
            <div class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                ${{ number_format($summary['draws'] ?? 0, 2) }}
            </div>
            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Capital withdrawn') }}
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Related-Party Transactions') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                ${{ number_format($summary['related_party_totals'] ?? 0, 2) }}
            </div>
            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Year') }}: {{ $year }}
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
        <strong>{{ __('Note') }}:</strong> {{ __('This summary is for Form 5472 reporting. Related-party totals include all transactions between the entity and owners.') }}
    </div>
</div>
