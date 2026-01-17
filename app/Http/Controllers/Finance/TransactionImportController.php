<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ImportTransactionsRequest;
use App\Models\Account;
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

        // Parse the CSV using the uploaded file path directly
        $parsed = $this->importService->parseCSV($file->getRealPath(), $parserType);
        $matched = $this->importService->matchTransactions($parsed, $account);

        return response()->json($matched);
    }

    /**
     * Confirm and import transactions.
     */
    public function store(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'parser_type' => 'required|string|in:santander,mercury,bancolombia',
        ]);

        $file = $request->file('file');
        $parserType = $request->input('parser_type');

        // Parse CSV using the uploaded file path directly
        $parsed = $this->importService->parseCSV($file->getRealPath(), $parserType);
        $matchResult = $this->importService->matchTransactions($parsed, $account);

        // Import only non-duplicate transactions
        $imported = $this->importService->importTransactions(
            $matchResult['unmatched'],
            $account,
            $parserType
        );

        return response()->json([
            'imported' => $imported,
        ]);
    }

    /**
     * Get available parsers.
     */
    public function getParsers(): JsonResponse
    {
        return response()->json([
            'parsers' => $this->importService->getAvailableParsers(),
        ]);
    }
}
