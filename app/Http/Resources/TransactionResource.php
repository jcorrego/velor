<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'account_id' => $this->account_id,
            'type' => $this->type,
            'original_amount' => number_format($this->original_amount, 2, '.', ''),
            'original_currency_id' => $this->original_currency_id,
            'converted_amount' => $this->converted_amount ? number_format($this->converted_amount, 2, '.', '') : null,
            'converted_currency_id' => $this->converted_currency_id,
            'fx_rate' => $this->fx_rate ? number_format($this->fx_rate, 8, '.', '') : null,
            'category_id' => $this->category_id,
            'counterparty_name' => $this->counterparty_name,
            'description' => $this->description,
            'tags' => $this->tags,
            'reconciled_at' => $this->reconciled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'account' => $this->whenLoaded('account'),
            'category' => $this->whenLoaded('category'),
            'originalCurrency' => $this->whenLoaded('originalCurrency'),
            'convertedCurrency' => $this->whenLoaded('convertedCurrency'),
        ];
    }
}
