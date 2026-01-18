<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Documents') }}</flux:heading>
        <flux:subheading>{{ __('Upload, tag, and link supporting documents.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)]">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Upload document') }}</flux:heading>
            <flux:subheading>{{ __('Attach metadata and link to related records.') }}</flux:subheading>

            <form wire:submit="save" class="mt-5 space-y-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="document-file">
                        {{ __('Document file') }}
                    </label>
                    <input
                        id="document-file"
                        type="file"
                        wire:model="file"
                        class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                    />
                    @error('file')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div wire:loading wire:target="file" class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Uploading...') }}
                    </div>
                </div>

                <flux:input wire:model="title" :label="__('Title (optional)')" type="text" />
                <flux:input wire:model="tagInput" :label="__('Tags (comma separated)')" type="text" />

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="document-jurisdiction">
                            {{ __('Jurisdiction') }}
                        </label>
                        <select
                            id="document-jurisdiction"
                            wire:model="jurisdiction_id"
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <option value="">{{ __('Select jurisdiction') }}</option>
                            @foreach ($jurisdictions as $jurisdiction)
                                <option value="{{ $jurisdiction->id }}">
                                    {{ $jurisdiction->name }} ({{ $jurisdiction->iso_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="document-tax-year">
                            {{ __('Tax year') }}
                        </label>
                        <select
                            id="document-tax-year"
                            wire:model="tax_year_id"
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <option value="">{{ __('Select tax year') }}</option>
                            @foreach ($taxYears as $taxYear)
                                <option value="{{ $taxYear->id }}">
                                    {{ $taxYear->year }} · {{ $taxYear->jurisdiction->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="document-legal">
                        {{ __('Legal document') }}
                    </label>
                    <flux:checkbox id="document-legal" wire:model="is_legal" :label="__('Run OCR for searchable text')" />
                </div>

                <div class="space-y-3">
                    <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Link to records') }}
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400" for="document-entities">
                            {{ __('Entities') }}
                        </label>
                        <select
                            id="document-entities"
                            wire:model="entityIds"
                            multiple
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400" for="document-assets">
                            {{ __('Assets') }}
                        </label>
                        <select
                            id="document-assets"
                            wire:model="assetIds"
                            multiple
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}">{{ $asset->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400" for="document-transactions">
                            {{ __('Transactions (latest 50)') }}
                        </label>
                        <select
                            id="document-transactions"
                            wire:model="transactionIds"
                            multiple
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            @foreach ($transactions as $transaction)
                                <option value="{{ $transaction->id }}">
                                    {{ $transaction->transaction_date->format('M d, Y') }} · {{ $transaction->description ?? __('Transaction #:id', ['id' => $transaction->id]) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400" for="document-filings">
                            {{ __('Filings') }}
                        </label>
                        <select
                            id="document-filings"
                            wire:model="filingIds"
                            multiple
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            @foreach ($filings as $filing)
                                <option value="{{ $filing->id }}">
                                    {{ $filing->filingType->name ?? $filing->filingType->code ?? __('Filing #:id', ['id' => $filing->id]) }} · {{ $filing->taxYear->year ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <flux:button variant="primary" type="submit">
                        {{ __('Save document') }}
                    </flux:button>
                    <x-action-message on="document-saved">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('Documents library') }}</flux:heading>
                    <flux:subheading>{{ __('Filter and review uploaded files.') }}</flux:subheading>
                </div>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search documents...') }}"
                    type="text"
                />

                <flux:select wire:model.live="filterJurisdictionId" placeholder="{{ __('All jurisdictions') }}">
                    <option value="">{{ __('All jurisdictions') }}</option>
                    @foreach ($jurisdictions as $jurisdiction)
                        <option value="{{ $jurisdiction->id }}">
                            {{ $jurisdiction->name }} ({{ $jurisdiction->iso_code }})
                        </option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterTaxYearId" placeholder="{{ __('All tax years') }}">
                    <option value="">{{ __('All tax years') }}</option>
                    @foreach ($taxYears as $taxYear)
                        <option value="{{ $taxYear->id }}">
                            {{ $taxYear->year }} · {{ $taxYear->jurisdiction->name }}
                        </option>
                    @endforeach
                </flux:select>
            </div>

            @if (session()->has('message'))
                <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                    <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
                </div>
            @endif

            @if ($documents->isEmpty())
                <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('No documents yet. Upload your first file to get started.') }}
                </div>
            @else
                <div class="mt-6 flex flex-col gap-4">
                    @foreach ($documents as $document)
                        <div class="rounded-lg border border-zinc-200 p-4 shadow-sm dark:border-zinc-700">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $document->title ?? $document->original_name }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $document->original_name }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($document->is_legal)
                                        <flux:badge size="sm" color="amber">{{ __('Legal') }}</flux:badge>
                                    @endif
                                    @if ($document->mime_type)
                                        <flux:badge size="sm" color="zinc">{{ $document->mime_type }}</flux:badge>
                                    @endif
                                </div>
                            </div>

                            @if ($document->tags->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($document->tags as $tag)
                                        <flux:badge size="sm" color="zinc">{{ $tag->name }}</flux:badge>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-3 grid gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <div class="flex flex-wrap gap-2">
                                    <span>{{ __('Jurisdiction:') }}</span>
                                    <span class="text-zinc-700 dark:text-zinc-200">
                                        {{ $document->jurisdiction?->name ?? __('—') }}
                                    </span>
                                    <span>•</span>
                                    <span>{{ __('Tax year:') }}</span>
                                    <span class="text-zinc-700 dark:text-zinc-200">
                                        {{ $document->taxYear?->year ?? __('—') }}
                                    </span>
                                </div>

                                @if ($document->entities->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>{{ __('Entities:') }}</span>
                                        @foreach ($document->entities as $entity)
                                            <flux:badge size="sm" color="blue">{{ $entity->name }}</flux:badge>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($document->assets->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>{{ __('Assets:') }}</span>
                                        @foreach ($document->assets as $asset)
                                            <flux:badge size="sm" color="purple">{{ $asset->name }}</flux:badge>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($document->transactions->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>{{ __('Transactions:') }}</span>
                                        @foreach ($document->transactions as $transaction)
                                            <flux:badge size="sm" color="zinc">
                                                {{ $transaction->transaction_date->format('M d, Y') }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($document->filings->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>{{ __('Filings:') }}</span>
                                        @foreach ($document->filings as $filing)
                                            <flux:badge size="sm" color="zinc">
                                                {{ $filing->filingType->code ?? __('Filing') }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($documents->hasPages())
                    <div class="mt-4">
                        {{ $documents->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
</div>