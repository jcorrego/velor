<?php

namespace App\Livewire\Management;

use App\Http\Requests\StoreUserProfileRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\Jurisdiction;
use App\Models\UserProfile;
use Illuminate\View\View;
use Livewire\Component;

class Profiles extends Component
{
    public ?int $editingId = null;

    public ?int $jurisdiction_id = null;

    public string $name = '';

    public string $tax_id = '';

    public string $default_currency = '';

    public string $display_currency = '';

    public function edit(int $profileId): void
    {
        $profile = UserProfile::query()
            ->where('user_id', auth()->id())
            ->with('jurisdiction')
            ->findOrFail($profileId);

        $this->editingId = $profile->id;
        $this->jurisdiction_id = $profile->jurisdiction_id;
        $this->name = $profile->name;
        $this->tax_id = $profile->tax_id ?? '';
        $this->default_currency = $profile->default_currency ?? '';
        $this->display_currency = $profile->display_currencies[$profile->jurisdiction->iso_code] ?? '';
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
            $profile = UserProfile::query()
                ->where('user_id', auth()->id())
                ->findOrFail($this->editingId);

            $profile->update($validated);
        } else {
            UserProfile::create($validated);
        }

        $this->resetForm();
        $this->dispatch('profile-saved');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.management.profiles', [
            'profiles' => UserProfile::query()
                ->where('user_id', auth()->id())
                ->with('jurisdiction')
                ->orderBy('name')
                ->get(),
            'jurisdictions' => Jurisdiction::query()
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', [
            'title' => __('Profiles'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $displayCurrencies = null;
        $jurisdiction = $this->jurisdiction_id
            ? Jurisdiction::query()->find($this->jurisdiction_id)
            : null;

        if ($jurisdiction && $this->display_currency !== '') {
            $displayCurrencies = [
                $jurisdiction->iso_code => strtoupper($this->display_currency),
            ];
        }

        return [
            'user_id' => auth()->id(),
            'jurisdiction_id' => $this->jurisdiction_id,
            'name' => $this->name,
            'tax_id' => $this->tax_id,
            'default_currency' => strtoupper($this->default_currency),
            'display_currencies' => $displayCurrencies,
            'user_profile_id' => $this->editingId,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rulesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateUserProfileRequest::create('/', 'PATCH', $data)
            : StoreUserProfileRequest::create('/', 'POST', $data);

        return $request->rules();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function messagesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateUserProfileRequest::create('/', 'PATCH', $data)
            : StoreUserProfileRequest::create('/', 'POST', $data);

        return $request->messages();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->jurisdiction_id = null;
        $this->name = '';
        $this->tax_id = '';
        $this->default_currency = '';
        $this->display_currency = '';
    }
}
