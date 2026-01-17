<?php

use App\Enums\Finance\TransactionType;
use App\Finance\Services\FxRateService as FinanceFxRateService;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\Finance\FxRateService as TransactionFxRateService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $filterAccountId = '';
    public $filterCategoryId = '';
    public $filterType = '';
    public $search = '';
    public $showFxOverrideModal = false;
    public $fxOverrideTransactionId = null;
    public $fxOverrideRate = '';
    public $fxOverrideReason = '';
    public $fxOverrideDescription = '';
    public $showTransactionForm = false;
    public $editingTransactionId = null;
    public $transactionDate = '';
    public $transactionAccountId = '';
    public $transactionType = '';
    public $transactionAmount = '';
    public $transactionCurrencyId = '';
    public $transactionCategoryId = '';
    public $transactionCounterpartyName = '';
    public $transactionDescription = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterAccountId()
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryId()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatedTransactionAccountId(): void
    {
        if (! $this->transactionAccountId || $this->transactionCurrencyId) {
            return;
        }

        $account = Account::query()->find($this->transactionAccountId);
        if (! $account) {
            return;
        }

        $this->transactionCurrencyId = (string) $account->currency_id;
    }

    public function openTransactionForm(): void
    {
        $this->resetTransactionForm();
        $this->showTransactionForm = true;
        $this->dispatch('modal-show', name: 'manual-transaction');
    }

    public function editTransaction(int $id): void
    {
        $transaction = Transaction::query()->with('account')->findOrFail($id);

        if ($transaction->account->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->editingTransactionId = $transaction->id;
        $this->transactionDate = $transaction->transaction_date->format('Y-m-d');
        $this->transactionAccountId = (string) $transaction->account_id;
        $this->transactionType = $transaction->type->value;
        $this->transactionAmount = (string) $transaction->original_amount;
        $this->transactionCurrencyId = (string) $transaction->original_currency_id;
        $this->transactionCategoryId = $transaction->category_id ? (string) $transaction->category_id : '';
        $this->transactionCounterpartyName = $transaction->counterparty_name ?? '';
        $this->transactionDescription = $transaction->description ?? '';
        $this->showTransactionForm = true;
        $this->dispatch('modal-show', name: 'manual-transaction');
    }

    public function closeTransactionForm(): void
    {
        $this->resetTransactionForm();
        $this->dispatch('modal-close', name: 'manual-transaction');
    }

    public function saveTransaction(): void
    {
        $validated = $this->validate([
            'transactionDate' => ['required', 'date'],
            'transactionAccountId' => ['required', 'integer', 'exists:accounts,id'],
            'transactionType' => ['required', 'string', Rule::in(array_map(fn ($case) => $case->value, TransactionType::cases()))],
            'transactionAmount' => ['required', 'decimal:0,2', 'not_in:0'],
            'transactionCurrencyId' => ['required', 'integer', 'exists:currencies,id'],
            'transactionCategoryId' => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'transactionCounterpartyName' => ['nullable', 'string', 'max:255'],
            'transactionDescription' => ['nullable', 'string', 'max:1000'],
        ], [
            'transactionDate.required' => 'The transaction date is required.',
            'transactionDate.date' => 'The transaction date must be a valid date.',
            'transactionAccountId.required' => 'The account is required.',
            'transactionAccountId.exists' => 'The selected account does not exist.',
            'transactionType.required' => 'The transaction type is required.',
            'transactionType.in' => 'The selected transaction type is invalid.',
            'transactionAmount.required' => 'The amount is required.',
            'transactionAmount.decimal' => 'The amount must be a valid decimal with up to 2 decimal places.',
            'transactionAmount.not_in' => 'The amount must not be zero.',
            'transactionCurrencyId.required' => 'The currency is required.',
            'transactionCurrencyId.exists' => 'The selected currency does not exist.',
            'transactionCategoryId.exists' => 'The selected category does not exist.',
            'transactionCounterpartyName.string' => 'The counterparty name must be a string.',
            'transactionCounterpartyName.max' => 'The counterparty name must not exceed 255 characters.',
            'transactionDescription.string' => 'The description must be a string.',
            'transactionDescription.max' => 'The description must not exceed 1000 characters.',
        ]);

        $account = Account::query()->with('entity')->findOrFail($validated['transactionAccountId']);

        if ($account->entity->user_id !== auth()->id()) {
            abort(403);
        }

        if ($validated['transactionCategoryId']) {
            $category = TransactionCategory::query()->with('entity')->findOrFail($validated['transactionCategoryId']);

            if ($category->entity->user_id !== auth()->id()) {
                abort(403);
            }
        }

        $originalCurrency = Currency::query()->findOrFail($validated['transactionCurrencyId']);
        $baseCurrency = Currency::query()->where('code', 'EUR')->first() ?? $originalCurrency;
        $transactionDate = Carbon::parse($validated['transactionDate']);

        $fxRate = app(FinanceFxRateService::class)->getRate($originalCurrency, $baseCurrency, $transactionDate);
        $convertedAmount = (float) $validated['transactionAmount'] * $fxRate;

        $payload = [
            'transaction_date' => $validated['transactionDate'],
            'account_id' => $validated['transactionAccountId'],
            'type' => $validated['transactionType'],
            'original_amount' => $validated['transactionAmount'],
            'original_currency_id' => $originalCurrency->id,
            'converted_amount' => $convertedAmount,
            'converted_currency_id' => $baseCurrency->id,
            'fx_rate' => $fxRate,
            'fx_source' => 'ecb',
            'category_id' => $validated['transactionCategoryId'] ?: null,
            'counterparty_name' => $validated['transactionCounterpartyName'] ?: null,
            'description' => $validated['transactionDescription'] ?: null,
        ];

        if ($this->editingTransactionId) {
            $transaction = Transaction::query()->with('account.entity')->findOrFail($this->editingTransactionId);

            if ($transaction->account->entity->user_id !== auth()->id()) {
                abort(403);
            }

            $transaction->update($payload);
            $message = 'Transaction updated successfully.';
        } else {
            $payload['import_source'] = 'manual';
            Transaction::create($payload);
            $message = 'Transaction created successfully.';
        }

        $this->resetTransactionForm();
        $this->dispatch('modal-close', name: 'manual-transaction');
        session()->flash('message', $message);
    }

    private function resetTransactionForm(): void
    {
        $this->reset([
            'showTransactionForm',
            'editingTransactionId',
            'transactionDate',
            'transactionAccountId',
            'transactionType',
            'transactionAmount',
            'transactionCurrencyId',
            'transactionCategoryId',
            'transactionCounterpartyName',
            'transactionDescription',
        ]);
        $this->resetValidation();
    }

    public function reconcile($id)
    {
        $transaction = Transaction::findOrFail($id);
        
        // Verify ownership
        if ($transaction->account->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $transaction->update(['reconciled_at' => now()]);
        
        session()->flash('message', 'Transaction marked as reconciled.');
    }

    public function delete($id)
    {
        $transaction = Transaction::findOrFail($id);
        
        // Verify ownership
        if ($transaction->account->entity->user_id !== auth()->id()) {
            abort(403);
        }
        
        $transaction->delete();
        
        session()->flash('message', 'Transaction deleted successfully.');
    }

    public function openFxOverride($id)
    {
        $transaction = Transaction::findOrFail($id);

        if ($transaction->account->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->fxOverrideTransactionId = $transaction->id;
        $this->fxOverrideRate = (string) $transaction->fx_rate;
        $this->fxOverrideReason = '';
        $this->fxOverrideDescription = $transaction->description ?? 'Transaction #'.$transaction->id;
        $this->showFxOverrideModal = true;
        $this->dispatch('modal-show', name: 'fx-rate-override');
    }

    public function closeFxOverride()
    {
        $this->reset(['showFxOverrideModal', 'fxOverrideTransactionId', 'fxOverrideRate', 'fxOverrideReason', 'fxOverrideDescription']);
        $this->resetValidation();
        $this->dispatch('modal-close', name: 'fx-rate-override');
    }

    public function saveFxOverride()
    {
        $this->validate([
            'fxOverrideTransactionId' => ['required', 'integer', 'exists:transactions,id'],
            'fxOverrideRate' => ['required', 'numeric', 'min:0.000001'],
            'fxOverrideReason' => ['required', 'string', 'max:255'],
        ]);

        $transaction = Transaction::findOrFail($this->fxOverrideTransactionId);

        if ($transaction->account->entity->user_id !== auth()->id()) {
            abort(403);
        }

        app(TransactionFxRateService::class)->overrideRateForTransaction(
            $transaction->id,
            (float) $this->fxOverrideRate,
            $this->fxOverrideReason
        );

        $this->closeFxOverride();
        session()->flash('message', 'FX rate override saved.');
    }

    public function with()
    {
        $query = Transaction::query()
            ->whereHas('account.entity', fn($q) => $q->where('user_id', auth()->id()))
            ->with(['account', 'category', 'originalCurrency', 'convertedCurrency']);

        if ($this->filterAccountId) {
            $query->where('account_id', $this->filterAccountId);
        }

        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhere('counterparty_name', 'like', "%{$this->search}%");
            });
        }

        $accounts = Account::query()
            ->whereHas('entity', fn($q) => $q->where('user_id', auth()->id()))
            ->get();

        $categories = TransactionCategory::query()
            ->whereHas('entity', fn($q) => $q->where('user_id', auth()->id()))
            ->get();

        $currencies = Currency::query()
            ->where('is_active', true)
            ->get();

        return [
            'transactions' => $query->latest('transaction_date')->paginate(20),
            'accounts' => $accounts,
            'categories' => $categories,
            'currencies' => $currencies,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ __('Transactions') }}</flux:heading>
            <flux:subheading>{{ __('Review and reconcile imported transactions.') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button size="sm" variant="ghost" wire:click="openTransactionForm">
                {{ __('Add Transaction') }}
            </flux:button>
            <flux:button size="sm" variant="primary" href="{{ route('finance.import.index') }}">
                {{ __('Import Transactions') }}
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-4">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search transactions...') }}" 
                type="text"
            />

            <flux:select wire:model.live="filterAccountId" placeholder="{{ __('All Accounts') }}">
                <option value="">{{ __('All Accounts') }}</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterCategoryId" placeholder="{{ __('All Categories') }}">
                <option value="">{{ __('All Categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterType" placeholder="{{ __('All Types') }}">
                <option value="">{{ __('All Types') }}</option>
                @foreach(TransactionType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/20">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
        </div>
    @endif

    <!-- Transactions Table -->
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Amount</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $transaction->transaction_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">{{ $transaction->description ?? '—' }}</div>
                                @if($transaction->counterparty_name)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $transaction->counterparty_name }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $transaction->account->name }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $transaction->category->name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <flux:badge 
                                    size="sm" 
                                    :color="$transaction->type === \App\Enums\Finance\TransactionType::Income ? 'green' : ($transaction->type === \App\Enums\Finance\TransactionType::Expense ? 'red' : 'zinc')"
                                >
                                    {{ $transaction->type->label() }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $transaction->originalCurrency->symbol }}{{ number_format($transaction->original_amount, 2) }}
                                </div>
                                @if($transaction->converted_currency_id !== $transaction->original_currency_id)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        ≈ {{ $transaction->convertedCurrency->symbol }}{{ number_format($transaction->converted_amount, 2) }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                @if($transaction->reconciled_at)
                                    <flux:badge size="sm" color="green">Reconciled</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Pending</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    @if(!$transaction->reconciled_at)
                                        <flux:button 
                                            wire:click="reconcile({{ $transaction->id }})" 
                                            size="xs" 
                                            variant="ghost"
                                        >
                                            Reconcile
                                        </flux:button>
                                    @endif
                                    <flux:button
                                        wire:click="editTransaction({{ $transaction->id }})"
                                        size="xs"
                                        variant="ghost"
                                    >
                                        Edit
                                    </flux:button>
                                    <flux:button
                                        wire:click="openFxOverride({{ $transaction->id }})"
                                        size="xs"
                                        variant="ghost"
                                    >
                                        Override FX Rate
                                    </flux:button>
                                    <flux:button 
                                        wire:click="delete({{ $transaction->id }})" 
                                        wire:confirm="Are you sure you want to delete this transaction?"
                                        size="xs" 
                                        variant="danger"
                                    >
                                        Delete
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No transactions found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <flux:modal name="fx-rate-override" focusable class="max-w-lg">
        <form wire:submit="saveFxOverride" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Override FX Rate') }}</flux:heading>
                <flux:subheading>
                    {{ __('Update the FX rate used for this transaction.') }}
                </flux:subheading>
                <div class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $fxOverrideDescription }}
                </div>
            </div>

            <flux:input
                wire:model="fxOverrideRate"
                label="{{ __('FX Rate') }}"
                type="number"
                step="0.000001"
                min="0.000001"
            />

            <flux:input
                wire:model="fxOverrideReason"
                label="{{ __('Reason') }}"
                type="text"
                placeholder="{{ __('Bank conversion rate') }}"
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" wire:click="closeFxOverride">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save Override') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="manual-transaction" focusable class="max-w-2xl">
        <form wire:submit="saveTransaction" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingTransactionId ? __('Edit Transaction') : __('Add Transaction') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Record a transaction with the required details.') }}
                </flux:subheading>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input
                    wire:model="transactionDate"
                    label="{{ __('Date') }}"
                    type="date"
                />

                <flux:select wire:model="transactionAccountId" label="{{ __('Account') }}" placeholder="{{ __('Select account') }}">
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="transactionType" label="{{ __('Type') }}" placeholder="{{ __('Select type') }}">
                    @foreach(TransactionType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="transactionAmount"
                    label="{{ __('Amount') }}"
                    type="number"
                    step="0.01"
                />

                <flux:select wire:model="transactionCurrencyId" label="{{ __('Currency') }}" placeholder="{{ __('Select currency') }}">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="transactionCategoryId" label="{{ __('Category (Optional)') }}" placeholder="{{ __('Select category') }}">
                    <option value="">{{ __('No category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input
                wire:model="transactionCounterpartyName"
                label="{{ __('Counterparty (Optional)') }}"
                type="text"
            />

            <flux:textarea
                wire:model="transactionDescription"
                label="{{ __('Description (Optional)') }}"
                rows="3"
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" wire:click="closeTransactionForm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingTransactionId ? __('Update Transaction') : __('Create Transaction') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
