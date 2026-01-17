<x-layouts::app :title="__('Import Transactions')">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-6">
            <flux:heading size="xl">Import Transactions</flux:heading>
            <flux:text class="mt-2">
                Account: <strong>{{ $account->name }}</strong>
            </flux:text>
        </div>

        <livewire:finance.transaction-import-form :account="$account" />

        <div class="mt-8">
            <flux:button variant="ghost" href="{{ route('finance') }}">
                ← Back to Finance
            </flux:button>
        </div>
    </div>
</x-layouts::app>
