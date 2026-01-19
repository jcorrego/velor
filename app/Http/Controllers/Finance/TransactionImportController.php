<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\ImportBatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ImportTransactionsRequest;
use App\Models\Account;
use App\Models\ImportBatch;
use App\Services\Finance\TransactionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionImportController extends Controller
{
    public function __construct(private TransactionImportService $importService) {}

    /**
     * Preview CSV import - parse and match against existing transactions.
     */
    public function preview(ImportTransactionsRequest $request, Account $account): JsonResponse
    {
        $file = $request->file('file');
        $parserType = $request->input('parser_type', 'santander');

        try {
            $parsed = $this->parseTransactions($file->getRealPath(), $account->id, $parserType);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $matched = $this->importService->matchTransactions($parsed, $account);

        return response()->json($matched);
    }

    /**
     * Confirm and create import batch for review (instead of directly importing).
     */
    public function store(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt,pdf|max:5120',
            'parser_type' => 'required|string|in:santander,mercury,bancolombia',
        ]);

        $file = $request->file('file');
        $parserType = $request->input('parser_type');

        try {
            $parsed = $this->parseTransactions($file->getRealPath(), $account->id, $parserType);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $matchResult = $this->importService->matchTransactions($parsed, $account);

        // Create import batch with parsed transactions for review
        $batch = ImportBatch::create([
            'account_id' => $account->id,
            'status' => ImportBatchStatus::Pending,
            'proposed_transactions' => $matchResult['unmatched'],
            'transaction_count' => count($matchResult['unmatched']),
        ]);

        return response()->json([
            'batch_id' => $batch->id,
            'transaction_count' => count($matchResult['unmatched']),
            'duplicates' => $matchResult['duplicates'],
            'message' => 'Import batch created successfully. Please review and approve in the Import Review queue.',
        ]);
    }

    /**
     * Get available parsers.
     */
    public function getParsers(): JsonResponse
    {
        return response()->json([
            'parsers' => $this->importService->getAvailableParsers(),
            'pdf_parsers' => $this->importService->getAvailablePdfParsers(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseTransactions(string $filePath, int $accountId, string $parserType): array
    {
        if ($this->isPdfFile($filePath)) {
            return $this->importService->parsePDF($filePath, $accountId, $parserType);
        }

        return $this->importService->parseCSV($filePath, $parserType);
    }

    private function isPdfFile(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf';
    }
}
