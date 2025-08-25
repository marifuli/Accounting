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

        // --- Normalize "code" (uppercased, space -> dash) ---
        if (array_key_exists('code', $data)) {
            $data['code'] = strtoupper(str_replace(' ', '-', (string) $data['code']));
        }

        // --- Normalize currency to allowed set (uppercased) ---
        if (array_key_exists('currency', $data)) {
            $allowed = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'INR', 'BRL', 'ZAR', 'BDT', 'other'];
            $cur = strtoupper((string) $data['currency']);
            $data['currency'] = in_array($cur, $allowed, true) ? $cur : 'other';
        }

        // --- Normalize booleans ---
        // If checkbox is missing, default to false (or keep existing if you prefer)
        $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : false;

        // --- Decimals: coerce to 2dp if provided; don't null out on blank strings ---
        foreach (['opening_balance', 'current_balance'] as $balanceField) {
            if (array_key_exists($balanceField, $data)) {
                if ($data[$balanceField] === '' || $data[$balanceField] === null) {
                    unset($data[$balanceField]); // keep existing DB value
                } else {
                    $data[$balanceField] = round((float) $data[$balanceField], 2);
                }
            }
        }

        // --- CVV/PIN: if blank, do not overwrite existing values ---
        foreach (['card_cvv', 'card_pin'] as $secret) {
            if (!array_key_exists($secret, $data) || $data[$secret] === null || $data[$secret] === '') {
                unset($data[$secret]);
            }
        }

        // --- When the account is not a card, clear card-only fields on the DB side ---
        if (array_key_exists('type', $data) && $data['type'] !== 'card') {
            $data['card_type'] = null;
            $data['card_valid_from'] = null;
            $data['card_expiry'] = null;
            // Secrets should never persist for non-card types
            $data['card_cvv'] = null;
            $data['card_pin'] = null;
        }

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
