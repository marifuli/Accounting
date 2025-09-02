<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ExpenseIncomeAccount;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\UpcommingExpenseIncome;
use App\Services\CurrencyExchangeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyExchangeService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    function dashboard() {
        // Get all accounts with their balances
        $accounts = Account::where('is_active', true)->get();

        // Convert all account balances to BDT
        $totalBalance = 0;
        foreach ($accounts as $account) {
            $balanceInBDT = $this->currencyService->convertToPrimaryCurrency(
                $account->current_balance,
                $account->currency
            );
            $totalBalance += $balanceInBDT;
        }

        $accountsCount = $accounts->count();

        // Get transaction statistics
        $totalIncome = Transaction::where('type', 'inc')
            ->sum('receive_actual_amount');

        $totalExpenses = Transaction::where('type', 'exp')
            ->sum('send_actual_amount');

        // Get recent transactions (last 10)
        $recentTransactions = Transaction::with('category')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'name' => $transaction->name,
                    'type' => $transaction->type,
                    'amount' => $transaction->type === 'inc'
                        ? $transaction->receive_actual_amount
                        : $transaction->send_actual_amount,
                    'category' => $transaction->category?->name,
                    'date' => $transaction->created_at->format('M d, Y'),
                    'from_account' => $transaction->from_account?->name ?? $transaction->from_account?->account_name,
                    'to_account' => $transaction->to_account?->name ?? $transaction->to_account?->account_name,
                ];
            });

        // Get transactions by category for pie chart
        $transactionsByCategory = Transaction::select(
                'transaction_categories.name as category_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN type = "inc" THEN receive_actual_amount ELSE send_actual_amount END) as total_amount')
            )
            ->leftJoin('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->groupBy('transaction_categories.id', 'transaction_categories.name')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category_name ?? 'Uncategorized',
                    'count' => $item->count,
                    'amount' => floatval($item->total_amount)
                ];
            });

        // Get monthly income vs expenses for the last 6 months
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $income = Transaction::where('type', 'inc')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('receive_actual_amount');

            $expenses = Transaction::where('type', 'exp')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('send_actual_amount');

            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'income' => floatval($income),
                'expenses' => floatval($expenses)
            ];
        }

        // Get account balances for overview (converted to BDT)
        $accountBalances = $accounts->map(function ($account) {
            $balanceInBDT = $this->currencyService->convertToPrimaryCurrency(
                $account->current_balance,
                $account->currency
            );

            return [
                'name' => $account->name ?: $account->account_name,
                'balance' => floatval($balanceInBDT),
                'original_balance' => floatval($account->current_balance),
                'type' => $account->type,
                'currency' => $account->currency,
                'primary_currency' => $this->currencyService->getPrimaryCurrency()
            ];
        });

        // Get upcoming expenses/income (next 30 days)
        $upcomingItems = UpcommingExpenseIncome::where('date', '>=', Carbon::now())
            ->where('date', '<=', Carbon::now()->addDays(30))
            ->orderBy('date')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'title' => $item->title,
                    'amount' => floatval($item->amount),
                    'type' => $item->type,
                    'date' => Carbon::parse($item->date)->format('M d, Y'),
                    'days_until' => Carbon::parse($item->date)->diffInDays(Carbon::now())
                ];
            });

        $upcomingIncome = $upcomingItems->where('type', 'inc')->sum('amount');
        $upcomingExpenses = $upcomingItems->where('type', 'exp')->sum('amount');

        return inertia('Dashboard', [
            'stats' => [
                'totalBalance' => floatval($totalBalance),
                'totalIncome' => floatval($totalIncome),
                'totalExpenses' => floatval($totalExpenses),
                'accountsCount' => $accountsCount,
                'upcomingIncome' => floatval($upcomingIncome),
                'upcomingExpenses' => floatval($upcomingExpenses),
                'netIncome' => floatval($totalIncome - $totalExpenses),
                'primaryCurrency' => $this->currencyService->getPrimaryCurrency()
            ],
            'recentTransactions' => $recentTransactions,
            'transactionsByCategory' => $transactionsByCategory,
            'monthlyData' => $monthlyData,
            'accountBalances' => $accountBalances,
            'upcomingItems' => $upcomingItems
        ]);
    }
}
