<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Mass-assignable columns (match your migration).
     */
    protected $fillable = [
        'name',
        'type',
        'category_id',
        'from_account_id',
        'to_account_id',
        'description',
        'attachments',
        'send_total_amount',
        'send_actual_amount',
        'receive_total_amount',
        'receive_actual_amount',
        'date',
    ];
    protected $appends = ['from_account', 'to_account'];
    /**
     * Casts for JSON + decimals.
     */
    protected $casts = [
        'attachments'          => 'array',
        'send_total_amount'    => 'decimal:2',
        'send_actual_amount'   => 'decimal:2',
        'receive_total_amount' => 'decimal:2',
        'receive_actual_amount' => 'decimal:2',
    ];

    /**
     * Relationships.
     */
    public function category()
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function getFromAccountAttribute()
    {
        if($this->type === 'inc') {
            return ExpenseIncomeAccount::find($this->from_account_id);
        }
        return Account::find($this->from_account_id);
        // return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function getToAccountAttribute()
    {
        if($this->type === 'exp') {
            return ExpenseIncomeAccount::find($this->to_account_id);
        }
        return Account::find($this->to_account_id);
        // return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function fees()
    {
        return $this->hasMany(TransactionFee::class)->orderBy('id');
    }

    // Optional convenience
    public function sourceFees()
    {
        return $this->hasMany(TransactionFee::class)->where('type', TransactionFee::TYPE_FROM);
    }

    public function destFees()
    {
        return $this->hasMany(TransactionFee::class)->where('type', TransactionFee::TYPE_TO);
    }
}
