<x-layouts::app :title="__('Categories')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Categories') }}</flux:heading>
            <flux:subheading>{{ __('Organize transactions for tax reporting.') }}</flux:subheading>
        </div>

        <livewire:finance.category-management />
    </div>
</x-layouts::app>
