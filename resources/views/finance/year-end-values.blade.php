<x-layouts::app :title="__('Year-End Values')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Year-End Values') }}</flux:heading>
            <flux:subheading>{{ __('Capture year-end balances for accounts and assets.') }}</flux:subheading>
        </div>

        <livewire:finance.year-end-values />
    </div>
</x-layouts::app>
