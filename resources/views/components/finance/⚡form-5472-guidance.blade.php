<?php

use App\Http\Requests\StoreFilingFormResponseRequest;
use App\Models\Filing;
use App\Models\FormSchema;
use Livewire\Component;

new class extends Component
{
    public string $filingId = '';

    /**
     * @var array<string, string>
     */
    public array $formData = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $sections = [];

    public ?int $formSchemaId = null;

    public ?string $schemaTitle = null;

    public function mount(): void
    {
        $firstFiling = Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', '5472'))
            ->with('taxYear')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($firstFiling) {
            $this->filingId = (string) $firstFiling->id;
            $this->loadSchema();
        }
    }

    public function updatedFilingId(): void
    {
        $this->loadSchema();
    }

    public function save(): void
    {
        $filing = $this->currentFiling();
        $schema = $this->currentSchema($filing);

        if (! $filing || ! $schema) {
            $this->addError('filingId', __('Select a Form 5472 filing to continue.'));
            return;
        }

        $data = [
            'filing_id' => $filing->id,
            'form_schema_id' => $schema->id,
            'form_data' => $this->formData,
        ];

        $request = StoreFilingFormResponseRequest::create('/', 'POST', $data);
        $validated = validator(
            $data,
            $request->rulesForSchema($schema),
            $request->messagesForSchema($schema),
        )->validate();

        $filing->form_schema_id = $schema->id;
        $filing->form_data = $validated['form_data'] ?? [];
        $filing->save();

        $this->dispatch('form-5472-saved');
    }

    public function with(): array
    {
        $filings = Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', '5472'))
            ->with(['taxYear', 'filingType'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filing = $this->currentFiling();

        return [
            'filings' => $filings,
            'filing' => $filing,
            'sections' => $this->sections,
            'schemaTitle' => $this->schemaTitle,
        ];
    }

    private function loadSchema(): void
    {
        $this->resetErrorBag();

        $filing = $this->currentFiling();
        $schema = $this->currentSchema($filing);

        if (! $schema) {
            $this->sections = [];
            $this->formSchemaId = null;
            $this->schemaTitle = null;
            $this->formData = [];
            return;
        }

        $this->formSchemaId = $schema->id;
        $this->schemaTitle = $schema->title;
        $this->sections = $schema->sections ?? [];

        $this->formData = $filing->form_data ?? [];

        foreach ($this->sections as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $key = $field['key'] ?? null;
                if (! $key) {
                    continue;
                }

                $this->formData[$key] = $this->formData[$key] ?? '';
            }
        }
    }

    private function currentFiling(): ?Filing
    {
        if (! $this->filingId) {
            return null;
        }

        return Filing::query()
            ->where('user_id', auth()->id())
            ->with(['taxYear', 'filingType'])
            ->find($this->filingId);
    }

    private function currentSchema(?Filing $filing): ?FormSchema
    {
        if (! $filing) {
            return null;
        }

        return FormSchema::query()
            ->where('form_code', '5472')
            ->where('tax_year', $filing->taxYear->year)
            ->first();
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Form 5472 Guidance & Data') }}</flux:heading>
        <flux:subheading>{{ __('Review section guidance and capture supplemental Form 5472 details.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:select wire:model.live="filingId" label="{{ __('Form 5472 Filing') }}" placeholder="{{ __('Select a filing') }}">
            <option value="">{{ __('Select a filing') }}</option>
            @foreach($filings as $filingOption)
                <option value="{{ $filingOption->id }}">
                    {{ $filingOption->taxYear->year }} - {{ ucfirst(str_replace('_', ' ', $filingOption->status->value)) }}
                </option>
            @endforeach
        </flux:select>
    </div>

    @error('filingId')
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    @if (! $filing)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            {{ __('No Form 5472 filings found. Create a Form 5472 filing in your tax year filings to get started.') }}
        </div>
    @elseif ($sections === [])
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            {{ __('Form 5472 guidance is not yet available for this tax year.') }}
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:heading size="md">{{ $schemaTitle ?? __('Form 5472') }}</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $filing->taxYear->year }}</flux:badge>
            </div>
            <flux:subheading class="mt-1">{{ __('Supplemental data is saved per filing and tax year.') }}</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-6">
            @foreach ($sections as $section)
                <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-4">
                        <div>
                            <flux:heading size="md">{{ $section['title'] }}</flux:heading>
                            @if (! empty($section['summary']))
                                <div class="mt-2 grid gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    @foreach ($section['summary'] as $line)
                                        <p>{{ $line }}</p>
                                    @endforeach
                                </div>
                            @endif
                            @if (! empty($section['bullets']))
                                <ul class="mt-3 grid gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    @foreach ($section['bullets'] as $bullet)
                                        <li class="flex gap-2">
                                            <span class="mt-1 h-1.5 w-1.5 rounded-full bg-zinc-400"></span>
                                            <span>{{ $bullet }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        @if (! empty($section['fields']))
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($section['fields'] as $field)
                                    @php
                                        $fieldKey = $field['key'] ?? '';
                                        $fieldLabel = $field['label'] ?? $fieldKey;
                                        $fieldType = $field['type'] ?? 'text';
                                    @endphp

                                    @if ($fieldType === 'textarea')
                                        <flux:textarea
                                            wire:model="formData.{{ $fieldKey }}"
                                            label="{{ $fieldLabel }}"
                                        />
                                    @else
                                        <flux:input
                                            wire:model="formData.{{ $fieldKey }}"
                                            label="{{ $fieldLabel }}"
                                            type="{{ $fieldType }}"
                                        />
                                    @endif
                                    <flux:error name="formData.{{ $fieldKey }}" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">
                    {{ __('Save Form 5472 data') }}
                </flux:button>
                <x-action-message on="form-5472-saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    @endif
</div>
