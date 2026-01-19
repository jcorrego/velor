<?php

use App\Finance\Services\UsTaxReportingService;
use Livewire\Component;

new class extends Component
{
    public $filingId = '';

    public function mount(): void
    {
        // Auto-select first Form 5472 filing if available
        $firstFiling = \App\Models\Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', '5472'))
            ->with('taxYear')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($firstFiling) {
            $this->filingId = $firstFiling->id;
        }
    }

    public function with(): array
    {
        $filings = \App\Models\Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', '5472'))
            ->with(['taxYear', 'filingType'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filing = null;
        $summary = ['contributions' => 0, 'draws' => 0, 'related_party_totals' => 0];

        if ($this->filingId) {
            $filing = \App\Models\Filing::query()
                ->where('user_id', auth()->id())
                ->with(['taxYear', 'filingType'])
                ->find($this->filingId);
        }

        if ($filing) {
            $service = app(UsTaxReportingService::class);
            $user = auth()->user();
            $year = $filing->taxYear->year;

            $summary = $service->getOwnerFlowSummary($user, $year);
        }

        return [
            'filings' => $filings,
            'filing' => $filing,
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

    <div class="grid gap-4 md:grid-cols-3">
        <flux:select wire:model.live="filingId" label="{{ __('Form 5472 Filing') }}" placeholder="{{ __('Select a filing') }}">
            <option value="">{{ __('Select a filing') }}</option>
            @foreach($filings as $filingOption)
                <option value="{{ $filingOption->id }}">
                    {{ $filingOption->taxYear->year }} - {{ ucfirst(str_replace('_', ' ', $filingOption->status->value)) }}
                </option>
            @endforeach
        </flux:select>
    </div>

    @if (! $filing)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            {{ __('No Form 5472 filings found. Create a Form 5472 filing in your tax year filings to get started.') }}
        </div>
    @else
        <!-- Summary Totals -->
        <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Owner Contributions') }}</div>
            <div class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                ${{ number_format($summary['contributions'] ?? 0, 2) }} USD
            </div>
            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Capital invested') }}
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Owner Draws') }}</div>
            <div class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                ${{ number_format($summary['draws'] ?? 0, 2) }} USD
            </div>
            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Capital withdrawn') }}
            </div>
        </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Related-Party Transactions') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    ${{ number_format($summary['related_party_totals'] ?? 0, 2) }} USD
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Status') }}: {{ ucfirst(str_replace('_', ' ', $filing->status->value)) }}
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <strong>{{ __('Note') }}:</strong> {{ __('This summary is for Form 5472 reporting. Related-party totals include all transactions between the entity and owners.') }}
        </div>
    @endif
</div>
