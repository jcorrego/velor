<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'currency_id' => $this->currency_id,
            'institution_name' => $this->institution_name,
            'account_number' => $this->account_number,
            'is_active' => $this->is_active,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'entity' => $this->whenLoaded('entity'),
            'currency' => $this->whenLoaded('currency'),
        ];
    }
}
