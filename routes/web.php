<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ExpenseIncomeAccountController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TransactionCategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UpcommingExpenseIncomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/dashboard', '/')->name('home');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [HomeController::class, 'dashboard'])->name('dashboard');
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

    // Backup route
    Route::get('/backup/download', [BackupController::class, 'download'])->name('backup.download');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
