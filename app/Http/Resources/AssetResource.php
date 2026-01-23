<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'entity_id' => $this->entity_id,
            'address_id' => $this->address_id,
            'ownership_structure' => $this->ownership_structure,
            'acquisition_date' => $this->acquisition_date?->format('Y-m-d'),
            'acquisition_cost' => $this->acquisition_cost ? number_format($this->acquisition_cost, 2, '.', '') : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'entity' => $this->whenLoaded('entity'),
            'address' => $this->whenLoaded('address'),
        ];
    }
}
