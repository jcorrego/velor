<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCategoryTaxMappingRequest;
use App\Http\Resources\CategoryTaxMappingResource;
use App\Models\CategoryTaxMapping;
use App\Models\TransactionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryTaxMappingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CategoryTaxMapping::query()
            ->whereHas('transactionCategory.entity', function ($query) {
                $query->where('user_id', auth()->id());
            });

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $mappings = $query
            ->with('transactionCategory')
            ->paginate(20);

        return CategoryTaxMappingResource::collection($mappings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryTaxMappingRequest $request): JsonResponse
    {
        $category = TransactionCategory::findOrFail($request->validated('category_id'));
        $this->ensureUserOwnsCategory($category, $request->user()?->id);

        $mapping = CategoryTaxMapping::create($request->validated());

        return response()->json(
            new CategoryTaxMappingResource($mapping->load('transactionCategory')),
            201
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, CategoryTaxMapping $categoryTaxMapping): JsonResponse
    {
        $this->ensureUserOwnsCategory($categoryTaxMapping->transactionCategory, $request->user()?->id);

        $categoryTaxMapping->delete();

        return response()->json(status: 204);
    }

    /**
     * Ensure the authenticated user owns the category.
     */
    private function ensureUserOwnsCategory(TransactionCategory $category, ?int $userId): void
    {
        if (! $userId || $category->entity->user_id !== $userId) {
            abort(403);
        }
    }
}
