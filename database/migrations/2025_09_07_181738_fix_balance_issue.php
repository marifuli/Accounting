<?php

use App\Models\Account;
use App\Models\ExpenseIncomeAccount;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Transaction::query()
            ->latest()
            ->chunk(100, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $this->resetTransaction($transaction);
                }
            });

        Transaction::query()
            ->chunk(100, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $this->calculateTransaction($transaction);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
    public function resetTransaction(Transaction $transaction): void
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
        DB::transaction(function () use ($transaction, $toDec, $classesFor, $lock, $typeLong) {
            $classes = $classesFor($typeLong);

            // Lock rows (prevents race conditions)
            $source = $lock($classes['from'], $transaction->from_account_id);
            $dest   = $lock($classes['to'],   $transaction->to_account_id);

            $sendTotal    = $toDec($transaction->send_total_amount);
            $receiveTotal = $toDec($transaction->receive_total_amount);

            // Reverse the balance effects
            $source->current_balance = $toDec($source->current_balance) + $sendTotal;     // give back to source
            $dest->current_balance   = $toDec($dest->current_balance)   - $receiveTotal;  // take back from dest

            $source->save();
            $dest->save();
        });
    }
    public function calculateTransaction(Transaction $transaction): void
    {
        $data = $transaction->toArray();

        // 6) Numeric helpers
        $toDec = static fn($v) => round((float) $v, 2);
        $sendTotal    = $toDec($data['send_actual_amount']    ?? 0);
        $receiveTotal = $toDec($data['receive_total_amount'] ?? 0);

        // 7) Map short type -> models for balance updates
        $classesFor = static function (string $typeShort) {
            // 'inc'   : From E/I -> To Asset
            // 'exp'   : From Asset -> To E/I
            // 'asset' : From Asset -> To Asset
            return match ($typeShort) {
                'inc'   => ['from' => \App\Models\ExpenseIncomeAccount::class, 'to' => \App\Models\Account::class],
                'exp'   => ['from' => \App\Models\Account::class,              'to' => \App\Models\ExpenseIncomeAccount::class],
                default => ['from' => \App\Models\Account::class,              'to' => \App\Models\Account::class],
            };
        };

        $lock = static function (string $model, $id) {
            return $model::query()->lockForUpdate()->findOrFail($id);
        };

        // 8) Create Transaction, Fees, and update balances atomically
        DB::transaction(function () use (&$data, $transaction, $toDec, $sendTotal, $receiveTotal, $classesFor, $lock) {
            /** @var \App\Models\Transaction $tx */
            $tx = $transaction;
            // Balance updates (overdrafts allowed)
            $classes = $classesFor($data['type']); // 'inc' | 'exp' | 'asset'
            $source  = $lock($classes['from'], $data['from_account_id']);
            $dest    = $lock($classes['to'],   $data['to_account_id']);

            $source->current_balance = $toDec($source->current_balance) - $sendTotal;
            $dest->current_balance   = $toDec($dest->current_balance)   + $receiveTotal;

            $source->save();
            $dest->save();
        });
    }
};
