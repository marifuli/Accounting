<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    /**
     * GET /transactionos
     */
    public function index()
    {
        $transactions = Transaction::with([
                'category:id,name',
                'fromAccount:id,name',
                'toAccount:id,name',
            ])
            ->latest()
            ->paginate(15);

        // View folder suggestion: resources/js/Pages/Transactionos/Index.vue
        return Inertia::render('Transactionos/Index', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * GET /transactionos/create
     */
    public function create()
    {
        $categories = TransactionCategory::select('id', 'name')
            ->orderBy('name')->get();

        $accounts = Account::select('id', 'name')
            ->orderBy('name')->get();

        // resources/js/Pages/Transactionos/Create.vue
        return Inertia::render('Transactionos/Create', [
            'categories' => $categories,
            'accounts'   => $accounts,
        ]);
    }

    /**
     * POST /transactionos
     */
    public function store(StoreTransactionRequest $request)
    {
        $data = $request->validated();

        // Normalize nullable FKs
        foreach (['category_id', 'from_account_id', 'to_account_id'] as $fk) {
            if (empty($data[$fk])) {
                $data[$fk] = null;
            }
        }

        // Handle optional multiple files
        $paths = [];
        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    // store to public disk (make sure you ran: php artisan storage:link)
                    $paths[] = $file->store('transactions', 'public');
                }
            }
        }
        $data['attachments'] = $paths ?: null;

        Transaction::create($data);

        return redirect()
            ->route('transactionos.index')
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * GET /transactionos/{transactiono}
     *
     * NOTE: route parameter is "transactiono" because the resource
     * name is "transactionos". This ensures implicit binding works.
     */
    public function show(Transaction $transactiono)
    {
        $transactiono->load([
            'category:id,name',
            'fromAccount:id,name',
            'toAccount:id,name',
        ]);

        // resources/js/Pages/Transactionos/Show.vue
        return Inertia::render('Transactionos/Show', [
            'transaction' => $transactiono,
        ]);
    }

    /**
     * GET /transactionos/{transactiono}/edit
     */
    public function edit(Transaction $transactiono)
    {
        $categories = TransactionCategory::select('id', 'name')
            ->orderBy('name')->get();

        $accounts = Account::select('id', 'name')
            ->orderBy('name')->get();

        // resources/js/Pages/Transactionos/Edit.vue
        return Inertia::render('Transactionos/Edit', [
            'transaction' => $transactiono->load([
                'category:id,name',
                'fromAccount:id,name',
                'toAccount:id,name',
            ]),
            'categories'  => $categories,
            'accounts'    => $accounts,
        ]);
    }

    /**
     * PUT/PATCH /transactionos/{transactiono}
     */
    public function update(UpdateTransactionRequest $request, Transaction $transactiono)
    {
        $data = $request->validated();

        foreach (['category_id', 'from_account_id', 'to_account_id'] as $fk) {
            if (array_key_exists($fk, $data) && empty($data[$fk])) {
                $data[$fk] = null;
            }
        }

        // If new files are uploaded, append them to existing attachments
        if ($request->hasFile('attachments')) {
            $existing = (array) ($transactiono->attachments ?? []);
            foreach ((array) $request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $existing[] = $file->store('transactions', 'public');
                }
            }
            $data['attachments'] = $existing;
        } else {
            // Keep existing attachments if none provided in request
            unset($data['attachments']);
        }

        $transactiono->update($data);

        return redirect()
            ->route('transactionos.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * DELETE /transactionos/{transactiono}
     */
    public function destroy(Transaction $transactiono)
    {
        // (Optional) delete stored files as well
        if (is_array($transactiono->attachments)) {
            foreach ($transactiono->attachments as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $transactiono->delete();

        return redirect()
            ->route('transactionos.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
