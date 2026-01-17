<?php

use App\Enums\Finance\TransactionType;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\TransactionCategory;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    public $categories;
    public $entities;
    public $jurisdictions;
    
    public $filterJurisdictionId = '';
    public $editingId = null;
    
    #[Validate('required|string|max:255')]
    public $name = '';
    
    #[Validate('required|exists:jurisdictions,id')]
    public $jurisdiction_id = '';
    
    #[Validate('nullable|exists:entities,id')]
    public $entity_id = null;
    
    #[Validate('required')]
    public $type = '';
    
    #[Validate('nullable|integer|min:0')]
    public $sort_order = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->entities = Entity::where('user_id', auth()->id())->get();
        $this->jurisdictions = Jurisdiction::all();
        
        $query = TransactionCategory::query()
            ->where(function($q) {
                $q->whereHas('entity', fn($query) => $query->where('user_id', auth()->id()))
                  ->orWhereNull('entity_id');
            })
            ->with(['entity', 'jurisdiction', 'categoryTaxMappings']);
        
        if ($this->filterJurisdictionId) {
            $query->where('jurisdiction_id', $this->filterJurisdictionId);
        }
        
        $this->categories = $query->orderBy('sort_order')->orderBy('name')->get();
    }

    public function updatedFilterJurisdictionId()
    {
        $this->loadData();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'jurisdiction_id' => $this->jurisdiction_id,
            'entity_id' => $this->entity_id,
            'type' => $this->type,
            'sort_order' => $this->sort_order ?? 0,
        ];

        if ($this->editingId) {
            $category = TransactionCategory::findOrFail($this->editingId);
            
            // Only allow editing if user owns the entity
            if ($category->entity_id && $category->entity->user_id !== auth()->id()) {
                abort(403);
            }
            
            $category->update($data);
            $message = 'Category updated successfully.';
        } else {
            TransactionCategory::create($data);
            $message = 'Category created successfully.';
        }

        $this->reset(['name', 'jurisdiction_id', 'entity_id', 'type', 'sort_order', 'editingId']);
        $this->loadData();
        
        session()->flash('message', $message);
    }

    public function edit($id)
    {
        $category = TransactionCategory::findOrFail($id);
        
        // Only allow editing if user owns the entity
        if ($category->entity_id && $category->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->jurisdiction_id = $category->jurisdiction_id;
        $this->entity_id = $category->entity_id;
        $this->type = $category->type->value;
        $this->sort_order = $category->sort_order;
    }

    public function cancel()
    {
        $this->reset(['name', 'jurisdiction_id', 'entity_id', 'type', 'sort_order', 'editingId']);
        $this->resetValidation();
    }

    public function delete($id)
    {
        $category = TransactionCategory::findOrFail($id);
        
        // Only allow deleting if user owns the entity
        if ($category->entity_id && $category->entity->user_id !== auth()->id()) {
            abort(403);
        }
        
        // Check if category has transactions
        if ($category->transactions()->exists()) {
            session()->flash('error', 'Cannot delete category with existing transactions. Please reassign or delete the transactions first.');
            return;
        }
        
        $category->delete();
        $this->loadData();
        
        session()->flash('message', 'Category deleted successfully.');
    }
};
?>

<div class="grid gap-6 lg:grid-cols-[minmax(0,400px)_minmax(0,1fr)]">
    <!-- Form Section -->
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ $editingId ? __('Edit Category') : __('Add Category') }}</flux:heading>
        <flux:subheading>{{ __('Organize transactions for tax reporting.') }}</flux:subheading>

        @if (session()->has('message'))
            <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mt-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        @endif

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:input wire:model="name" label="{{ __('Category Name') }}" type="text" />

            <flux:select wire:model="jurisdiction_id" label="{{ __('Jurisdiction') }}" placeholder="{{ __('Select jurisdiction') }}">
                @foreach($jurisdictions as $jurisdiction)
                    <option value="{{ $jurisdiction->id }}">{{ $jurisdiction->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="entity_id" label="{{ __('Entity (Optional)') }}" placeholder="{{ __('All entities') }}">
                <option value="">{{ __('-- Global Category --') }}</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="type" label="{{ __('Transaction Type') }}" placeholder="{{ __('Select type') }}">
                @foreach(TransactionType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="sort_order" label="{{ __('Sort Order') }}" type="number" />

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? __('Update Category') : __('Create Category') }}
                </flux:button>
                
                @if($editingId)
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                @endif
            </div>
        </form>
    </section>

    <!-- List Section -->
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ __('Transaction Categories') }}</flux:heading>
                <flux:subheading>{{ __('Manage your income and expense categories.') }}</flux:subheading>
            </div>
        </div>

        <!-- Filter -->
        <div class="mb-6">
            <flux:select wire:model.live="filterJurisdictionId" label="{{ __('Filter by Jurisdiction') }}" placeholder="{{ __('All jurisdictions') }}">
                <option value="">{{ __('All jurisdictions') }}</option>
                @foreach($jurisdictions as $jurisdiction)
                    <option value="{{ $jurisdiction->id }}">{{ $jurisdiction->name }}</option>
                @endforeach
            </flux:select>
        </div>

        @if($categories->isEmpty())
            <div class="mt-6 text-center">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No categories found. Create your first one!') }}</p>
            </div>
        @else
            <div class="mt-6 space-y-3">
                @foreach($categories as $category)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $category->name }}</h3>
                                <flux:badge size="sm" :color="$category->type->value === 'income' ? 'green' : ($category->type->value === 'expense' ? 'red' : 'zinc')">
                                    {{ $category->type->label() }}
                                </flux:badge>
                                @if($category->entity_id)
                                    <flux:badge size="sm" color="blue">{{ $category->entity->name }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Global</flux:badge>
                                @endif
                            </div>
                            <div class="mt-1 flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                <span>{{ $category->jurisdiction->name }}</span>
                                @if($category->sort_order > 0)
                                    <span>•</span>
                                    <span>Order: {{ $category->sort_order }}</span>
                                @endif
                                @if($category->categoryTaxMappings->isNotEmpty())
                                    <span>•</span>
                                    <span>{{ $category->categoryTaxMappings->count() }} tax mapping(s)</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="edit({{ $category->id }})" size="sm" variant="ghost">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button 
                                wire:click="delete({{ $category->id }})" 
                                wire:confirm="Are you sure you want to delete this category?"
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