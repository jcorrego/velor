<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Import Review Queue') }}</flux:heading>
        <flux:subheading>{{ __('Review and approve transaction imports before they affect your accounts.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,380px)]">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Pending Batches') }}</flux:heading>
            <flux:subheading>{{ __('Batches awaiting your review and approval.') }}</flux:subheading>

            @if ($batches->isEmpty())
                <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('No import batches awaiting review.') }}
                </div>
            @else
                <div class="mt-6 space-y-4">
                    @foreach ($batches as $batch)
                        <div
                            wire:key="batch-{{ $batch->id }}"
                            @click="$wire.selectBatch({{ $batch->id }})"
                            class="cursor-pointer rounded-lg border transition-colors {{ $selectedBatchId === $batch->id ? 'border-blue-400 bg-blue-50 dark:border-blue-600 dark:bg-blue-900/20' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }} p-4"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-medium text-zinc-900 dark:text-white">
                                        {{ $batch->account->name }}
                                    </h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ __('Transactions:') }} {{ $batch->transaction_count }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500">
                                        {{ $batch->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <flux:badge color="yellow">{{ $batch->status->label() }}</flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $batches->links() }}
                </div>
            @endif
        </section>

        @if ($selectedBatch)
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Review Batch') }}</flux:heading>

                @if ($errors->any())
                    <div class="mt-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('There were errors with your submission') }}</h3>
                                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                    <ul class="list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <p class="text-zinc-600 dark:text-zinc-400">{{ __('Account') }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $selectedBatch->account->name }}</p>
                    </div>

                    <div>
                        <p class="text-zinc-600 dark:text-zinc-400">{{ __('Transactions') }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $selectedBatch->transaction_count }}</p>
                    </div>

                    <div>
                        <p class="text-zinc-600 dark:text-zinc-400">{{ __('Created') }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $selectedBatch->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                <!-- Transaction List -->
                @if ($selectedBatch->proposed_transactions && count($selectedBatch->proposed_transactions) > 0)
                    <div class="mt-6">
                        <flux:subheading>{{ __('Transactions to Import') }}</flux:subheading>
                        <div class="mt-3 max-h-96 space-y-2 overflow-y-auto">
                            @foreach ($selectedBatch->proposed_transactions as $transaction)
                                <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-zinc-900 dark:text-white">{{ $transaction['description'] ?? '—' }}</p>
                                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-zinc-600 dark:text-zinc-400">
                                                <span>{{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y') }}</span>
                                                @if ($transaction['counterparty'] ?? null)
                                                    <span>• {{ $transaction['counterparty'] }}</span>
                                                @endif
                                                @if ($transaction['category_name'] ?? null)
                                                    <span>• <span class="text-blue-600 dark:text-blue-400">{{ $transaction['category_name'] }}</span></span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="ml-3 text-right">
                                            <p class="font-semibold {{ $transaction['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ number_format(abs($transaction['amount']), 2) }}
                                            </p>
                                            <p class="text-zinc-500">{{ $transaction['original_currency'] ?? 'EUR' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($selectedBatch->status->value === 'pending')
                    <div class="mt-6 space-y-3">
                        <div wire:loading.remove>
                            <flux:button
                                variant="primary"
                                class="w-full"
                                wire:click="approveBatch({{ $selectedBatch->id }})"
                            >
                                {{ __('Approve') }}
                            </flux:button>

                            <div class="mt-2">
                                <flux:textarea
                                    wire:model="rejectionReason"
                                    :label="__('Rejection reason (if rejecting)')"
                                    :placeholder="__('Explain why you are rejecting this batch...')"
                                />
                            </div>

                            <flux:button
                                variant="danger"
                                class="w-full"
                                wire:click="rejectBatch({{ $selectedBatch->id }})"
                            >
                                {{ __('Reject') }}
                            </flux:button>
                        </div>

                        <div wire:loading class="flex justify-center py-4">
                            <div class="h-5 w-5 animate-spin rounded-full border-2 border-blue-400 border-t-transparent"></div>
                        </div>
                    </div>
                @else
                    <div class="mt-4 rounded-md bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        @if ($selectedBatch->status->value === 'applied')
                            {{ __('This batch has been approved and applied.') }}
                        @else
                            {{ __('This batch has been rejected.') }}
                            @if ($selectedBatch->rejection_reason)
                                <p class="mt-2 font-medium">{{ __('Reason:') }}</p>
                                <p class="mt-1">{{ $selectedBatch->rejection_reason }}</p>
                            @endif
                        @endif
                    </div>
                @endif
            </section>
        @endif
    </div>
</div>
