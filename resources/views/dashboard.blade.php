<x-layouts::app :title="__('Dashboard')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Track filing progress and upcoming due dates.') }}</flux:subheading>
        </div>

        <livewire:dashboard.filing-status-summary />

        <livewire:dashboard.category-totals />
    </div>
</x-layouts::app>
