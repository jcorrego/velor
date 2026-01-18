<?php

use App\Models\Account;
use App\Services\Finance\TransactionImportService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Account $account;
    public $file;
    public $fileType = 'csv';
    public $parserType = '';
    public $previewData = null;
    public $importedCount = 0;
    public $showSuccess = false;
    public $importDescription = '';

    public function mount(Account $account): void
    {
        if ($account->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->account = $account;
        $this->configureImportSettings();
    }

    public function getParsersProperty(): array
    {
        return app(TransactionImportService::class)->getAvailableParsers();
    }

    public function getCsvParsersProperty(): array
    {
        $allowed = ['mercury'];

        return array_intersect_key($this->parsers, array_flip($allowed));
    }

    public function getPdfParsersProperty(): array
    {
        $allowed = ['santander', 'bancolombia'];

        return array_intersect_key(
            app(TransactionImportService::class)->getAvailablePdfParsers(),
            array_flip($allowed)
        );
    }

    public function getActiveParsersProperty(): array
    {
        if ($this->fileType === 'pdf') {
            return $this->pdfParsers;
        }

        return $this->csvParsers;
    }

    public function preview(): void
    {
        $rules = [
            'file' => ['required', 'file', 'max:5120'],
            'parserType' => ['required', Rule::in(array_keys($this->activeParsers))],
        ];

        if ($this->fileType === 'pdf') {
            $rules['file'][] = 'mimes:pdf';
        } else {
            $rules['file'][] = 'mimes:csv,txt';
        }

        $this->validate($rules);

        try {
            $service = app(TransactionImportService::class);
            $fullPath = $this->file->getRealPath();

            if (! $fullPath || ! is_readable($fullPath)) {
                $this->addError('file', 'Uploaded file is unavailable. Please try again.');

                return;
            }

            $extension = strtolower($this->file->getClientOriginalExtension());
            if ($this->fileType === 'pdf' && $extension !== 'pdf') {
                $this->addError('file', 'Please select a PDF file for PDF imports.');

                return;
            }

            if ($this->fileType === 'csv' && ! in_array($extension, ['csv', 'txt'], true)) {
                $this->addError('file', 'Please select a CSV file for CSV imports.');

                return;
            }

            $parsed = $this->fileType === 'pdf'
                ? $service->parsePDF($fullPath, $this->account->id, $this->parserType)
                : $service->parseCSV($fullPath, $this->parserType);
            $matchResult = $service->matchTransactions($parsed, $this->account);

            $this->previewData = $matchResult;
        } catch (\Exception $e) {
            $this->addError('file', 'Error parsing file: '.$e->getMessage());
        }
    }

    public function import(): void
    {
        if (! $this->previewData) {
            $this->addError('file', 'Please preview the file first.');

            return;
        }

        try {
            $service = app(TransactionImportService::class);

            $count = $service->importTransactions(
                $this->previewData['unmatched'],
                $this->account,
                $this->parserType
            );

            $this->importedCount = $count;
            $this->showSuccess = true;
            $this->reset(['file', 'parserType', 'previewData']);

            $this->dispatch('transactions-imported');
        } catch (\Exception $e) {
            $this->addError('file', 'Error importing transactions: '.$e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->reset(['file', 'parserType', 'previewData', 'importedCount', 'showSuccess']);
        $this->configureImportSettings();
    }

    private function configureImportSettings(): void
    {
        $accountName = mb_strtolower($this->account->name);

        if (str_contains($accountName, 'mercury')) {
            $this->fileType = 'csv';
            $this->parserType = 'mercury';
            $this->importDescription = 'Expected file: Mercury CSV export.';
        } elseif (str_contains($accountName, 'santander')) {
            $this->fileType = 'pdf';
            $this->parserType = 'santander';
            $this->importDescription = 'Expected file: Banco Santander PDF statement.';
        } elseif (str_contains($accountName, 'bancolombia')) {
            $this->fileType = 'pdf';
            $this->parserType = 'bancolombia';
            $this->importDescription = 'Expected file: Bancolombia PDF statement.';
        } else {
            $this->fileType = 'csv';
            $this->parserType = '';
            $this->importDescription = 'No import format configured for this account.';
        }
    }
};
?>

<div>
    @if($showSuccess)
        <flux:callout variant="success" class="mb-6">
            Successfully imported {{ $importedCount }} transaction(s)!
            <flux:button size="sm" variant="ghost" wire:click="resetForm" class="ml-2">
                Import More
            </flux:button>
        </flux:callout>
    @else
        <flux:heading size="lg" class="mb-4">Import Transactions</flux:heading>
        
        <form wire:submit="preview" class="space-y-6">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $importDescription }}
            </div>

            <!-- File Upload -->
            <flux:field>
                <flux:label>Import File</flux:label>
                <input 
                    type="file" 
                    wire:model="file"
                    accept="{{ $fileType === 'pdf' ? '.pdf' : '.csv,.txt' }}"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700"
                />
                <flux:error name="file" />
                
                @if($file)
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        Selected: {{ $file->getClientOriginalName() }}
                    </div>
                @endif

                <div wire:loading wire:target="file" class="mt-2 text-sm text-zinc-500">
                    Uploading...
                </div>
            </flux:field>

            @if(!$previewData)
                <flux:button type="submit" variant="primary" :disabled="!$file || !$parserType">
                    <span wire:loading.remove wire:target="preview">Preview Import</span>
                    <span wire:loading wire:target="preview">Processing...</span>
                </flux:button>
            @endif
        </form>

        <!-- Preview Results -->
        @if($previewData)
            <flux:separator class="my-6" />
            
            <div class="space-y-4">
                <flux:heading size="md">Import Preview</flux:heading>

                <!-- Summary Stats -->
                <div class="grid grid-cols-3 gap-4">
                    <flux:callout variant="info">
                        <div class="text-2xl font-bold">{{ $previewData['total'] }}</div>
                        <div class="text-sm">Total Transactions</div>
                    </flux:callout>

                    <flux:callout variant="success">
                        <div class="text-2xl font-bold">{{ $previewData['new'] }}</div>
                        <div class="text-sm">New to Import</div>
                    </flux:callout>

                    <flux:callout variant="warning">
                        <div class="text-2xl font-bold">{{ $previewData['duplicates'] }}</div>
                        <div class="text-sm">Duplicates (Skipped)</div>
                    </flux:callout>
                </div>

                <!-- New Transactions to Import -->
                @if(count($previewData['unmatched']) > 0)
                    <div class="mt-6">
                        <flux:heading size="sm" class="mb-3">Transactions to Import</flux:heading>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @foreach($previewData['unmatched'] as $transaction)
                                <div class="flex justify-between items-center p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                                    <div class="flex-1">
                                        <div class="font-medium whitespace-pre-line">{{ $transaction['description'] }}</div>
                                        <div class="text-sm text-zinc-500">
                                            {{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y') }}
                                            @if($transaction['counterparty'])
                                                • {{ $transaction['counterparty'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold {{ $transaction['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format(abs($transaction['amount']), 2) }} {{ $transaction['original_currency'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Duplicate Transactions -->
                @if(count($previewData['matched']) > 0)
                    <div class="mt-6">
                        <flux:heading size="sm" class="mb-3">Duplicate Transactions (Will Be Skipped)</flux:heading>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($previewData['matched'] as $transaction)
                                <div class="flex justify-between items-center p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 opacity-60">
                                    <div class="flex-1">
                                        <div class="font-medium whitespace-pre-line">{{ $transaction['description'] }}</div>
                                        <div class="text-sm text-zinc-500">
                                            {{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y') }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-zinc-500">
                                            {{ number_format(abs($transaction['amount']), 2) }} {{ $transaction['original_currency'] }}
                                        </div>
                                        <flux:badge variant="warning" size="sm">Duplicate</flux:badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-6">
                    <flux:button 
                        variant="primary" 
                        wire:click="import"
                        :disabled="count($previewData['unmatched']) === 0"
                    >
                        <span wire:loading.remove wire:target="import">
                            Import {{ $previewData['new'] }} Transaction(s)
                        </span>
                        <span wire:loading wire:target="import">
                            Importing...
                        </span>
                    </flux:button>

                    <flux:button variant="ghost" wire:click="resetForm">
                        Cancel
                    </flux:button>
                </div>
            </div>
        @endif
    @endif
</div>
