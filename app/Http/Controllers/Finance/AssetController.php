<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreAssetRequest;
use App\Http\Requests\Finance\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Http\Resources\AssetValuationResource;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $assets = $this->filterByUser(Asset::query())
            ->with(['jurisdiction', 'entity', 'acquisitionCurrency', 'valuations'])
            ->paginate(15);

        return AssetResource::collection($assets)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = Asset::create($request->validated());

        return response()->json(
            new AssetResource($asset->load(['jurisdiction', 'entity', 'acquisitionCurrency', 'valuations'])),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Asset $asset): JsonResponse
    {
        $this->ensureUserOwnsAsset($asset);

        return response()->json(
            new AssetResource($asset->load(['jurisdiction', 'entity', 'acquisitionCurrency', 'valuations']))
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        $this->ensureUserOwnsAsset($asset);

        $asset->update($request->validated());

        return response()->json(
            new AssetResource($asset->load(['jurisdiction', 'entity', 'acquisitionCurrency', 'valuations']))
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $asset): JsonResponse
    {
        $this->ensureUserOwnsAsset($asset);

        $asset->delete();

        return response()->json(status: 204);
    }

    /**
     * Get all valuations for an asset.
     */
    public function valuations(Asset $asset): AnonymousResourceCollection
    {
        $this->ensureUserOwnsAsset($asset);

        $valuations = $asset->valuations()->paginate(15);

        return AssetValuationResource::collection($valuations);
    }

    /**
     * Filter assets by authenticated user.
     */
    private function filterByUser($query)
    {
        return $query->whereHas('entity', function ($q) {
            $q->where('user_id', auth()->id());
        });
    }

    /**
     * Ensure the authenticated user owns the asset.
     */
    private function ensureUserOwnsAsset(Asset $asset): void
    {
        if ($asset->entity->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
