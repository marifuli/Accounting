<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseIncomeAccountRequest;
use App\Http\Requests\UpdateExpenseIncomeAccountRequest;
use App\Models\ExpenseIncomeAccount;
use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseIncomeAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $expenseIncomeAccounts = ExpenseIncomeAccount::latest()->paginate(15);

        // IMPORTANT: this key MUST match your defineProps key 1:1
        return Inertia::render('ExpenseIncomeAccounts/Index', [
            'expenseIncomeAccounts' => $expenseIncomeAccounts,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $transactionCategories = TransactionCategory::all();
        return Inertia::render('ExpenseIncomeAccounts/Create', compact('transactionCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseIncomeAccountRequest $request)
    {
        $data = $request->validated();

        $account = ExpenseIncomeAccount::create($data);

        return redirect()
            ->route('expense-income-accounts.index')
            ->with('success', 'Expense/Income account created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseIncomeAccount $expense_income_account)
    {
        return Inertia::render('ExpenseIncomeAccounts/Show', compact('expense_income_account'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenseIncomeAccount $expenseIncomeAccount)
    {
        // Provide categories for the <select>
        $transactionCategories = TransactionCategory::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('ExpenseIncomeAccounts/Edit', [
            'expense_income_account' => $expenseIncomeAccount,
            'transactionCategories'  => $transactionCategories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseIncomeAccountRequest $request, ExpenseIncomeAccount $expenseIncomeAccount)
    {
        $data = $request->validated();

        $expenseIncomeAccount->update($data);

        return redirect()
            ->route('expense-income-accounts.index')
            ->with('success', 'Expense/Income account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseIncomeAccount $expenseIncomeAccount)
    {
        try {
            $expenseIncomeAccount->delete();

            return redirect()
                ->route('expense-income-accounts.index')
                ->with('success', 'Expense/Income account deleted successfully.');
        } catch (\Throwable $e) {
            // Optional: handle FK constraint errors, etc.
            return redirect()
                ->route('expense-income-accounts.index')
                ->with('error', 'Unable to delete this record. It may be referenced by other data.');
        }
    }
}
