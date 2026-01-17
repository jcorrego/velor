<x-layouts::app :title="__('Import Transactions')">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="mb-6">
            <flux:heading size="xl">Import Transactions</flux:heading>
            <flux:text class="mt-2">
                Select an account to import transactions into.
            </flux:text>
        </div>

        @if ($accounts->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-6 text-sm text-zinc-600 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                No accounts found. Create an account first, then return here to import transactions.
            </div>
            <div class="mt-6">
                <flux:button variant="primary" href="{{ route('finance') }}">
                    Go to Finance
                </flux:button>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($accounts as $account)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $account->name }}
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $account->entity->name }}
                            </div>
                        </div>
                        <flux:button size="sm" variant="primary" href="{{ route('finance.import', $account) }}">
                            Import
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-8">
            <flux:button variant="ghost" href="{{ route('finance') }}">
                ← Back to Finance
            </flux:button>
        </div>
    </div>
</x-layouts::app>
