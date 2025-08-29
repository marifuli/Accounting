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
    public function index(Request $request)
    {
        // Build the base query + eager loads
        $query = Transaction::with([
            'category:id,name',
            'fromAccount:id,name',
            'toAccount:id,name',
        ]);

        // Simple helper to coerce numeric inputs safely
        $num = function ($v) {
            if ($v === null || $v === '') return null;
            return is_numeric($v) ? (float)$v : null;
        };

        // ---- Filters ----
        if ($request->filled('from_account_id')) {
            $query->where('from_account_id', $request->input('from_account_id'));
        }
        if ($request->filled('to_account_id')) {
            $query->where('to_account_id', $request->input('to_account_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Amount range helpers
        $ranges = [
            'send_total_amount',
            'send_actual_amount',
            'receive_total_amount',
            'receive_actual_amount',
        ];

        foreach ($ranges as $col) {
            $min = $num($request->input("{$col}_min"));
            $max = $num($request->input("{$col}_max"));
            if (!is_null($min) && !is_null($max)) {
                // both present
                $query->whereBetween($col, [$min, $max]);
            } elseif (!is_null($min)) {
                $query->where($col, '>=', $min);
            } elseif (!is_null($max)) {
                $query->where($col, '<=', $max);
            }
        }

        $transactions = $query
            ->latest()
            ->paginate(15)
            ->appends($request->query()); // keep filters in pagination links

        // Options for selects
        $accounts   = Account::select('id', 'name')->orderBy('name')->get();
        $categories = TransactionCategory::select('id', 'name')->orderBy('name')->get();

        // Echo filters back to the page so v-models have initial values
        $filters = [
            'from_account_id'          => $request->input('from_account_id'),
            'to_account_id'            => $request->input('to_account_id'),
            'category_id'              => $request->input('category_id'),
            'send_total_amount_min'    => $request->input('send_total_amount_min'),
            'send_total_amount_max'    => $request->input('send_total_amount_max'),
            'send_actual_amount_min'   => $request->input('send_actual_amount_min'),
            'send_actual_amount_max'   => $request->input('send_actual_amount_max'),
            'receive_total_amount_min' => $request->input('receive_total_amount_min'),
            'receive_total_amount_max' => $request->input('receive_total_amount_max'),
            'receive_actual_amount_min' => $request->input('receive_actual_amount_min'),
            'receive_actual_amount_max' => $request->input('receive_actual_amount_max'),
        ];

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
            'filters'      => $filters,
            'accounts'     => $accounts,
            'categories'   => $categories,
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
    // app/Http/Controllers/TransactionController.php

    public function store(StoreTransactionRequest $request)
    {
        // dd($request->all());
        $data = $request->validated();
        // dd($data);
        // Ensure target folder exists (storage/app/public/transactions)
        $disk = Storage::disk('public');
        if (! $disk->exists('transactions')) {
            $disk->makeDirectory('transactions');
        }

        // Save all uploaded files
        $paths = [];
        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('transactions', 'public');
                }
            }
        }
        $data['attachments'] = $paths ?: null;

        // Extract fees from payload
        $sourceFees = $data['source_fees'] ?? [];
        $destFees   = $data['dest_fees'] ?? [];
        unset($data['source_fees'], $data['dest_fees']);

        // Normalize type to DB enum (inc|exp|asset)
        $data['type'] = match (strtolower($data['type'] ?? 'asset')) {
            'income', 'inc'  => 'inc',
            'expense', 'exp' => 'exp',
            default          => 'asset',
        };

        // Helpers
        $toDec        = static fn($v) => round((float) $v, 2);
        $sendTotal    = $toDec($data['send_total_amount'] ?? 0);
        $receiveTotal = $toDec($data['receive_total_amount'] ?? 0);

        // Map short type -> models for balance updates
        $classesFor = static function (string $typeShort) {
            // 'inc'   : From E/I -> To Asset
            // 'exp'   : From Asset -> To E/I
            // 'asset' : From Asset -> To Asset
            return match ($typeShort) {
                'inc'   => ['from' => ExpenseIncomeAccount::class, 'to' => Account::class],
                'exp'   => ['from' => Account::class,            'to' => ExpenseIncomeAccount::class],
                default => ['from' => Account::class,            'to' => Account::class],
            };
        };

        $lock = static function (string $model, $id) {
            return $model::query()->lockForUpdate()->findOrFail($id);
        };

        DB::transaction(function () use (&$data, $sourceFees, $destFees, $toDec, $sendTotal, $receiveTotal, $classesFor, $lock) {
            // 1) Create transaction row
            /** @var \App\Models\Transaction $tx */
            $tx = Transaction::create($data);

            // 2) Persist fees
            foreach ($sourceFees as $f) {
                if (!isset($f['name'], $f['amount'])) continue;
                TransactionFee::create([
                    'transaction_id' => $tx->id,
                    'name'           => (string) $f['name'],
                    'amount'         => $toDec($f['amount']),
                    'type'           => 'from',
                ]);
            }
            foreach ($destFees as $f) {
                if (!isset($f['name'], $f['amount'])) continue;
                TransactionFee::create([
                    'transaction_id' => $tx->id,
                    'name'           => (string) $f['name'],
                    'amount'         => $toDec($f['amount']),
                    'type'           => 'to',
                ]);
            }

            // 3) Balance updates (overdrafts allowed)
            $classes = $classesFor($data['type']); // 'inc' | 'exp' | 'asset'
            $source  = $lock($classes['from'], $data['from_account_id']);
            $dest    = $lock($classes['to'],   $data['to_account_id']);

            $source->current_balance = $toDec($source->current_balance) - $sendTotal;
            $dest->current_balance   = $toDec($dest->current_balance)   + $receiveTotal;

            $source->save();
            $dest->save();
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction created and balances updated (overdrafts allowed).');
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
     * GET /transactions/{transaction}/edit
     * - Normalizes short codes (inc/exp/ast) to long strings (income/expense/asset) for the UI
     * - Sends all numeric amounts as floats
     * - Sends existing attachments as array
     * - Sends fees split into source_fees / dest_fees
     */
    public function edit(Transaction $transaction)
    {
        // Normalize type to long form for the UI
        $rawType = (string) $transaction->type;
        $uiType  = match (strtolower($rawType)) {
            'inc', 'income'  => 'income',
            'exp', 'expense' => 'expense',
            'ast', 'asset',  'asset' => 'asset',
            default          => 'asset',
        };

        $categories        = TransactionCategory::select('id', 'name')->orderBy('name')->get();
        $accounts          = Account::select('id', 'name', 'currency', 'current_balance')->orderBy('name')->get();
        $exp_inc_accounts  = ExpenseIncomeAccount::select('id', 'name', 'currency', 'current_balance')->orderBy('name')->get();

        // Eager-load fees
        $transaction->load(['fees:id,transaction_id,name,amount,type']);

        $fees        = $transaction->fees;
        $source_fees = $fees->where('type', 'from')->values()->map(fn($f) => [
            'name'   => (string)$f->name,
            'amount' => (float)$f->amount,
        ])->all();
        $dest_fees   = $fees->where('type', 'to')->values()->map(fn($f) => [
            'name'   => (string)$f->name,
            'amount' => (float)$f->amount,
        ])->all();

        return Inertia::render('transactions/Edit', [
            'transaction'      => [
                'id'                    => $transaction->id,
                'type'                  => $uiType, // UI uses long strings
                'name'                  => (string)$transaction->name,
                'category_id'           => $transaction->category_id,
                'from_account_id'       => $transaction->from_account_id,
                'to_account_id'         => $transaction->to_account_id,
                'description'           => $transaction->description,
                'attachments'           => is_array($transaction->attachments) ? $transaction->attachments : [],

                'send_total_amount'     => (float)$transaction->send_total_amount,
                'send_actual_amount'    => (float)$transaction->send_actual_amount,
                'receive_total_amount'  => (float)$transaction->receive_total_amount,
                'receive_actual_amount' => (float)$transaction->receive_actual_amount,

                'created_at'            => optional($transaction->created_at)->toIso8601String(),
                'updated_at'            => optional($transaction->updated_at)->toIso8601String(),
            ],
            'source_fees'       => $source_fees,
            'dest_fees'         => $dest_fees,
            'categories'        => $categories,
            'accounts'          => $accounts,          // Asset accounts
            'exp_inc_accounts'  => $exp_inc_accounts,  // Expense/Income accounts
        ]);
    }


    /**
     * PUT/PATCH /transactions/{transaction}
     * - Overdrafts allowed: no insufficient-balance checks
     * - Accepts both long (income/expense/asset) and short (inc/exp/ast) types
     * - Reverses old effect, updates row, re-applies new effect
     * - Merges new attachments with existing
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $data = $request->validated();

        // Ensure directory exists for any new uploads
        $disk = Storage::disk('public');
        if (! $disk->exists('transactions')) {
            $disk->makeDirectory('transactions');
        }

        // ---- New attachments (merge with existing) ----
        $newPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $newPaths[] = $file->store('transactions', 'public');
                }
            }
        }
        if (!empty($newPaths)) {
            $existing = (array) ($transaction->attachments ?? []);
            $data['attachments'] = array_values(array_merge($existing, $newPaths));
        } else {
            unset($data['attachments']); // keep as-is
        }

        // ---- Extract fees from payload ----
        $sourceFees = $data['source_fees'] ?? [];
        $destFees   = $data['dest_fees'] ?? [];
        unset($data['source_fees'], $data['dest_fees']);

        // ---- Helpers ----
        $toDec           = static fn($v) => round((float)$v, 2);
        $newSendTotal    = $toDec($data['send_total_amount']    ?? 0);
        $newReceiveTotal = $toDec($data['receive_total_amount'] ?? 0);

        // Normalize NEW type to SHORT enum stored in DB (inc|exp|asset)
        $newTypeShort = match (strtolower((string)($data['type'] ?? 'asset'))) {
            'inc', 'income'  => 'inc',
            'exp', 'expense' => 'exp',
            'ast', 'asset'   => 'asset',
            default          => 'asset',
        };
        $data['type'] = $newTypeShort;

        // Previous state (normalize to short)
        $oldTypeShort   = match (strtolower((string)$transaction->type)) {
            'inc', 'income'  => 'inc',
            'exp', 'expense' => 'exp',
            'ast', 'asset'   => 'asset',
            default          => 'asset',
        };
        $oldSendTotal    = $toDec($transaction->send_total_amount);
        $oldReceiveTotal = $toDec($transaction->receive_total_amount);
        $oldFromId       = $transaction->from_account_id;
        $oldToId         = $transaction->to_account_id;

        // Map type -> model classes for balance effects
        $classesFor = static function (string $typeShort) {
            // 'inc'   : From E/I -> To Asset
            // 'exp'   : From Asset -> To E/I
            // 'asset' : From Asset -> To Asset
            return match ($typeShort) {
                'inc'   => ['from' => ExpenseIncomeAccount::class, 'to' => Account::class],
                'exp'   => ['from' => Account::class,            'to' => ExpenseIncomeAccount::class],
                default => ['from' => Account::class,            'to' => Account::class],
            };
        };

        $lock = static function (string $model, $id) {
            return $model::query()->lockForUpdate()->findOrFail($id);
        };

        DB::transaction(function () use (
            $transaction,
            $data,
            $sourceFees,
            $destFees,
            $toDec,
            $newSendTotal,
            $newReceiveTotal,
            $oldTypeShort,
            $newTypeShort,
            $oldSendTotal,
            $oldReceiveTotal,
            $oldFromId,
            $oldToId,
            $lock,
            $classesFor
        ) {
            // 1) Reverse previous balances
            $oldClasses = $classesFor($oldTypeShort);
            $prevSource = $lock($oldClasses['from'], $oldFromId);
            $prevDest   = $lock($oldClasses['to'],   $oldToId);

            $prevSource->current_balance = $toDec($prevSource->current_balance) + $oldSendTotal;
            $prevDest->current_balance   = $toDec($prevDest->current_balance)   - $oldReceiveTotal;

            $prevSource->save();
            $prevDest->save();

            // 2) Update base transaction fields
            $transaction->update($data);

            // 3) Replace fees
            $transaction->fees()->delete();

            foreach ($sourceFees as $f) {
                if (!isset($f['name'], $f['amount'])) continue;
                TransactionFee::create([
                    'transaction_id' => $transaction->id,
                    'name'           => (string)$f['name'],
                    'amount'         => $toDec($f['amount']),
                    'type'           => 'from',
                ]);
            }
            foreach ($destFees as $f) {
                if (!isset($f['name'], $f['amount'])) continue;
                TransactionFee::create([
                    'transaction_id' => $transaction->id,
                    'name'           => (string)$f['name'],
                    'amount'         => $toDec($f['amount']),
                    'type'           => 'to',
                ]);
            }

            // 4) Apply new balances (overdrafts allowed)
            $newClasses = $classesFor($newTypeShort);
            $source = $lock($newClasses['from'], $transaction->from_account_id);
            $dest   = $lock($newClasses['to'],   $transaction->to_account_id);

            $source->current_balance = $toDec($source->current_balance) - $newSendTotal;
            $dest->current_balance   = $toDec($dest->current_balance)   + $newReceiveTotal;

            $source->save();
            $dest->save();
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction updated and balances adjusted (overdrafts allowed).');
    }


    /**
     * DELETE /transactions/{transaction}
     */
    public function destroy(Transaction $transaction)
    {
        $toDec = static fn($v) => round((float) $v, 2);

        // Map type (accept short or long)
        $typeLong = match (strtolower((string) $transaction->type)) {
            'inc', 'income'  => 'income',
            'exp', 'expense' => 'expense',
            'ast', 'asset',  'asset' => 'asset',
            default          => 'asset',
        };

        // Determine model classes for source/dest based on type
        $classesFor = static function (string $type) {
            // income : From E/I -> To Asset
            // expense: From Asset -> To E/I
            // asset  : From Asset -> To Asset
            return match ($type) {
                'income'  => ['from' => ExpenseIncomeAccount::class, 'to' => Account::class],
                'expense' => ['from' => Account::class,              'to' => ExpenseIncomeAccount::class],
                default   => ['from' => Account::class,              'to' => Account::class],
            };
        };

        $lock = static function (string $model, $id) {
            return $model::query()->lockForUpdate()->findOrFail($id);
        };

        // Keep attachment paths to delete from disk AFTER a successful DB commit
        $attachmentPaths = is_array($transaction->attachments) ? $transaction->attachments : [];

        DB::transaction(function () use ($transaction, $toDec, $classesFor, $lock, $typeLong) {
            $classes = $classesFor($typeLong);

            // Lock rows (prevents race conditions)
            $source = $lock($classes['from'], $transaction->from_account_id);
            $dest   = $lock($classes['to'],   $transaction->to_account_id);

            $sendTotal    = $toDec($transaction->send_total_amount);
            $receiveTotal = $toDec($transaction->receive_total_amount);

            // Ensure dest can give back what it received
            if ($toDec($dest->current_balance) - $receiveTotal < 0) {
                throw ValidationException::withMessages([
                    'to_account_id' => 'Cannot reverse: the destination account does not have enough balance to give back the received amount.',
                ]);
            }

            // Reverse the balance effects
            $source->current_balance = $toDec($source->current_balance) + $sendTotal;     // give back to source
            $dest->current_balance   = $toDec($dest->current_balance)   - $receiveTotal;  // take back from dest

            $source->save();
            $dest->save();

            // Remove fees (if FK cascade not configured)
            // If you have ON DELETE CASCADE on transaction_fees.transaction_id, you can skip this.
            $transaction->fees()->delete();

            // Finally delete the transaction record
            $transaction->delete();
        });

        // Delete files from disk AFTER the DB transaction succeeds
        if (!empty($attachmentPaths)) {
            try {
                Storage::disk('public')->delete($attachmentPaths);
            } catch (\Throwable $e) {
                // Optional: log but don't fail the request
                \Log::warning('Failed to delete transaction attachments', [
                    'transaction_id' => $transaction->id ?? null,
                    'paths'          => $attachmentPaths,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction reversed and deleted.');
    }
}
