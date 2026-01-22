<x-layouts::app :title="__('Assets')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Assets') }}</flux:heading>
            <flux:subheading>{{ __('Track real estate assets.') }}</flux:subheading>
        </div>

        <livewire:finance.asset-management />
    </div>
</x-layouts::app>
