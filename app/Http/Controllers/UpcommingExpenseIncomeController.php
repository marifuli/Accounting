<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUpcommingExpenseIncomeRequest;
use App\Http\Requests\UpdateUpcommingExpenseIncomeRequest;
use App\Models\ExpenseIncomeAccount;
use App\Models\UpcommingExpenseIncome;
use Inertia\Inertia;

class UpcommingExpenseIncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $upcommingExpIncs = UpcommingExpenseIncome::paginate(15);

        return Inertia::render('UpcommingExpInc/Index', compact('upcommingExpIncs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $expIncAccounts = ExpenseIncomeAccount::all();
        return Inertia::render('UpcommingExpInc/Create', compact('expIncAccounts'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUpcommingExpenseIncomeRequest $request)
    {
        $data = $request->validated();

        // Normalize FK (avoid empty string -> null)
        $data['eia_id'] = $request->filled('eia_id') ? (int) $request->input('eia_id') : null;

        // ---- Save files directly here (no helper) ----
        $paths = [];

        // May be an array (multiple) or a single UploadedFile depending on the client
        $files = $request->file('attachments', []);

        // Normalize to array
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    // Store to storage/app/public/upcoming and return "upcoming/xxx.ext"
                    $paths[] = $file->store('upcoming', 'public');
                }
            }
        }

        // Save paths as JSON array (your model casts attachments => array)
        $data['attachments'] = $paths ?: null;

        \App\Models\UpcommingExpenseIncome::create($data);

        return redirect()
            ->route('upcomming-expense-income.index')
            ->with('success', 'Upcoming income/expense created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(UpcommingExpenseIncome $upcomming_expense_income)
    {
        return Inertia::render('UpcommingExpInc/Show', compact('upcomming_expense_income'));
    }

    /**
     * Show the form for editing the specified resource.
     */
 public function edit(UpcommingExpenseIncome $upcomming_expense_income)
{
    // Provide accounts for the <select>
    $expIncAccounts = ExpenseIncomeAccount::select('id', 'name')
        ->orderBy('name')
        ->get();

    return Inertia::render('UpcommingExpInc/Edit', [
        'upcomming_expense_income' => $upcomming_expense_income,
        'expIncAccounts'           => $expIncAccounts,
    ]);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateUpcommingExpenseIncomeRequest $request,
        UpcommingExpenseIncome $upcomming_expense_income
    ) {
        $data = $request->validated();

        // Normalize nullable FK
        if (empty($data['eia_id'])) {
            $data['eia_id'] = null;
        }

        // Optional new files — if provided, append to existing
        $newPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $newPaths[] = $file->store('upcoming', 'public');
                }
            }
        }

        if (!empty($newPaths)) {
            $existing = (array) ($upcomming_expense_income->attachments ?? []);
            $data['attachments'] = array_values(array_unique(array_merge($existing, $newPaths)));
        } else {
            // Don’t overwrite attachments if none uploaded
            unset($data['attachments']);
        }

        $upcomming_expense_income->update($data);

        return redirect()
            ->route('upcomming-expense-income.index')
            ->with('success', 'Upcoming item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UpcommingExpenseIncome $upcomming_expense_income)
    {
        // (Optional) remove stored files; comment these 3 lines if you don’t want to delete files
        foreach ((array) $upcomming_expense_income->attachments as $path) {
            if ($path) Storage::disk('public')->delete($path);
        }

        $upcomming_expense_income->delete();

        return redirect()
            ->route('upcomming-expense-income.index')
            ->with('success', 'Upcoming item deleted.');
    }
}
