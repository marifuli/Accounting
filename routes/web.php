<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ExpenseIncomeAccountController;
use App\Http\Controllers\TransactionCategoryController;
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
    // Route::get('/', function () {
    //     return Inertia::render('Settings/Index');
    // })->name('index');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
