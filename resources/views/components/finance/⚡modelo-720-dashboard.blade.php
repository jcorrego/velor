<?php

use App\Finance\Services\SpainTaxReportingService;
use Livewire\Component;

new class extends Component
{
    public $filingId = '';

    public function mount(): void
    {
        $firstFiling = \App\Models\Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', '720'))
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
            ->whereHas('filingType', fn ($query) => $query->where('code', '720'))
            ->with(['taxYear', 'filingType'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filing = null;
        $summary = [
            'threshold' => 50000,
            'total_assets' => 0,
            'categories' => [],
        ];

        if ($this->filingId) {
            $filing = \App\Models\Filing::query()
                ->where('user_id', auth()->id())
                ->with(['taxYear', 'filingType'])
                ->find($this->filingId);
        }

        if ($filing) {
            $service = app(SpainTaxReportingService::class);
            $summary = $service->getModelo720Summary(auth()->user(), $filing->taxYear->year);
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
        <flux:heading size="lg">{{ __('Modelo 720 Foreign Asset Dashboard') }}</flux:heading>
        <flux:subheading>{{ __('Foreign asset totals by category and threshold status in EUR.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:select wire:model.live="filingId" label="{{ __('Modelo 720 Filing') }}" placeholder="{{ __('Select a filing') }}">
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
            {{ __('No Modelo 720 filings found. Create a 720 filing in your tax year filings to get started.') }}
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Foreign Assets') }}</div>
                <div class="mt-2 text-2xl font-semibold text-indigo-600 dark:text-indigo-400">
                    {{ number_format($summary['total_assets'], 2) }} EUR
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Year') }}: {{ $filing->taxYear->year }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Threshold') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($summary['threshold'], 2) }} EUR
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Per category') }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</div>
                <div class="mt-2 text-2xl font-semibold {{ $summary['total_assets'] >= $summary['threshold'] ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    {{ $summary['total_assets'] >= $summary['threshold'] ? __('Above threshold') : __('Below threshold') }}
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Overall') }}
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse($summary['categories'] as $categoryName => $category)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __($categoryName) }}</div>
                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $category['status'] === 'above' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' }}">
                            {{ $category['status'] === 'above' ? __('Above threshold') : __('Below threshold') }}
                        </span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Total') }}</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($category['total'], 2) }} EUR</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ __('Threshold') }}</span>
                        <span>{{ number_format($category['threshold'], 2) }} EUR</span>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                    {{ __('No foreign asset totals available for this filing.') }}
                </div>
            @endforelse
        </div>

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <strong>{{ __('Note') }}:</strong> {{ __('Totals use year-end values and are converted to EUR.') }}
        </div>
    @endif
</div>
