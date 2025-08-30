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
    // routes/web.php
    Route::delete(
        'upcomming-expense-income/{upcomming_expense_income}/attachments/{index}',
        [UpcommingExpenseIncomeController::class, 'destroyAttachment']
    )->name('upcomming-expense-income.attachments.destroy');

    Route::resource('transactions', TransactionController::class);
    Route::delete('transactions/{transaction}/attachment/{index}', [TransactionController::class, 'destroyAttachment'])->name('transaction.attachments.destroy');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
