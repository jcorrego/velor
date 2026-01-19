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

    <div class="space-y-6">
        <div class="flex flex-wrap gap-2 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:button
                size="sm"
                :variant="$activeTab === 'accounts' ? 'primary' : 'ghost'"
                wire:click="$set('activeTab', 'accounts')"
            >
                {{ __('Accounts') }}
            </flux:button>
            <flux:button
                size="sm"
                :variant="$activeTab === 'transactions' ? 'primary' : 'ghost'"
                wire:click="$set('activeTab', 'transactions')"
            >
                {{ __('Transactions') }}
            </flux:button>
            <flux:button
                size="sm"
                :variant="$activeTab === 'assets' ? 'primary' : 'ghost'"
                wire:click="$set('activeTab', 'assets')"
            >
                {{ __('Assets') }}
            </flux:button>
            <flux:button
                size="sm"
                :variant="$activeTab === 'categories' ? 'primary' : 'ghost'"
                wire:click="$set('activeTab', 'categories')"
            >
                {{ __('Categories') }}
            </flux:button>
            <flux:button
                size="sm"
                :variant="$activeTab === 'mappings' ? 'primary' : 'ghost'"
                wire:click="$set('activeTab', 'mappings')"
            >
                {{ __('Mappings') }}
            </flux:button>
        </div>

        @if ($activeTab === 'accounts')
            <livewire:finance.account-management />
        @elseif ($activeTab === 'transactions')
            <livewire:finance.transaction-list />
        @elseif ($activeTab === 'assets')
            <livewire:finance.asset-management />
        @elseif ($activeTab === 'categories')
            <livewire:finance.category-management />
        @elseif ($activeTab === 'mappings')
            <livewire:finance.category-mapping />
        @endif
    </div>
</div>
