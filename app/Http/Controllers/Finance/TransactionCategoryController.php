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
        $categories = TransactionCategory::query()
            ->with(['taxMappings'])
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
            new TransactionCategoryResource($category->load(['taxMappings'])),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionCategory $transactionCategory): JsonResponse
    {
        return response()->json(
            new TransactionCategoryResource($transactionCategory->load(['taxMappings']))
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transactionCategory): JsonResponse
    {
        $transactionCategory->update($request->validated());

        return response()->json(
            new TransactionCategoryResource($transactionCategory->load(['taxMappings']))
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionCategory $transactionCategory): JsonResponse
    {
        if ($transactionCategory->transactions()->exists()) {
            return response()->json(
                ['message' => 'Cannot delete category with existing transactions'],
                422
            );
        }

        $transactionCategory->delete();

        return response()->json(status: 204);
    }
}
