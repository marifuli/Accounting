<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseIncomeAccount extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseIncomeAccountFactory> */
    use HasFactory;

    protected $table = 'expense_income_accounts';

    protected $fillable = [
        'name',
        'description',
        'currency',
        'current_balance',
        'type',
    ];
}
