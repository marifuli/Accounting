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
use Illuminate\Validation\ValidationException;


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

        $asset_accounts = Account::select('id', 'name', 'currency', 'current_balance')
            ->orderBy('name')->get();



        $exp_inc_accounts = ExpenseIncomeAccount::select('id', 'name', 'current_balance', 'currency')
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
        $data = $request->validated();

        // ---- Attachments (optional, public disk) ----
        $paths = [];
        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('transactions', 'public'); // ensure: php artisan storage:link
                }
            }
        }
        $data['attachments'] = $paths ?: null;

        // ---- Pull fee payloads out of the base transaction data ----
        $sourceFees = $data['source_fees'] ?? [];
        $destFees   = $data['dest_fees'] ?? [];
        unset($data['source_fees'], $data['dest_fees']);

        // ---- Helpers ----
        $toDec = static fn($v) => round((float)$v, 2);
        $sendTotal    = $toDec($data['send_total_amount'] ?? 0);
        $receiveTotal = $toDec($data['receive_total_amount'] ?? 0);

        DB::transaction(function () use (&$data, $sourceFees, $destFees, $toDec, $sendTotal, $receiveTotal) {

            // 1) Create the Transaction
            /** @var \App\Models\Transaction $tx */
            $tx = Transaction::create($data);

            // 2) Store Fees (if provided)
            foreach ($sourceFees as $f) {
                if (!isset($f['name'], $f['amount'])) continue;
                TransactionFee::create([
                    'transaction_id' => $tx->id,
                    'name'           => (string)$f['name'],
                    'amount'         => $toDec($f['amount']),
                    'type'           => 'from',
                ]);
            }
            foreach ($destFees as $f) {
                if (!isset($f['name'], $f['amount'])) continue;
                TransactionFee::create([
                    'transaction_id' => $tx->id,
                    'name'           => (string)$f['name'],
                    'amount'         => $toDec($f['amount']),
                    'type'           => 'to',
                ]);
            }

            // 3) Balance updates (row-level locks to avoid race conditions)
            $type = $data['type'] ?? 'asset';

            $lock = static function (string $model, $id) {
                return $model::query()->lockForUpdate()->findOrFail($id);
            };

            if ($type === 'income') {
                // From: ExpenseIncomeAccount  -> To: Account
                /** @var ExpenseIncomeAccount $source */
                $source = $lock(ExpenseIncomeAccount::class, $data['from_account_id']);
                /** @var Account $dest */
                $dest   = $lock(Account::class, $data['to_account_id']);

                if ($toDec($source->current_balance) - $sendTotal < 0) {
                    throw ValidationException::withMessages([
                        'from_account_id' => 'Insufficient balance in the source account.',
                    ]);
                }

                // source pays send_total, dest receives receive_total
                $source->current_balance = $toDec($source->current_balance) - $sendTotal;
                $dest->current_balance   = $toDec($dest->current_balance)   + $receiveTotal;

                $source->save();
                $dest->save();

                // If your transactions table has currency columns you could do:
                // $tx->update(['send_currency' => $source->currency, 'receive_currency' => $dest->currency]);

            } elseif ($type === 'expense') {
                // From: Account  -> To: ExpenseIncomeAccount
                /** @var Account $source */
                $source = $lock(Account::class, $data['from_account_id']);
                /** @var ExpenseIncomeAccount $dest */
                $dest   = $lock(ExpenseIncomeAccount::class, $data['to_account_id']);

                if ($toDec($source->current_balance) - $sendTotal < 0) {
                    throw ValidationException::withMessages([
                        'from_account_id' => 'Insufficient balance in the source account.',
                    ]);
                }

                $source->current_balance = $toDec($source->current_balance) - $sendTotal;
                $dest->current_balance   = $toDec($dest->current_balance)   + $receiveTotal;

                $source->save();
                $dest->save();
            } else { // 'asset'
                // From: Account  -> To: Account
                /** @var Account $source */
                $source = $lock(Account::class, $data['from_account_id']);
                /** @var Account $dest */
                $dest   = $lock(Account::class, $data['to_account_id']);

                if ($toDec($source->current_balance) - $sendTotal < 0) {
                    throw ValidationException::withMessages([
                        'from_account_id' => 'Insufficient balance in the source account.',
                    ]);
                }

                $source->current_balance = $toDec($source->current_balance) - $sendTotal;
                $dest->current_balance   = $toDec($dest->current_balance)   + $receiveTotal;

                $source->save();
                $dest->save();
            }
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction created and balances updated.');
    }


    /**
     * GET /transactions/{transactiono}
     *
     * NOTE: route parameter is "transactiono" because the resource
     * name is "transactions". This ensures implicit binding works.
     */
    public function show(Transaction $transaction) // <-- rename here
    {
        // category for label
        $transaction->load(['category:id,name']);

        // Resolve endpoints by type
        $fromAccount   = null;
        $fromEiAccount = null;
        $toAccount     = null;
        $toEiAccount   = null;

        if ($transaction->type === 'income') {
            $fromEiAccount = ExpenseIncomeAccount::select('id', 'name', 'currency', 'current_balance')
                ->find($transaction->from_account_id);
            $toAccount = Account::select('id', 'name', 'currency', 'current_balance')
                ->find($transaction->to_account_id);
        } elseif ($transaction->type === 'expense') {
            $fromAccount = Account::select('id', 'name', 'currency', 'current_balance')
                ->find($transaction->from_account_id);
            $toEiAccount = ExpenseIncomeAccount::select('id', 'name', 'currency', 'current_balance')
                ->find($transaction->to_account_id);
        } else { // 'asset'
            $fromAccount = Account::select('id', 'name', 'currency', 'current_balance')
                ->find($transaction->from_account_id);
            $toAccount = Account::select('id', 'name', 'currency', 'current_balance')
                ->find($transaction->to_account_id);
        }

        // Split fees
        $fees = TransactionFee::select('name', 'amount', 'type')
            ->where('transaction_id', $transaction->id)
            ->get();

        $sourceFees = $fees->where('type', 'from')->values()->map(fn($f) => [
            'name' => $f->name,
            'amount' => (float)$f->amount,
        ]);
        $destFees = $fees->where('type', 'to')->values()->map(fn($f) => [
            'name' => $f->name,
            'amount' => (float)$f->amount,
        ]);

        return Inertia::render('transactions/Show', [
            'transaction' => [
                'id'   => $transaction->id,
                'type' => $transaction->type,
                'name' => $transaction->name,
                'category' => $transaction->category
                    ? ['id' => $transaction->category->id, 'name' => $transaction->category->name]
                    : null,

                'from_account' => $fromAccount ? [
                    'id' => $fromAccount->id,
                    'name' => $fromAccount->name,
                    'currency' => $fromAccount->currency,
                    'current_balance' => (float)$fromAccount->current_balance,
                ] : null,

                'from_ei_account' => $fromEiAccount ? [
                    'id' => $fromEiAccount->id,
                    'name' => $fromEiAccount->name,
                    'currency' => $fromEiAccount->currency,
                    'current_balance' => (float)$fromEiAccount->current_balance,
                ] : null,

                'to_account' => $toAccount ? [
                    'id' => $toAccount->id,
                    'name' => $toAccount->name,
                    'currency' => $toAccount->currency,
                    'current_balance' => (float)$toAccount->current_balance,
                ] : null,

                'to_ei_account' => $toEiAccount ? [
                    'id' => $toEiAccount->id,
                    'name' => $toEiAccount->name,
                    'currency' => $toEiAccount->currency,
                    'current_balance' => (float)$toEiAccount->current_balance,
                ] : null,

                'send_actual_amount'    => (float)$transaction->send_actual_amount,
                'send_total_amount'     => (float)$transaction->send_total_amount,
                'receive_actual_amount' => (float)$transaction->receive_actual_amount,
                'receive_total_amount'  => (float)$transaction->receive_total_amount,

                'description' => $transaction->description,
                'attachments' => is_array($transaction->attachments) ? $transaction->attachments : [],

                'source_fees' => $sourceFees,
                'dest_fees'   => $destFees,

                'created_at' => optional($transaction->created_at)->toIso8601String(),
                'updated_at' => optional($transaction->updated_at)->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /transactions/{transactiono}/edit
     */
    public function edit(Transaction $transaction)
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
