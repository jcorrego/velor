<?php

use App\Finance\Services\UsTaxReportingService;
use App\Http\Requests\StoreFilingFormResponseRequest;
use App\Models\Currency;
use App\Models\Entity;
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
        $summary = ['contributions' => 0, 'draws' => 0, 'related_party_totals' => 0];
        $yearEndTotals = ['total' => 0, 'entities' => []];

        if ($filing) {
            $summary = app(UsTaxReportingService::class)->getOwnerFlowSummary(
                auth()->user(),
                $filing->taxYear->year
            );
            $yearEndTotals = app(UsTaxReportingService::class)->getForm5472YearEndTotals(
                auth()->user(),
                $filing->taxYear->year
            );
        }

        return [
            'filings' => $filings,
            'filing' => $filing,
            'sections' => $this->sections,
            'schemaTitle' => $this->schemaTitle,
            'calculatedFields' => $this->calculatedFields,
            'summary' => $summary,
            'yearEndTotals' => $yearEndTotals,
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

        $this->hydrateReportingCorporation($filing);
    }

    private function hydrateReportingCorporation(Filing $filing): void
    {
        $reportingName = $this->formData['1a'] ?? null;
        $reportingEin = $this->formData['1b'] ?? null;

        if (($reportingName !== null && $reportingName !== '') || ($reportingEin !== null && $reportingEin !== '')) {
            return;
        }

        $entity = Entity::query()
            ->where('user_id', auth()->id())
            ->whereHas('jurisdiction', fn ($query) => $query->where('iso_code', 'USA'))
            ->orderBy('created_at')
            ->first();

        if (! $entity) {
            return;
        }

        $this->formData['1a'] = $entity->name;

        if ($entity->ein_or_tax_id) {
            $this->formData['1b'] = $entity->ein_or_tax_id;
        }

        $this->persistFormData($filing);
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
        <flux:heading size="lg">{{ __('Form 5472') }}</flux:heading>
        <flux:subheading>{{ __('Capture supplemental Form 5472 details.') }}</flux:subheading>
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
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Owner Contributions') }}</div>
                <div class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                    ${{ number_format($summary['contributions'] ?? 0, 2) }} USD
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Capital invested') }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Owner Draws') }}</div>
                <div class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                    ${{ number_format($summary['draws'] ?? 0, 2) }} USD
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Capital withdrawn') }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Related-Party Transactions') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    ${{ number_format($summary['related_party_totals'] ?? 0, 2) }} USD
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Status') }}: {{ ucfirst(str_replace('_', ' ', $filing->status->value)) }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Year-End Assets + Accounts') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    ${{ number_format($yearEndTotals['total'] ?? 0, 2) }} USD
                </div>
                <div class="mt-3 space-y-2 text-xs text-zinc-500 dark:text-zinc-400">
                    @forelse ($yearEndTotals['entities'] ?? [] as $entityTotal)
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $entityTotal['entity_name'] }}</span>
                                <span>${{ number_format($entityTotal['total'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2 text-[11px]">
                                <span>{{ __('Accounts') }}: ${{ number_format($entityTotal['accounts_total'] ?? 0, 2) }}</span>
                                <span>{{ __('Assets') }}: ${{ number_format($entityTotal['assets_total'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    @empty
                        <div>{{ __('No year-end values recorded for this jurisdiction.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <strong>{{ __('Note') }}:</strong> {{ __('This summary is for Form 5472 reporting. Related-party totals include all transactions between the entity and owners.') }}
        </div>

        @if ($sections === [])
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                {{ __('Form 5472 data is not yet available for this tax year.') }}
            </div>
        @else
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <flux:heading size="md" class="flex flex-col gap-2">
                        <div>{{ $schemaTitle ?? __('Form 5472') }}</div>
                    </flux:heading>

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
    @endif
</div>
