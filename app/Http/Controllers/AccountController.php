<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        // If CVV/PIN are left blank on the form, do not overwrite existing values
        foreach (['card_cvv', 'card_pin'] as $secret) {
            if (!array_key_exists($secret, $data) || is_null($data[$secret]) || $data[$secret] === '') {
                unset($data[$secret]);
            }
        }

        // Avoid unintentionally nulling balances when inputs are left empty
        foreach (['opening_balance', 'current_balance'] as $balanceField) {
            if (array_key_exists($balanceField, $data) && $data[$balanceField] === null) {
                unset($data[$balanceField]);
            }
        }

        // If current_balance not provided but opening_balance changed AND current is empty,
        // you can choose to sync it (comment out if you prefer not to):
        // if (!array_key_exists('current_balance', $data) && array_key_exists('opening_balance', $data) && (float)$account->current_balance === 0.0) {
        //     $data['current_balance'] = $data['opening_balance'];
        // }

        $account->update($data);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        $account->delete();
        return redirect()->route('accounts.index')->with('204', 'Account deleted successfully.');
    }
}
