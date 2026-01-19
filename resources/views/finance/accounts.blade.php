<x-layouts::app :title="__('Accounts')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Accounts') }}</flux:heading>
            <flux:subheading>{{ __('Manage bank accounts and payment methods.') }}</flux:subheading>
        </div>

        <livewire:finance.account-management />
    </div>
</x-layouts::app>
