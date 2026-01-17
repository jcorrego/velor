<?php

use App\Enums\Finance\AccountType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    public $accounts;
    public $entities;
    public $currencies;
    
    public $editingId = null;
    
    #[Validate('required|string|max:255')]
    public $name = '';
    
    #[Validate('required')]
    public $type = '';
    
    #[Validate('required|exists:currencies,id')]
    public $currency_id = '';
    
    #[Validate('required|exists:entities,id')]
    public $entity_id = '';
    
    #[Validate('required|date')]
    public $opening_date = '';
    
    #[Validate('nullable|date')]
    public $closing_date = null;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->entities = Entity::where('user_id', auth()->id())->get();
        $this->currencies = Currency::where('is_active', true)->get();
        $this->accounts = Account::query()
            ->whereHas('entity', fn($q) => $q->where('user_id', auth()->id()))
            ->with(['entity', 'currency'])
            ->latest()
            ->get();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'currency_id' => $this->currency_id,
            'entity_id' => $this->entity_id,
            'opening_date' => $this->opening_date,
            'closing_date' => $this->closing_date,
        ];

        if ($this->editingId) {
            $account = Account::findOrFail($this->editingId);
            
            // Verify ownership
            if ($account->entity->user_id !== auth()->id()) {
                abort(403);
            }
            
            // Don't allow changing opening_date
            unset($data['opening_date']);
            
            $account->update($data);
        } else {
            Account::create($data);
        }

        $this->reset(['name', 'type', 'currency_id', 'entity_id', 'opening_date', 'closing_date', 'editingId']);
        $this->loadData();
        
        session()->flash('message', $this->editingId ? 'Account updated successfully.' : 'Account created successfully.');
    }

    public function edit($id)
    {
        $account = Account::findOrFail($id);
        
        // Verify ownership
        if ($account->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->editingId = $account->id;
        $this->name = $account->name;
        $this->type = $account->type->value;
        $this->currency_id = $account->currency_id;
        $this->entity_id = $account->entity_id;
        $this->opening_date = $account->opening_date->format('Y-m-d');
        $this->closing_date = $account->closing_date?->format('Y-m-d');
    }

    public function cancel()
    {
        $this->reset(['name', 'type', 'currency_id', 'entity_id', 'opening_date', 'closing_date', 'editingId']);
        $this->resetValidation();
    }

    public function delete($id)
    {
        $account = Account::findOrFail($id);
        
        // Verify ownership
        if ($account->entity->user_id !== auth()->id()) {
            abort(403);
        }
        
        $account->delete();
        $this->loadData();
        
        session()->flash('message', 'Account deleted successfully.');
    }
};
?>

<div class="grid gap-6 lg:grid-cols-[minmax(0,400px)_minmax(0,1fr)]">
    <!-- Form Section -->
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ $editingId ? __('Edit Account') : __('Add Account') }}</flux:heading>
        <flux:subheading>{{ __('Manage your bank accounts and payment methods.') }}</flux:subheading>

        @if (session()->has('message'))
            <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
            </div>
        @endif

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:input wire:model="name" label="{{ __('Account Name') }}" type="text" />

            <flux:select wire:model="type" label="{{ __('Account Type') }}" placeholder="{{ __('Select type') }}">
                @foreach(AccountType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="entity_id" label="{{ __('Entity') }}" placeholder="{{ __('Select entity') }}">
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="currency_id" label="{{ __('Currency') }}" placeholder="{{ __('Select currency') }}">
                @foreach($currencies as $currency)
                    <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="opening_date" label="{{ __('Opening Date') }}" type="date" :disabled="!!$editingId" />

            <flux:input wire:model="closing_date" label="{{ __('Closing Date (Optional)') }}" type="date" />

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? __('Update Account') : __('Create Account') }}
                </flux:button>
                
                @if($editingId)
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                @endif
            </div>
        </form>
    </section>

    <!-- List Section -->
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Your Accounts') }}</flux:heading>
        <flux:subheading>{{ __('View and manage all your financial accounts.') }}</flux:subheading>

        @if($accounts->isEmpty())
            <div class="mt-6 text-center">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No accounts yet. Create your first one!') }}</p>
            </div>
        @else
            <div class="mt-6 space-y-3">
                @foreach($accounts as $account)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $account->name }}</h3>
                                <flux:badge size="sm" color="zinc">{{ $account->type->label() }}</flux:badge>
                            </div>
                            <div class="mt-1 flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                <span>{{ $account->entity->name }}</span>
                                <span>•</span>
                                <span>{{ $account->currency->code }}</span>
                                <span>•</span>
                                <span>Opened: {{ $account->opening_date->format('M Y') }}</span>
                                @if($account->closing_date)
                                    <span>•</span>
                                    <flux:badge size="sm" color="red">Closed</flux:badge>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="edit({{ $account->id }})" size="sm" variant="ghost">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button 
                                wire:click="delete({{ $account->id }})" 
                                wire:confirm="Are you sure you want to delete this account?"
                                size="sm" 
                                variant="danger"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>