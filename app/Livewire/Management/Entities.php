<?php

namespace App\Livewire\Management;

use App\EntityType;
use App\Http\Requests\StoreEntityRequest;
use App\Http\Requests\UpdateEntityRequest;
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

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.management.entities', [
            'entities' => Entity::query()
                ->where('user_id', auth()->id())
                ->with('jurisdiction')
                ->orderBy('name')
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
        $this->type = EntityType::Individual->value;
        $this->name = '';
        $this->ein_or_tax_id = '';
    }
}
