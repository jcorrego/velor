<?php

use App\FilingStatus;
use App\Models\Filing;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $filings = Filing::query()
            ->where('user_id', auth()->id())
            ->with(['taxYear.jurisdiction', 'filingType'])
            ->get();

        $statusCounts = collect(FilingStatus::cases())
            ->mapWithKeys(fn (FilingStatus $status) => [$status->value => $filings->where('status', $status)->count()])
            ->all();

        $openFilings = $filings->filter(fn (Filing $filing) => $filing->status !== FilingStatus::Filed);
        $now = Carbon::now();

        $dueFilings = $openFilings
            ->map(function (Filing $filing) use ($now) {
                $dueDate = $filing->due_date;

                return [
                    'id' => $filing->id,
                    'filing' => $filing,
                    'due_date' => $dueDate,
                    'due_label' => $dueDate?->format('M d, Y') ?? '—',
                    'due_sort' => $dueDate?->timestamp ?? PHP_INT_MAX,
                    'due_status' => $this->dueStatusLabel($dueDate, $now),
                    'due_color' => $this->dueStatusColor($dueDate, $now),
                ];
            })
            ->sortBy('due_sort')
            ->take(6)
            ->values();

        return [
            'openCount' => $openFilings->count(),
            'statusCounts' => $statusCounts,
            'statusLabels' => collect(FilingStatus::cases())
                ->mapWithKeys(fn (FilingStatus $status) => [$status->value => Str::headline($status->value)])
                ->all(),
            'dueFilings' => $dueFilings,
            'hasFilings' => $filings->isNotEmpty(),
        ];
    }

    private function dueStatusLabel(?CarbonInterface $dueDate, CarbonInterface $now): string
    {
        if (! $dueDate) {
            return __('No due date');
        }

        $daysUntil = $now->diffInDays($dueDate, false);

        if ($daysUntil < 0) {
            return __('Overdue');
        }

        if ($daysUntil <= 30) {
            return __('Due soon');
        }

        return __('Upcoming');
    }

    private function dueStatusColor(?CarbonInterface $dueDate, CarbonInterface $now): string
    {
        if (! $dueDate) {
            return 'zinc';
        }

        $daysUntil = $now->diffInDays($dueDate, false);

        if ($daysUntil < 0) {
            return 'red';
        }

        if ($daysUntil <= 30) {
            return 'amber';
        }

        return 'zinc';
    }
};
?>

<div class="grid gap-6 lg:grid-cols-[minmax(0,320px)_minmax(0,1fr)]">
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ __('Filing status') }}</flux:heading>
                <flux:subheading>{{ __('Open filings and status distribution.') }}</flux:subheading>
            </div>
            <flux:badge size="lg" color="zinc">{{ $openCount }}</flux:badge>
        </div>

        <div class="mt-6 grid gap-4">
            @foreach ($statusCounts as $status => $count)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                    <span>{{ $statusLabels[$status] ?? $status }}</span>
                    <flux:badge size="sm" color="zinc">{{ $count }}</flux:badge>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <flux:heading size="lg">{{ __('Upcoming due dates') }}</flux:heading>
            <flux:subheading>{{ __('Next filings that still need attention.') }}</flux:subheading>
        </div>

        @if (! $hasFilings)
            <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                {{ __('No filings yet. Add a filing to start tracking deadlines.') }}
            </div>
        @elseif ($dueFilings->isEmpty())
            <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                {{ __('All filings are marked as filed.') }}
            </div>
        @else
            <div class="mt-5 space-y-3">
                @foreach ($dueFilings as $due)
                    <div class="flex flex-col gap-2 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $due['filing']->filingType->name }} · {{ $due['filing']->taxYear->year }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $due['filing']->taxYear->jurisdiction->name ?? __('Unknown jurisdiction') }}
                                </div>
                            </div>
                            <flux:badge size="sm" color="{{ $due['due_color'] }}">
                                {{ $due['due_status'] }}
                            </flux:badge>
                        </div>
                        <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-300">
                            <span>{{ __('Status:') }} {{ $statusLabels[$due['filing']->status->value] ?? $due['filing']->status->value }}</span>
                            <span>{{ __('Due:') }} {{ $due['due_label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
