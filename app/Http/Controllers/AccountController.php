<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction; 
use Illuminate\Http\RedirectResponse;


class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $accounts = Account::paginate(10);
        return Inertia::render('Accounts/Index', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Accounts/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountRequest $request)
    {
        $data = $request->validated();

        // Ensure balances are set sensibly
        $data['opening_balance']  = $data['opening_balance']  ?? 0;
        $data['current_balance']  = $data['current_balance']  ?? $data['opening_balance'];

        // (Optional) if currency somehow missing, fall back to migration default
        $data['currency'] = $data['currency'] ?? 'USD';

        $account = Account::create($data);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(Account $account)
    {
        return Inertia::render('Accounts/Show', compact('account'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account)
    {
        return Inertia::render('Accounts/Edit', compact('account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountRequest $request, Account $account)
    {
        $data = $request->validated();

        // Keep currency if omitted (defensive)
        $data['currency'] = $data['currency'] ?? $account->currency;

        // No conditional nulling — whatever the user filled stays.
        $account->fill($data)->save();

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Account $account): RedirectResponse
    {
        $isUsed = Transaction::where(function ($q) use ($account) {
            $q->where(fn($qq) => $qq->where('type', 'inc')->where('to_account_id', $account->id))
              ->orWhere(fn($qq) => $qq->where('type', 'exp')->where('from_account_id', $account->id))
              ->orWhere(fn($qq) => $qq->where('type', 'asset')
                    ->where(function ($q2) use ($account) {
                        $q2->where('from_account_id', $account->id)
                           ->orWhere('to_account_id', $account->id);
                    }));
        })->exists();

        if ($isUsed) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'Account cannot be deleted because it is used in one or more transactions.');
        }

        $account->delete();

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
