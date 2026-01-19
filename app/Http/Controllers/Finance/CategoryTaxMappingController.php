<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCategoryTaxMappingRequest;
use App\Http\Resources\CategoryTaxMappingResource;
use App\Models\CategoryTaxMapping;
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
        $query = CategoryTaxMapping::query();

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
        $categoryTaxMapping->delete();

        return response()->json(status: 204);
    }
}
