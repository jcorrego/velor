<div class="grid gap-6 lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)]">
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Add Tax Year') }}</flux:heading>
        <flux:subheading>{{ __('Create new tax years per jurisdiction.') }}</flux:subheading>

        @if (session()->has('message'))
            <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
            </div>
        @endif

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:select wire:model="jurisdiction_id" label="{{ __('Jurisdiction') }}" placeholder="{{ __('Select jurisdiction') }}">
                @foreach($jurisdictions as $jurisdiction)
                    <option value="{{ $jurisdiction->id }}">{{ $jurisdiction->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="year" label="{{ __('Tax Year') }}" type="number" min="2000" max="2100" />

            <flux:button type="submit" variant="primary">
                {{ __('Create Tax Year') }}
            </flux:button>
        </form>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Tax Years') }}</flux:heading>
        <flux:subheading>{{ __('Years are listed by jurisdiction.') }}</flux:subheading>

        @if($taxYears->isEmpty())
            <div class="mt-6 text-center">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No tax years yet.') }}</p>
            </div>
        @else
            <div class="mt-6 space-y-6">
                @foreach($taxYears as $jurisdiction => $years)
                    <div class="space-y-2">
                        <flux:heading size="sm">{{ $jurisdiction }}</flux:heading>
                        <div class="flex flex-wrap gap-2">
                            @foreach($years as $taxYear)
                                <flux:badge size="sm" color="zinc">{{ $taxYear->year }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
