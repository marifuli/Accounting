<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ExpenseIncomeAccountController;
use App\Http\Controllers\TransactionCategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UpcommingExpenseIncomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('accounts', AccountController::class);
    Route::resource('transaction-categories', TransactionCategoryController::class);
    Route::resource('expense-income-accounts', ExpenseIncomeAccountController::class);
    Route::resource('upcomming-expense-income', UpcommingExpenseIncomeController::class);
    Route::resource('transactions', TransactionController::class);
    // Route::prefix('transactions')->as('transactions.')->group(function () {
    //     // Resource-style routes
    //     Route::get('/',                 [TransactionController::class, 'index'])->name('index');
    //     Route::get('/create/income',    [TransactionController::class, 'create_income'])->name('create');
    //     Route::get('/create/expense',   [TransactionController::class, 'create_expense'])->name('create');
    //     Route::get('/create/asset',     [TransactionController::class, 'create_asset'])->name('create');
      
    //     Route::post('/income/store',  [TransactionController::class, 'income_store'])->name('income.store');
    //     Route::post('/expense/store', [TransactionController::class, 'expense_store'])->name('expense.store');
    //     Route::post('/asset/store',   [TransactionController::class, 'asset_store'])->name('asset.store');

    //     // Put create BEFORE the wildcard show; also constrain {transaction} to numbers
    //     Route::get('/{transaction}',           [TransactionController::class, 'show'])
    //         ->whereNumber('transaction')->name('show');

    //     Route::get('/{transaction}/edit',      [TransactionController::class, 'edit'])
    //         ->whereNumber('transaction')->name('edit');

    //     Route::match(['put', 'patch'], '/{transaction}', [TransactionController::class, 'update'])
    //         ->whereNumber('transaction')->name('update');

    //     Route::delete('/{transaction}',        [TransactionController::class, 'destroy'])
    //         ->whereNumber('transaction')->name('destroy');

    // });
    // Route::get('/', function () {
    //     return Inertia::render('Settings/Index');
    // })->name('index');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
