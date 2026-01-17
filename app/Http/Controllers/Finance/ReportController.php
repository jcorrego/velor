<?php

namespace App\Http\Controllers\Finance;

use App\Finance\Services\RentalPropertyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\RentalPropertyReportRequest;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Rental property report summary (income, expenses, depreciation).
     */
    public function rentalProperty(
        RentalPropertyReportRequest $request,
        Asset $asset,
        RentalPropertyService $service
    ): JsonResponse {
        $this->ensureUserOwnsAsset($asset);

        $year = (int) $request->validated('year');

        $income = $service->getAnnualRentalIncome($asset, $year);
        $expenses = $service->getAnnualRentalExpenses($asset, $year);
        $depreciation = $service->getAnnualDepreciation($asset);
        $net = $service->calculateNetRentalIncome($asset, $year);

        return response()->json([
            'asset_id' => $asset->id,
            'year' => $year,
            'income' => $income,
            'expenses' => $expenses,
            'depreciation' => $depreciation,
            'net_income' => $net,
        ]);
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
