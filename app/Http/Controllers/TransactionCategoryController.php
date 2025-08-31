<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionCategoryRequest;
use App\Http\Requests\UpdateTransactionCategoryRequest;
use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
    {
        $transactionCategories = TransactionCategory::orderBy('name', 'asc')->paginate(100000);
        return Inertia::render('Categories/Index', compact('transactionCategories'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Categories/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionCategoryRequest $request)
    {
        $formData = $request->validated();
        TransactionCategory::create($formData);

        return redirect()
            ->route('transaction-categories.index')
            ->with('success', 'Transaction Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionCategory $transaction_category)
    {
        return Inertia::render('Categories/Show', compact('transaction_category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionCategory $transaction_category)
    {
        return Inertia::render('Categories/Edit', compact('transaction_category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transaction_category)
    {
        $formData = $request->validated();
        $transaction_category->update($formData);

        return redirect()
            ->route('transaction-categories.index')
            ->with('success', 'Transaction category updated successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionCategory $transaction_category)
    {
        $transaction_category->delete();

        return redirect()
            ->route('transaction-categories.index')
            ->with('success', 'Transaction category deleted successfully');
    }
}
