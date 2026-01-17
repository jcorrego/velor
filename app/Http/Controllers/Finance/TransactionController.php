<?php

namespace App\Http\Controllers\Finance;

use App\Finance\Services\FxRateService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransactionRequest;
use App\Http\Requests\Finance\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Currency;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private FxRateService $fxRateService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->filterByUser(Transaction::query());

        if ($request->has('account_id')) {
            $query->where('account_id', $request->input('account_id'));
        }

        $transactions = $query
            ->with(['account', 'category', 'originalCurrency', 'convertedCurrency'])
            ->paginate(50);

        return TransactionResource::collection($transactions)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $originalCurrency = Currency::findOrFail($validated['original_currency_id']);
        $convertedCurrencyId = $validated['converted_currency_id'] ?? $validated['original_currency_id'];
        $convertedCurrency = Currency::findOrFail($convertedCurrencyId);

        $transactionDate = Carbon::parse($request->input('transaction_date'));
        $fxRate = $this->fxRateService->getRate(
            $originalCurrency,
            $convertedCurrency,
            $transactionDate
        );

        $validated['fx_rate'] = $fxRate;
        $validated['converted_amount'] = $validated['original_amount'] * $fxRate;
        $validated['fx_source'] = 'ecb';

        if (! isset($validated['converted_currency_id'])) {
            $validated['converted_currency_id'] = $validated['original_currency_id'];
        }

        $transaction = Transaction::create($validated);

        return response()->json(
            new TransactionResource($transaction->load(['account', 'category', 'originalCurrency', 'convertedCurrency'])),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $this->ensureUserOwnsTransaction($transaction);

        return response()->json(
            new TransactionResource($transaction->load(['account', 'category', 'originalCurrency', 'convertedCurrency']))
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $this->ensureUserOwnsTransaction($transaction);

        $transaction->update($request->validated());

        return response()->json(
            new TransactionResource($transaction->load(['account', 'category', 'originalCurrency', 'convertedCurrency']))
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->ensureUserOwnsTransaction($transaction);

        $transaction->delete();

        return response()->json(status: 204);
    }

    /**
     * Mark transaction as reconciled.
     */
    public function reconcile(Transaction $transaction): JsonResponse
    {
        $this->ensureUserOwnsTransaction($transaction);

        $transaction->update(['reconciled_at' => now()]);

        return response()->json(
            $transaction->load(['account', 'category', 'originalCurrency', 'convertedCurrency'])
        );
    }

    /**
     * Filter transactions by authenticated user.
     */
    private function filterByUser($query)
    {
        return $query->whereHas('account.entity', function ($q) {
            $q->where('user_id', auth()->id());
        });
    }

    /**
     * Ensure the authenticated user owns the transaction.
     */
    private function ensureUserOwnsTransaction(Transaction $transaction): void
    {
        if ($transaction->account->entity->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
