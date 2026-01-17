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
            'current_valuation' => $this->current_valuation ? number_format($this->current_valuation, 2, '.', '') : null,
            'valuation_amount' => $this->current_valuation ? number_format($this->current_valuation, 2, '.', '') : null,
            'valuation_date' => $this->valuation_date?->format('Y-m-d'),
            'valuation_method' => $this->valuation_method,
            'depreciation_method' => $this->depreciation_method,
            'useful_life_years' => $this->useful_life_years,
            'annual_depreciation_amount' => $this->annual_depreciation_amount ? number_format($this->annual_depreciation_amount, 2, '.', '') : null,
            'is_rental_property' => $this->is_rental_property,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'jurisdiction' => $this->whenLoaded('jurisdiction'),
            'entity' => $this->whenLoaded('entity'),
            'currency' => $this->whenLoaded('currency'),
            'valuations' => $this->when(
                $this->relationLoaded('valuations'),
                AssetValuationResource::collection($this->valuations)
            ),
        ];
    }
}
