<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\User;
use App\Services\Finance\Parsers\PdfTextExtractor;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

it('previews a CSV import for a mercury account', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->mercury()->create();

    $csvContent = implode("\n", [
        'Date (UTC),Description,Amount,Status,Source Account,Bank Description,Reference,Note,Last Four Digits,Name On Card,Mercury Category,Category,GL Code,Timestamp,Original Currency,Check Number,Tags,Cardholder Email,Tracking ID',
        '01-17-2026,Coffee Shop,-4.25,Pending,Mercury Checking xx3992,COFFEE SHOP,,,1234,Test User,Grocery,,,01-17-2026 16:33:58,EUR,,,user@example.com,',
    ]);
    $file = UploadedFile::fake()->createWithContent('mercury.csv', $csvContent);

    $this->actingAs($user);

    Livewire::test('finance.transaction-import-form', ['account' => $account])
        ->set('file', $file)
        ->call('preview')
        ->assertHasNoErrors()
        ->assertSet('previewData.total', 1)
        ->assertSet('previewData.unmatched.0.original_currency', 'USD')
        ->assertSee('Category: Grocery');
});

it('previews a PDF import for an account', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->bancoSantander()->create();

    $pdfContent = "2026-01-17 Payment received 1500.00\n2026-01-16 Bank fee -5.00";
    $file = UploadedFile::fake()->createWithContent('santander.pdf', $pdfContent);

    $this->actingAs($user);

    app()->instance(PdfTextExtractor::class, new class($pdfContent) extends PdfTextExtractor
    {
        public function __construct(private string $text) {}

        public function extract(string $filePath): string
        {
            return $this->text;
        }
    });

    try {
        Livewire::test('finance.transaction-import-form', ['account' => $account])
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('previewData.total', 2)
            ->assertSet('previewData.unmatched.0.original_currency', 'EUR');
    } finally {
        app()->forgetInstance(PdfTextExtractor::class);
    }
});
