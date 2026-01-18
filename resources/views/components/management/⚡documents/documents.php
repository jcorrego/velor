<?php

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Asset;
use App\Models\Document;
use App\Models\DocumentTag;
use App\Models\Entity;
use App\Models\Filing;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\Transaction;
use App\Services\Documents\DocumentTextExtractor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $file;

    public string $title = '';

    public ?int $jurisdiction_id = null;

    public ?int $tax_year_id = null;

    public bool $is_legal = false;

    public string $tagInput = '';

    /** @var array<int, int|string> */
    public array $entityIds = [];

    /** @var array<int, int|string> */
    public array $assetIds = [];

    /** @var array<int, int|string> */
    public array $transactionIds = [];

    /** @var array<int, int|string> */
    public array $filingIds = [];

    public string $search = '';

    public string $filterJurisdictionId = '';

    public string $filterTaxYearId = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterJurisdictionId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTaxYearId(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $data = $this->formData();

        $validated = validator(
            $data,
            $this->rulesForSave($data),
            $this->messagesForSave($data)
        )->validate();

        $this->guardLinkedRecords(
            $validated['entity_ids'] ?? [],
            $validated['asset_ids'] ?? [],
            $validated['transaction_ids'] ?? [],
            $validated['filing_ids'] ?? []
        );

        $disk = config('filesystems.default', 'local');
        $path = $this->file->store(path: 'documents/'.auth()->id(), options: $disk);

        $document = Document::create([
            'user_id' => auth()->id(),
            'jurisdiction_id' => $validated['jurisdiction_id'],
            'tax_year_id' => $validated['tax_year_id'],
            'title' => $validated['title'] ?: $this->file->getClientOriginalName(),
            'original_name' => $this->file->getClientOriginalName(),
            'stored_path' => $path,
            'storage_disk' => $disk,
            'mime_type' => $this->file->getClientMimeType(),
            'size' => $this->file->getSize(),
            'is_legal' => $validated['is_legal'] ?? false,
        ]);

        $this->syncTags($document, $validated['tags'] ?? []);
        $this->syncLinks(
            $document,
            $validated['entity_ids'] ?? [],
            $validated['asset_ids'] ?? [],
            $validated['transaction_ids'] ?? [],
            $validated['filing_ids'] ?? []
        );

        $this->extractLegalText($document);

        $this->resetForm();
        $this->dispatch('document-saved');
        session()->flash('message', __('Document uploaded successfully.'));
    }

    public function render(): View
    {
        $documentsQuery = Document::query()
            ->where('user_id', auth()->id())
            ->with(['tags', 'jurisdiction', 'taxYear', 'entities', 'assets', 'transactions', 'filings'])
            ->latest();

        if ($this->filterJurisdictionId !== '') {
            $documentsQuery->where('jurisdiction_id', $this->filterJurisdictionId);
        }

        if ($this->filterTaxYearId !== '') {
            $documentsQuery->where('tax_year_id', $this->filterTaxYearId);
        }

        if ($this->search !== '') {
            $search = $this->search;
            $documentsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('extracted_text', 'like', "%{$search}%");
            });
        }

        return view('components.management.⚡documents.documents', [
            'documents' => $documentsQuery->paginate(12),
            'jurisdictions' => Jurisdiction::query()->orderBy('name')->get(),
            'taxYears' => TaxYear::query()->with('jurisdiction')->orderByDesc('year')->get(),
            'entities' => Entity::query()
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get(),
            'assets' => Asset::query()
                ->whereHas('entity', fn ($query) => $query->where('user_id', auth()->id()))
                ->orderBy('name')
                ->get(),
            'transactions' => Transaction::query()
                ->whereHas('account.entity', fn ($query) => $query->where('user_id', auth()->id()))
                ->latest('transaction_date')
                ->limit(50)
                ->get(),
            'filings' => Filing::query()
                ->where('user_id', auth()->id())
                ->with(['filingType', 'taxYear'])
                ->orderByDesc('created_at')
                ->get(),
        ])->layout('layouts.app', [
            'title' => __('Documents'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $tags = $this->normalizedTags();

        return [
            'user_id' => auth()->id(),
            'file' => $this->file,
            'title' => $this->title !== '' ? $this->title : null,
            'jurisdiction_id' => $this->jurisdiction_id,
            'tax_year_id' => $this->tax_year_id,
            'is_legal' => $this->is_legal,
            'tags' => $tags !== [] ? $tags : null,
            'entity_ids' => $this->normalizedIds($this->entityIds),
            'asset_ids' => $this->normalizedIds($this->assetIds),
            'transaction_ids' => $this->normalizedIds($this->transactionIds),
            'filing_ids' => $this->normalizedIds($this->filingIds),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rulesForSave(array $data): array
    {
        $request = StoreDocumentRequest::create('/', 'POST', $data);

        return $request->rules();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function messagesForSave(array $data): array
    {
        $request = StoreDocumentRequest::create('/', 'POST', $data);

        return $request->messages();
    }

    /**
     * @return array<int, string>
     */
    private function normalizedTags(): array
    {
        if ($this->tagInput === '') {
            return [];
        }

        return collect(explode(',', $this->tagInput))
            ->map(fn (string $tag) => trim($tag))
            ->filter(fn (string $tag) => $tag !== '')
            ->map(fn (string $tag) => Str::of($tag)->replaceMatches('/\s+/', ' ')->toString())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normalizedIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $entityIds
     * @param  array<int, int>  $assetIds
     * @param  array<int, int>  $transactionIds
     * @param  array<int, int>  $filingIds
     */
    private function guardLinkedRecords(array $entityIds, array $assetIds, array $transactionIds, array $filingIds): void
    {
        $userId = auth()->id();

        if ($entityIds !== []) {
            $count = Entity::query()
                ->where('user_id', $userId)
                ->whereIn('id', $entityIds)
                ->count();

            if ($count !== count($entityIds)) {
                abort(403);
            }
        }

        if ($assetIds !== []) {
            $count = Asset::query()
                ->whereHas('entity', fn ($query) => $query->where('user_id', $userId))
                ->whereIn('id', $assetIds)
                ->count();

            if ($count !== count($assetIds)) {
                abort(403);
            }
        }

        if ($transactionIds !== []) {
            $count = Transaction::query()
                ->whereHas('account.entity', fn ($query) => $query->where('user_id', $userId))
                ->whereIn('id', $transactionIds)
                ->count();

            if ($count !== count($transactionIds)) {
                abort(403);
            }
        }

        if ($filingIds !== []) {
            $count = Filing::query()
                ->where('user_id', $userId)
                ->whereIn('id', $filingIds)
                ->count();

            if ($count !== count($filingIds)) {
                abort(403);
            }
        }
    }

    /**
     * @param  array<int, string>  $tags
     */
    private function syncTags(Document $document, array $tags): void
    {
        if ($tags === []) {
            $document->tags()->sync([]);

            return;
        }

        $tagIds = collect($tags)
            ->map(fn (string $tag) => DocumentTag::firstOrCreate([
                'user_id' => auth()->id(),
                'name' => $tag,
            ])->id)
            ->all();

        $document->tags()->sync($tagIds);
    }

    /**
     * @param  array<int, int>  $entityIds
     * @param  array<int, int>  $assetIds
     * @param  array<int, int>  $transactionIds
     * @param  array<int, int>  $filingIds
     */
    private function syncLinks(Document $document, array $entityIds, array $assetIds, array $transactionIds, array $filingIds): void
    {
        $document->entities()->sync($entityIds);
        $document->assets()->sync($assetIds);
        $document->transactions()->sync($transactionIds);
        $document->filings()->sync($filingIds);
    }

    private function extractLegalText(Document $document): void
    {
        if (! $document->is_legal) {
            return;
        }

        try {
            $extractor = app(DocumentTextExtractor::class);
            $path = Storage::disk($document->storage_disk)->path($document->stored_path);
            $text = $extractor->extractText($path);

            if (trim($text) !== '') {
                $document->update(['extracted_text' => $text]);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'file',
            'title',
            'jurisdiction_id',
            'tax_year_id',
            'is_legal',
            'tagInput',
            'entityIds',
            'assetIds',
            'transactionIds',
            'filingIds',
        ]);

        $this->resetValidation();
    }
};
