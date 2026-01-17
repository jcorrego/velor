<?php

use Livewire\Component;

new class extends Component
{
    public $activeTab = 'accounts';
};
?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Finance') }}</flux:heading>
        <flux:subheading>{{ __('Manage accounts, transactions, assets, and financial reporting.') }}</flux:subheading>
    </div>

    <flux:tabs wire:model.live="activeTab" class="space-y-6">
        <flux:tab name="accounts">{{ __('Accounts') }}</flux:tab>
        <flux:tab name="transactions">{{ __('Transactions') }}</flux:tab>
        <flux:tab name="assets">{{ __('Assets') }}</flux:tab>
        <flux:tab name="categories">{{ __('Categories') }}</flux:tab>

        <flux:tab.panel name="accounts">
            <flux:⚡account-management />
        </flux:tab.panel>

        <flux:tab.panel name="transactions">
            <flux:⚡transaction-list />
        </flux:tab.panel>

        <flux:tab.panel name="assets">
            <flux:⚡asset-management />
        </flux:tab.panel>

        <flux:tab.panel name="categories">
            <flux:⚡category-management />
        </flux:tab.panel>
    </flux:tabs>
</div>