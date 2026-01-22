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
            'jurisdiction_id' => $this->jurisdiction_id,
            'entity_id' => $this->entity_id,
            'ownership_structure' => $this->ownership_structure,
            'acquisition_date' => $this->acquisition_date?->format('Y-m-d'),
            'acquisition_cost' => $this->acquisition_cost ? number_format($this->acquisition_cost, 2, '.', '') : null,
            'acquisition_currency_id' => $this->acquisition_currency_id,
            'depreciation_method' => $this->depreciation_method,
            'useful_life_years' => $this->useful_life_years,
            'annual_depreciation_amount' => $this->annual_depreciation_amount ? number_format($this->annual_depreciation_amount, 2, '.', '') : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'jurisdiction' => $this->whenLoaded('jurisdiction'),
            'entity' => $this->whenLoaded('entity'),
            'currency' => $this->whenLoaded('currency'),
        ];
    }
}
