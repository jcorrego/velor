<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionCategoryResource extends JsonResource
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
            'jurisdiction_id' => $this->jurisdiction_id,
            'income_or_expense' => $this->income_or_expense,
            'parent_category_id' => $this->parent_category_id,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'jurisdiction' => $this->whenLoaded('jurisdiction'),
            'taxMappings' => $this->whenLoaded('taxMappings'),
        ];
    }
}
