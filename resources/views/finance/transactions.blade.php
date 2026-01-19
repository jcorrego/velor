<x-layouts::app :title="__('Transactions')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Transactions') }}</flux:heading>
            <flux:subheading>{{ __('Review, reconcile, and import transactions.') }}</flux:subheading>
        </div>

        <livewire:finance.transaction-list />
    </div>
</x-layouts::app>
