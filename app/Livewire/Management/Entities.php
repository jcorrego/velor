<?php

namespace App\Livewire\Management;

use App\EntityType;
use App\Http\Requests\StoreEntityRequest;
use App\Http\Requests\UpdateEntityRequest;
use App\Models\Address;
use App\Models\Entity;
use App\Models\Jurisdiction;
use Illuminate\View\View;
use Livewire\Component;

class Entities extends Component
{
    public ?int $editingId = null;

    public ?int $jurisdiction_id = null;

    public string $type = '';

    public string $name = '';

    public string $ein_or_tax_id = '';

    public ?int $address_id = null;

    public string $address_country = '';

    public string $address_state = '';

    public string $address_city = '';

    public string $address_postal_code = '';

    public string $address_line_1 = '';

    public string $address_line_2 = '';

    public function mount(): void
    {
        $this->type = EntityType::Individual->value;
    }

    public function edit(int $entityId): void
    {
        $entity = Entity::query()
            ->where('user_id', auth()->id())
            ->with('jurisdiction')
            ->findOrFail($entityId);

        $this->editingId = $entity->id;
        $this->jurisdiction_id = $entity->jurisdiction_id;
        $this->address_id = $entity->address_id;
        $this->type = $entity->type->value;
        $this->name = $entity->name;
        $this->ein_or_tax_id = $entity->ein_or_tax_id ?? '';
    }

    public function save(): void
    {
        $data = $this->formData();
        $validated = validator(
            $data,
            $this->rulesForSave($data),
            $this->messagesForSave($data),
        )->validate();

        if ($this->editingId) {
            $entity = Entity::query()
                ->where('user_id', auth()->id())
                ->findOrFail($this->editingId);

            $entity->update($validated);
        } else {
            Entity::create($validated);
        }

        $this->resetForm();
        $this->dispatch('entity-saved');
    }

    public function openAddressModal(): void
    {
        $this->dispatch('modal-show', name: 'entity-address-create');
    }

    public function closeAddressModal(): void
    {
        $this->resetAddressForm();
        $this->resetValidation();
        $this->dispatch('modal-close', name: 'entity-address-create');
    }

    public function saveAddress(): void
    {
        $this->validate($this->addressRules());

        $address = Address::create([
            'user_id' => auth()->id(),
            'country' => $this->address_country,
            'state' => $this->address_state,
            'city' => $this->address_city,
            'postal_code' => $this->address_postal_code,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2 !== '' ? $this->address_line_2 : null,
        ]);

        $this->address_id = $address->id;

        $this->resetAddressForm();
        $this->resetValidation();
        $this->dispatch('modal-close', name: 'entity-address-create');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.management.entities', [
            'entities' => Entity::query()
                ->where('user_id', auth()->id())
                ->with(['jurisdiction', 'address'])
                ->orderBy('name')
                ->get(),
            'addresses' => Address::query()
                ->where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->get(),
            'jurisdictions' => Jurisdiction::query()
                ->orderBy('name')
                ->get(),
            'entityTypes' => EntityType::cases(),
        ])->layout('layouts.app', [
            'title' => __('Entities'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'user_id' => auth()->id(),
            'jurisdiction_id' => $this->jurisdiction_id,
            'address_id' => $this->address_id ?: null,
            'type' => $this->type,
            'name' => $this->name,
            'ein_or_tax_id' => $this->ein_or_tax_id !== '' ? $this->ein_or_tax_id : null,
            'entity_id' => $this->editingId,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rulesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateEntityRequest::create('/', 'PATCH', $data)
            : StoreEntityRequest::create('/', 'POST', $data);

        return $request->rules();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function messagesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateEntityRequest::create('/', 'PATCH', $data)
            : StoreEntityRequest::create('/', 'POST', $data);

        return $request->messages();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->jurisdiction_id = null;
        $this->address_id = null;
        $this->type = EntityType::Individual->value;
        $this->name = '';
        $this->ein_or_tax_id = '';
        $this->resetAddressForm();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function addressRules(): array
    {
        return [
            'address_country' => ['required', 'string', 'max:255'],
            'address_state' => ['required', 'string', 'max:255'],
            'address_city' => ['required', 'string', 'max:255'],
            'address_postal_code' => ['required', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function resetAddressForm(): void
    {
        $this->address_country = '';
        $this->address_state = '';
        $this->address_city = '';
        $this->address_postal_code = '';
        $this->address_line_1 = '';
        $this->address_line_2 = '';
    }
}
