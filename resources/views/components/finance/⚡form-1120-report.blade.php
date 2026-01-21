<?php

use App\Finance\Services\UsTaxReportingService;
use Livewire\Component;

new class extends Component
{
    public $filingId = '';

    public function mount(): void
    {
        $firstFiling = \App\Models\Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', '1120'))
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
            ->whereHas('filingType', fn ($query) => $query->where('code', '1120'))
            ->with(['taxYear', 'filingType'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filing = null;
        $summary = ['line_items' => [], 'total' => 0];

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

            $summary = $service->getForm1120Summary($user, $year);
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
        <flux:heading size="lg">{{ __('Form 1120 Summary') }}</flux:heading>
        <flux:subheading>{{ __('Corporate income and deductions grouped by line item.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:select wire:model.live="filingId" label="{{ __('Form 1120 Filing') }}" placeholder="{{ __('Select a filing') }}">
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
            {{ __('No Form 1120 filings found. Create a Form 1120 filing in your tax year filings to get started.') }}
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    ${{ number_format($summary['total'] ?? 0, 2) }} USD
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Year') }}: {{ $filing->taxYear->year }}
                </div>
            </div>
        </div>

        @if (! empty($summary['line_items']))
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Line Items') }}</div>
                <div class="mt-4 grid gap-2 md:grid-cols-2">
                    @foreach($summary['line_items'] as $lineItem => $amount)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $lineItem }}</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                ${{ number_format($amount, 2) }} USD
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                {{ __('No mapped transactions found for this filing year.') }}
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <strong>{{ __('Note') }}:</strong> {{ __('All amounts are shown in USD and grouped by Form 1120 line item mapping.') }}
        </div>
    @endif
</div>