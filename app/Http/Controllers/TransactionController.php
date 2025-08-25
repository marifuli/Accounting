<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Account;
use App\Models\ExpenseIncomeAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionFee;


class TransactionController extends Controller
{
    /**
     * GET /transactions
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

        // View folder suggestion: resources/js/Pages/transactions/Index.vue
        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * GET /transactions/create
     */
    public function create()
    {
        $categories = TransactionCategory::select('id', 'name')
            ->orderBy('name')->get();

        $asset_accounts = Account::select('id', 'name')
            ->orderBy('name')->get();
        
        $exp_inc_accounts = ExpenseIncomeAccount::select('id', 'name')
            ->orderBy('name')->get();

        // resources/js/Pages/transactions/Create.vue
        return Inertia::render('transactions/Create', [
            'categories'        => $categories,
            'accounts'          => $asset_accounts,
            'exp_inc_accounts'   => $exp_inc_accounts,
        ]);
    }

    /**
     * POST /transactions
     */
    public function store(StoreTransactionRequest $request)
    {
        DB::beginTransaction();

        try {
            $formData = $request->validated();
            // dd($formData);
            // Normalize nullable FKs
            foreach (['category_id', 'from_account_id', 'to_account_id'] as $fk) {
                if (empty($formData[$fk])) {
                    $formData[$fk] = null;
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
            $formData['attachments'] = $paths ?: null;

            // Create the transaction
            $transaction = Transaction::create($formData);

            // Save fees (expecting arrays like fees_from[0][name], fees_from[0][amount], and same for fees_to)
            $feesFrom = (array) $request->input('source_fees', []);
            $feesTo   = (array) $request->input('dest_fees', []);

            $now = now();
            $feeRows = [];
            // dd($feesFrom);
            foreach ($feesFrom as $row) {
                $name   = trim((string)($row['name'] ?? ''));
                $amount = (float) ($row['amount'] ?? 0);
                if ($name !== '' && $amount > 0) {
                    $feeRows[] = [
                        'transaction_id' => $transaction->id,
                        'name'           => $name,
                        'amount'         => $amount,
                        'type'           => 'from',
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }

            foreach ($feesTo as $row) {
                $name   = trim((string)($row['name'] ?? ''));
                $amount = (float) ($row['amount'] ?? 0);
                if ($name !== '' && $amount > 0) {
                    $feeRows[] = [
                        'transaction_id' => $transaction->id,
                        'name'           => $name,
                        'amount'         => $amount,
                        'type'           => 'to',
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }

            if (!empty($feeRows)) {
                // \Log::info('Transaction Fees inserting.');
                TransactionFee::insert($feeRows);
            }

            DB::commit();

            return redirect()
                ->route('transactions.index')
                ->with('success', 'Transaction created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['message' => 'Failed to create transaction.'])
                ->withInput();
        }
    }


    /**
     * GET /transactions/{transactiono}
     *
     * NOTE: route parameter is "transactiono" because the resource
     * name is "transactions". This ensures implicit binding works.
     */
    public function show(Transaction $transactiono)
    {
        $transactiono->load([
            'category:id,name',
            'fromAccount:id,name',
            'toAccount:id,name',
        ]);

        // resources/js/Pages/transactions/Show.vue
        return Inertia::render('transactions/Show', [
            'transaction' => $transactiono,
        ]);
    }

    /**
     * GET /transactions/{transactiono}/edit
     */
    public function edit(Transaction $transactiono)
    {
        $categories = TransactionCategory::select('id', 'name')
            ->orderBy('name')->get();

        $accounts = Account::select('id', 'name')
            ->orderBy('name')->get();

        // resources/js/Pages/transactions/Edit.vue
        return Inertia::render('transactions/Edit', [
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
     * PUT/PATCH /transactions/{transactiono}
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
            ->route('transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * DELETE /transactions/{transactiono}
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
            ->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
