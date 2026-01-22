<?php

use App\Finance\Services\UsTaxReportingService;
use App\Http\Requests\StoreFilingFormResponseRequest;
use App\Models\Currency;
use App\Models\Filing;
use App\Services\FormSchemaLoader;
use Livewire\Component;

new class extends Component
{
    public string $filingId = '';

    /**
     * @var array<string, mixed>
     */
    public array $formData = [];

    /**
     * @var array<string, array{value: float, formatted: string, transaction_count: int, category_count: int}>
     */
    public array $calculatedFields = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $sections = [];

    public ?string $schemaTitle = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $schema = null;

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

    public function updatedFormData(mixed $value, string $key): void
    {
        $filing = $this->currentFiling();
        $schema = $this->currentSchema($filing);

        if (! $filing || ! $schema) {
            return;
        }

        $request = StoreFilingFormResponseRequest::create('/', 'POST', [
            'formData' => $this->formData,
        ]);

        $rules = array_filter(
            $request->rulesForSchema($schema, 'formData'),
            fn (mixed $value, string $ruleKey): bool => str_starts_with($ruleKey, 'formData.'),
            ARRAY_FILTER_USE_BOTH
        );

        $messages = array_filter(
            $request->messagesForSchema($schema, 'formData'),
            fn (string $message, string $messageKey): bool => str_starts_with($messageKey, 'formData.'),
            ARRAY_FILTER_USE_BOTH
        );

        $this->validateOnly("formData.{$key}", $rules, $messages);

        $this->persistFormData($filing);
    }

    public function save(): void
    {
        $filing = $this->currentFiling();
        $schema = $this->currentSchema($filing);

        if (! $filing || ! $schema) {
            $this->addError('filingId', __('Select a Form 5472 filing to continue.'));
            return;
        }

        $normalizedFormData = $this->normalizeFormData($this->formData);

        $data = [
            'filing_id' => $filing->id,
            'formData' => $normalizedFormData,
        ];

        $request = StoreFilingFormResponseRequest::create('/', 'POST', $data);
        $validated = validator(
            $data,
            $request->rulesForSchema($schema, 'formData'),
            $request->messagesForSchema($schema, 'formData'),
        )->validate();

        $this->formData = $validated['formData'] ?? [];

        $this->persistFormData($filing);
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
            'calculatedFields' => $this->calculatedFields,
        ];
    }

    private function loadSchema(): void
    {
        $this->resetErrorBag();

        $filing = $this->currentFiling();
        $schema = $this->currentSchema($filing);

        if (! $schema) {
            $this->sections = [];
            $this->schemaTitle = null;
            $this->schema = null;
            $this->formData = [];
            $this->calculatedFields = [];
            return;
        }

        $this->schema = $schema;
        $this->schemaTitle = $schema['title'] ?? null;
        $this->sections = $schema['sections'] ?? [];

        $this->formData = $filing->form_data ?? [];
        $this->calculatedFields = [];

        foreach ($this->sections as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $key = $field['key'] ?? null;
                if (! $key) {
                    continue;
                }

                $fieldType = $field['type'] ?? 'text';
                if ($fieldType === 'calculated') {
                    $summary = $this->buildCalculatedFieldSummary($filing, (string) $key);
                    $this->calculatedFields[(string) $key] = $summary;
                    $this->formData[(string) $key] = $summary['value'];
                    continue;
                }

                $defaultValue = $fieldType === 'boolean' ? false : '';
                $this->formData[$key] = $this->formData[$key] ?? $defaultValue;
            }
        }
    }

    /**
     * @return array{value: float, formatted: string, transaction_count: int, category_count: int}
     */
    private function buildCalculatedFieldSummary(Filing $filing, string $lineItem): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'value' => 0.0,
                'formatted' => $this->formatCurrency(0.0),
                'transaction_count' => 0,
                'category_count' => 0,
            ];
        }

        $summary = app(UsTaxReportingService::class)->getForm5472LineItemSummary(
            $user,
            $filing->taxYear->year,
            $lineItem
        );

        return [
            'value' => $summary['total'],
            'formatted' => $this->formatCurrency($summary['total']),
            'transaction_count' => $summary['transaction_count'],
            'category_count' => $summary['category_count'],
        ];
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

    private function currentSchema(?Filing $filing): ?array
    {
        if (! $filing) {
            return null;
        }

        return app(FormSchemaLoader::class)->load(
            $filing->filingType->code,
            $filing->taxYear->year
        );
    }

    private function persistFormData(Filing $filing): void
    {
        $filing->form_data = $this->normalizeFormData($this->formData);
        $filing->save();
    }

    /**
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    private function normalizeFormData(array $formData): array
    {
        foreach ($formData as $key => $value) {
            if ($value === '') {
                $formData[$key] = null;
            }
        }

        return $formData;
    }

    private function formatCurrency(float $amount): string
    {
        $currency = Currency::query()->where('code', 'USD')->first();
        $symbol = $currency?->symbol ?? '$';

        return $symbol . number_format($amount, 2, '.', ',');
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Form 5472 Guidance & Data') }}</flux:heading>
        <flux:subheading>{{ __('Review section guidance and capture supplemental Form 5472 details.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:select wire:model.change="filingId" label="{{ __('Form 5472 Filing') }}" placeholder="{{ __('Select a filing') }}">
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

        <div class="space-y-6">
            @foreach ($sections as $section)
              @php
                  $sectionId = 'form-5472-section-' . $loop->index;
              @endphp
              <section
                  class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow-sm dark:divide-white/10 dark:bg-gray-800/50 dark:shadow-none dark:outline dark:-outline-offset-1 dark:outline-white/10"
                  x-data="{ open: false }"
                  data-form-5472-section
              >
                <button
                    type="button"
                    class="w-full cursor-pointer px-4 py-5 text-left sm:px-6 bg-gray-50 dark:bg-gray-700/30"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-controls="{{ $sectionId }}-content"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $section['label'] ?? '' }}</h3>
                            <p class="mt-1 text-md text-gray-500 dark:text-gray-400">{{ $section['title'] }}</p>
                        </div>
                        <span class="mt-1 text-zinc-500 dark:text-zinc-300">
                            <flux:icon.chevron-down class="size-4" x-show="open" />
                            <flux:icon.chevron-right class="size-4" x-show="!open" />
                        </span>
                    </div>
                    <div class="text-gray-900 opacity-50 dark:text-white bg-gray-50 dark:bg-gray-700/30">
                      @if (! empty($section['summary']))
                          <div class="mt-2 grid gap-2 text-sm text-gray-900 dark:text-zinc-300">
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
                </button>
                <div
                    class="px-4 py-5 sm:p-6 space-y-4"
                    id="{{ $sectionId }}-content"
                    x-show="open"
                    x-transition
                    x-cloak
                >
                        @if (! empty($section['fields']))
                            <div class="grid gap-12 md:grid-cols-12">
                                @foreach ($section['fields'] as $field)
                                    @php
                                        $fieldKey = $field['key'] ?? '';
                                        $fieldLabel = $field['label'] ?? $fieldKey;
                                        $fieldType = $field['type'] ?? 'text';
                                        $fieldHelp = $field['help'] ?? '';
                                        $fieldOptions = $field['options'] ?? [];
                                        $fieldRequired = $field['required'] ?? false;
                                        $fieldClass = $field['class'] ?? 'md:col-span-6';
                                        $fieldMask = $field['mask'] ?? null;
                                    @endphp
                                    <flux:field wire:key="form-5472-field-{{ $fieldKey }}" class="content-baseline {{ $fieldClass }}">
                                        @if ($fieldType === 'boolean')
                                            {{-- <flux:separator /> --}}
                                            <flux:field variant="inline" class="items-start">
                                                <flux:checkbox wire:model.change="formData.{{ $fieldKey }}"/>
                                                <flux:label>{{ $fieldLabel }}</flux:label>
                                            </flux:field>
                                            <flux:description class="mt-0!">{!! $fieldHelp !!}</flux:description>
                                            <flux:error name="formData.{{ $fieldKey }}" />
                                        @else
                                            @if ($fieldRequired)
                                                <flux:label>
                                                  {{ $fieldLabel }}
                                                  <flux:badge rounded color="amber" size="sm" class="ml-2">Required</flux:badge>
                                                </flux:label>
                                            @else
                                                <flux:label> {{ $fieldLabel }}</flux:label>
                                            @endif
                                            @if ($fieldType === 'calculated')
                                                @php
                                                    $calculated = $calculatedFields[$fieldKey] ?? null;
                                                    $calculatedValue = $calculated['formatted'] ?? '$0.00';
                                                    $transactionsCount = $calculated['transaction_count'] ?? 0;
                                                    $categoriesCount = $calculated['category_count'] ?? 0;
                                                    $calculatedInfo = __('Calculated from :transactions transactions across :categories categories.', [
                                                        'transactions' => $transactionsCount,
                                                        'categories' => $categoriesCount,
                                                    ]);
                                                @endphp
                                                <flux:input type="text" readonly value="{{ $calculatedValue }}" />
                                                <flux:description class="mt-0!">
                                                    @if ($fieldHelp)
                                                        {!! $fieldHelp !!}
                                                        <span class="block">{{ $calculatedInfo }}</span>
                                                    @else
                                                        {{ $calculatedInfo }}
                                                    @endif
                                                </flux:description>
                                            @elseif ($fieldType === 'textarea')
                                                <flux:textarea wire:model.live.debounce.500ms="formData.{{ $fieldKey }}" />
                                            @elseif ($fieldType === 'select')
                                                <flux:select
                                                    wire:model.change="formData.{{ $fieldKey }}"
                                                    :placeholder="__('Select :label', ['label' => $fieldLabel])"
                                                    label="{{ $fieldLabel }}"
                                                    description="{!! $fieldHelp !!}"
                                                >
                                                    <option value="">{{ __('Select :label', ['label' => $fieldLabel]) }}</option>
                                                    @foreach ($fieldOptions as $option)
                                                        <option value="{{ $option['value'] ?? $option['label'] }}">
                                                            {{ $option['label'] ?? $option['value'] }}
                                                        </option>
                                                    @endforeach
                                                </flux:select>
                                            @else
                                                @if ($fieldMask)
                                                  <flux:input
                                                      wire:model.live.debounce.500ms="formData.{{ $fieldKey }}"
                                                      type="{{ $fieldType }}"
                                                      mask="{{ $fieldMask }}"
                                                  />
                                                @else
                                                  <flux:input
                                                      wire:model.live.debounce.500ms="formData.{{ $fieldKey }}"
                                                      type="{{ $fieldType }}"
                                                  />
                                                @endif
                                                
                                            @endif
                                            <flux:error name="formData.{{ $fieldKey }}" />
                                            @if ($fieldType !== 'calculated')
                                                <flux:description class="mt-0!">{!! $fieldHelp !!}</flux:description>
                                            @endif
                                        @endif
                                    </flux:field>
                                @endforeach
                            </div>
                        @endif
                </div>
              </section>
            @endforeach

        </div>
    @endif
</div>
