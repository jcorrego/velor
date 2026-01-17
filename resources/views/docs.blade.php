<x-layouts::app :title="__('Documentation')">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <div class="space-y-6">
            <div>
                <flux:heading size="xl">{{ __('Documentation') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Guides for importing, categorizing, and reporting finance data.') }}
                </flux:text>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Contents') }}</flux:heading>
                <div class="mt-4 grid gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                    <a class="hover:underline" href="#csv-import">{{ __('CSV Import Guide') }}</a>
                    <a class="hover:underline" href="#category-mapping">{{ __('Category Setup and Schedule E Mapping') }}</a>
                    <a class="hover:underline" href="#developer-parsers">{{ __('Developer Guide: CSV Parsers') }}</a>
                    <a class="hover:underline" href="#rental-reporting">{{ __('Rental Property Reporting') }}</a>
                    <a class="hover:underline" href="#fx-management">{{ __('FX Rate Management and Overrides') }}</a>
                </div>
            </div>

            <section id="csv-import" class="space-y-4">
                <flux:heading size="lg">{{ __('CSV Import Guide') }}</flux:heading>
                <flux:text>{{ __('This guide explains how to import bank transactions from CSV files for supported banks.') }}</flux:text>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Supported Banks') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Banco Santander (Spain)') }}</li>
                        <li>{{ __('Mercury (USA)') }}</li>
                        <li>{{ __('Bancolombia (Colombia)') }}</li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('How to Import') }}</div>
                    <ol class="list-decimal space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Go to Finance.') }}</li>
                        <li>{{ __('Open the Transactions tab and click "Import Transactions".') }}</li>
                        <li>{{ __('Select the account to import into.') }}</li>
                        <li>{{ __('Choose your bank parser.') }}</li>
                        <li>{{ __('Upload the CSV file.') }}</li>
                        <li>{{ __('Preview the import and confirm.') }}</li>
                    </ol>
                </div>

                <div class="space-y-3">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Parser Expectations') }}</div>
                    <div class="space-y-2 text-sm text-zinc-700 dark:text-zinc-200">
                        <div><strong>{{ __('Mercury') }}</strong></div>
                        <ul class="list-disc space-y-1 pl-5">
                            <li>{{ __('Header includes "Date (UTC)" and "Amount".') }}</li>
                            <li>{{ __('Dates use') }} <code>MM-DD-YYYY</code> {{ __('in the Mercury export.') }}</li>
                            <li>{{ __('Amounts are numeric (negative for expenses).') }}</li>
                            <li>{{ __('Original currency in the file is ignored; Mercury transactions are imported as USD.') }}</li>
                        </ul>
                        <div class="pt-2"><strong>{{ __('Banco Santander') }}</strong></div>
                        <ul class="list-disc space-y-1 pl-5">
                            <li>{{ __('Separator: semicolon (;)') }}</li>
                            <li>{{ __('Header includes "Fecha", "Movimiento", "Cantidad".') }}</li>
                            <li>{{ __('Dates use') }} <code>DD/MM/YYYY</code>.</li>
                            <li>{{ __('Amounts may use commas for decimals.') }}</li>
                        </ul>
                        <div class="pt-2"><strong>{{ __('Bancolombia') }}</strong></div>
                        <ul class="list-disc space-y-1 pl-5">
                            <li>{{ __('Header includes "Fecha", "Descripción", "Débito", "Crédito".') }}</li>
                            <li>{{ __('Dates use') }} <code>DD/MM/YYYY</code>.</li>
                            <li>{{ __('Debits and credits are separated; credits import as positive, debits as negative.') }}</li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Troubleshooting') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('"Unexpected data found" usually means the selected parser does not match the CSV format.') }}</li>
                        <li>{{ __('"No such file" indicates an upload failure; re-upload the CSV and try again.') }}</li>
                        <li>{{ __('If a transaction already exists, it will be marked as a duplicate and skipped.') }}</li>
                    </ul>
                </div>
            </section>

            <section id="category-mapping" class="space-y-4">
                <flux:heading size="lg">{{ __('Category Setup and Schedule E Mapping') }}</flux:heading>
                <flux:text>{{ __('This guide explains how to create transaction categories and map them to tax forms like Schedule E and IRPF.') }}</flux:text>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Create Categories') }}</div>
                    <ol class="list-decimal space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Go to Finance.') }}</li>
                        <li>{{ __('Open the Categories tab.') }}</li>
                        <li>{{ __('Add a category with:') }}</li>
                    </ol>
                    <ul class="list-disc space-y-1 pl-10 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Name') }}</li>
                        <li>{{ __('Jurisdiction') }}</li>
                        <li>{{ __('Optional entity (use Global for shared categories)') }}</li>
                        <li>{{ __('Transaction type (Income or Expense)') }}</li>
                        <li>{{ __('Sort order (optional)') }}</li>
                    </ul>
                    <div class="text-sm text-zinc-700 dark:text-zinc-200">
                        {{ __('Categories are unique per jurisdiction and entity.') }}
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Create Tax Mappings') }}</div>
                    <ol class="list-decimal space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Go to Finance.') }}</li>
                        <li>{{ __('Open the Mappings tab.') }}</li>
                        <li>{{ __('Select a category.') }}</li>
                        <li>{{ __('Choose a tax form (e.g., Schedule E or IRPF).') }}</li>
                        <li>{{ __('Set the line item and country.') }}</li>
                        <li>{{ __('Save the mapping.') }}</li>
                    </ol>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Recommended Schedule E Line Items') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Rental income: "Rents received"') }}</li>
                        <li>{{ __('Repairs and maintenance') }}</li>
                        <li>{{ __('Management fees') }}</li>
                        <li>{{ __('Insurance') }}</li>
                        <li>{{ __('Utilities') }}</li>
                        <li>{{ __('Property tax') }}</li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Notes') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Multiple mappings can be created per category.') }}</li>
                        <li>{{ __('Mappings are used in rental reporting and tax form summaries.') }}</li>
                    </ul>
                </div>
            </section>

            <section id="developer-parsers" class="space-y-4">
                <flux:heading size="lg">{{ __('Developer Guide: CSV Parsers') }}</flux:heading>
                <flux:text>{{ __('This guide explains how to add a new CSV parser for bank statement imports.') }}</flux:text>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Parser Contract') }}</div>
                    <div class="text-sm text-zinc-700 dark:text-zinc-200">
                        {{ __('Implement CSVParserContract with:') }}
                    </div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li><code>parse(string $filePath): array</code></li>
                        <li><code>getName(): string</code></li>
                    </ul>
                    <div class="text-sm text-zinc-700 dark:text-zinc-200">
                        {{ __('Return an array of normalized transactions with:') }}
                    </div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li><code>date</code> (YYYY-MM-DD)</li>
                        <li><code>description</code></li>
                        <li><code>amount</code></li>
                        <li><code>original_currency</code></li>
                        <li><code>counterparty</code> (optional)</li>
                        <li><code>tags</code> (array)</li>
                        <li><code>import_source</code></li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Where to Add Parsers') }}</div>
                    <ol class="list-decimal space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Create a parser in') }} <code>app/Services/Finance/Parsers</code>.</li>
                        <li>{{ __('Register it in TransactionImportService::getAvailableParsers().') }}</li>
                        <li>{{ __('Update or add tests for the parser.') }}</li>
                    </ol>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Testing') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Add a unit test that feeds a sample CSV file into the parser.') }}</li>
                        <li>{{ __('Validate parsing output shape and date/amount conversions.') }}</li>
                        <li>{{ __('Use') }} <code>php artisan test --compact</code> {{ __('with the new test file.') }}</li>
                    </ul>
                </div>
            </section>

            <section id="rental-reporting" class="space-y-4">
                <flux:heading size="lg">{{ __('Rental Property Reporting') }}</flux:heading>
                <flux:text>{{ __('This guide explains how to view Schedule E style summaries for rental properties.') }}</flux:text>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Setup') }}</div>
                    <ol class="list-decimal space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Create assets in the Assets tab.') }}</li>
                        <li>{{ __('Ensure transactions are categorized as rental income or rental expenses.') }}</li>
                        <li>{{ __('Map categories to tax form line items if needed.') }}</li>
                    </ol>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('View the Report') }}</div>
                    <ol class="list-decimal space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Go to Finance.') }}</li>
                        <li>{{ __('Open the Reports tab.') }}</li>
                        <li>{{ __('Select a rental property asset.') }}</li>
                        <li>{{ __('Set the tax year.') }}</li>
                    </ol>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('What You See') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Rental income total') }}</li>
                        <li>{{ __('Rental expenses total') }}</li>
                        <li>{{ __('Depreciation') }}</li>
                        <li>{{ __('Net income') }}</li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Notes') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Reports aggregate categorized transactions for the selected year.') }}</li>
                        <li>{{ __('Depreciation uses the asset\'s configured method and useful life.') }}</li>
                    </ul>
                </div>
            </section>

            <section id="fx-management" class="space-y-4">
                <flux:heading size="lg">{{ __('FX Rate Management and Overrides') }}</flux:heading>
                <flux:text>{{ __('This guide explains how FX rates are applied and how to override them.') }}</flux:text>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Automatic FX Rates') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('FX rates are fetched from the ECB when available.') }}</li>
                        <li>{{ __('Transactions store original amount, converted amount, and the FX rate used.') }}</li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Override a Transaction Rate') }}</div>
                    <ol class="list-decimal space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Go to Finance.') }}</li>
                        <li>{{ __('Open the Transactions tab.') }}</li>
                        <li>{{ __('Click "Override FX Rate" on a transaction.') }}</li>
                        <li>{{ __('Enter the new rate and a reason.') }}</li>
                        <li>{{ __('Save the override.') }}</li>
                    </ol>
                    <div class="text-sm text-zinc-700 dark:text-zinc-200">
                        {{ __('The transaction is updated with the new rate and converted amount.') }}
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Notes') }}</div>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                        <li>{{ __('Overrides are recorded per transaction.') }}</li>
                        <li>{{ __('Use overrides for bank conversion rates or settlement differences.') }}</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
