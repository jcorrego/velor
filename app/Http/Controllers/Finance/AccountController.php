<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreAccountRequest;
use App\Http\Requests\Finance\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $accounts = $this->filterByUser(Account::query())
            ->with(['currency', 'entity', 'transactions'])
            ->paginate(15);

        return response()->json($accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = Account::create($request->validated());

        return response()->json(
            $account->load(['currency', 'entity']),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account): JsonResponse
    {
        $this->ensureUserOwnsAccount($account);

        return response()->json(
            $account->load(['currency', 'entity', 'transactions'])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $this->ensureUserOwnsAccount($account);

        $account->update($request->validated());

        return response()->json(
            $account->load(['currency', 'entity'])
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account): JsonResponse
    {
        $this->ensureUserOwnsAccount($account);

        $account->delete();

        return response()->json(status: 204);
    }

    /**
     * Filter accounts by authenticated user.
     */
    private function filterByUser($query)
    {
        return $query->whereHas('entity', function ($q) {
            $q->where('user_id', auth()->id());
        });
    }

    /**
     * Ensure the authenticated user owns the account.
     */
    private function ensureUserOwnsAccount(Account $account): void
    {
        if ($account->entity->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
