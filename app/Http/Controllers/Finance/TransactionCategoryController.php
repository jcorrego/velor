<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransactionCategoryRequest;
use App\Http\Requests\Finance\UpdateTransactionCategoryRequest;
use App\Http\Resources\TransactionCategoryResource;
use App\Models\TransactionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->filterByUser(TransactionCategory::query());

        if ($request->has('jurisdiction_id')) {
            $query->where('jurisdiction_id', $request->input('jurisdiction_id'));
        }

        $categories = $query
            ->with(['jurisdiction', 'entity', 'taxMappings'])
            ->paginate(20);

        return TransactionCategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionCategoryRequest $request): JsonResponse
    {
        $category = TransactionCategory::create($request->validated());

        return response()->json(
            new TransactionCategoryResource($category->load(['jurisdiction', 'entity', 'taxMappings'])),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionCategory $transactionCategory): JsonResponse
    {
        $this->ensureUserOwnsCategory($transactionCategory);

        return response()->json(
            new TransactionCategoryResource($transactionCategory->load(['jurisdiction', 'entity', 'taxMappings']))
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transactionCategory): JsonResponse
    {
        $this->ensureUserOwnsCategory($transactionCategory);

        $transactionCategory->update($request->validated());

        return response()->json(
            new TransactionCategoryResource($transactionCategory->load(['jurisdiction', 'entity', 'taxMappings']))
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionCategory $transactionCategory): JsonResponse
    {
        $this->ensureUserOwnsCategory($transactionCategory);

        if ($transactionCategory->transactions()->exists()) {
            return response()->json(
                ['message' => 'Cannot delete category with existing transactions'],
                422
            );
        }

        $transactionCategory->delete();

        return response()->json(status: 204);
    }

    /**
     * Filter categories by authenticated user.
     */
    private function filterByUser($query)
    {
        return $query->whereHas('entity', function ($q) {
            $q->where('user_id', auth()->id());
        });
    }

    /**
     * Ensure the authenticated user owns the category.
     */
    private function ensureUserOwnsCategory(TransactionCategory $category): void
    {
        if ($category->entity->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
