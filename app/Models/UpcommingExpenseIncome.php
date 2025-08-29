<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpcommingExpenseIncome extends Model
{
    /** @use HasFactory<\Database\Factories\UpcommingExpenseIncomeAccountFactory> */
    use HasFactory;

    protected $table = 'upcomming_expense_income_accounts';

    protected $fillable = [
        'title',
        'description',
        'eia_id',
        'date',
        'type',
        'attachments',
        'amount',
        'currency',
    ];

     protected $casts = [
        'attachments'   => 'array',   // <— so arrays are stored/retrieved as JSON automatically
        'date'          => 'date',
        'amount'        => 'decimal:2',
    ];
}
