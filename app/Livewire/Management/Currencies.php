<?php

namespace App\Livewire\Management;

use App\Http\Requests\Finance\StoreCurrencyRequest;
use App\Http\Requests\Finance\UpdateCurrencyRequest;
use App\Models\Currency;
use Illuminate\View\View;
use Livewire\Component;

class Currencies extends Component
{
    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $symbol = '';

    public bool $is_active = true;

    public function edit(int $currencyId): void
    {
        $currency = Currency::query()->findOrFail($currencyId);

        $this->editingId = $currency->id;
        $this->code = $currency->code;
        $this->name = $currency->name;
        $this->symbol = $currency->symbol ?? '';
        $this->is_active = $currency->is_active;
    }

    public function disable(int $currencyId): void
    {
        $currency = Currency::query()->findOrFail($currencyId);

        $this->resetErrorBag();
        if (! $this->ensureNotInUse($currency, __('Currency is in use and cannot be disabled.'))) {
            return;
        }

        $currency->update(['is_active' => false]);

        if ($this->editingId === $currency->id) {
            $this->is_active = false;
        }
    }

    public function enable(int $currencyId): void
    {
        $currency = Currency::query()->findOrFail($currencyId);
        $currency->update(['is_active' => true]);

        if ($this->editingId === $currency->id) {
            $this->is_active = true;
        }
    }

    public function delete(int $currencyId): void
    {
        $currency = Currency::query()->findOrFail($currencyId);

        $this->resetErrorBag();
        if (! $this->ensureNotInUse($currency, __('Currency is in use and cannot be deleted.'))) {
            return;
        }

        $currency->delete();

        if ($this->editingId === $currencyId) {
            $this->resetForm();
        }
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
            $currency = Currency::query()->findOrFail($this->editingId);
            $currency->update($validated);
        } else {
            Currency::create($validated);
        }

        $this->resetForm();
        $this->dispatch('currency-saved');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        $currencies = Currency::query()
            ->withCount([
                'accounts',
                'assets',
                'originalTransactions',
                'convertedTransactions',
                'fxRatesFrom',
                'fxRatesTo',
            ])
            ->orderBy('code')
            ->get()
            ->each(function (Currency $currency): void {
                $inUseCount = $currency->accounts_count
                    + $currency->assets_count
                    + $currency->original_transactions_count
                    + $currency->converted_transactions_count
                    + $currency->fx_rates_from_count
                    + $currency->fx_rates_to_count;

                $currency->setAttribute('in_use', $inUseCount > 0);
            });

        return view('livewire.management.currencies', [
            'currencies' => $currencies,
        ])->layout('layouts.app', [
            'title' => __('Currencies'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'currency_id' => $this->editingId,
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'symbol' => $this->symbol !== '' ? $this->symbol : null,
            'is_active' => $this->is_active,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rulesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateCurrencyRequest::create('/', 'PATCH', $data)
            : StoreCurrencyRequest::create('/', 'POST', $data);

        return $request->rules();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function messagesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateCurrencyRequest::create('/', 'PATCH', $data)
            : StoreCurrencyRequest::create('/', 'POST', $data);

        return $request->messages();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->symbol = '';
        $this->is_active = true;
    }

    private function ensureNotInUse(Currency $currency, string $message): bool
    {
        if ($currency->isInUse()) {
            $this->addError('currency', $message);

            return false;
        }

        return true;
    }
}
