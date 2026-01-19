<x-layouts::app :title="__('Mappings')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Mappings') }}</flux:heading>
            <flux:subheading>{{ __('Map categories to tax forms and line items.') }}</flux:subheading>
        </div>

        <livewire:finance.category-mapping />
    </div>
</x-layouts::app>
