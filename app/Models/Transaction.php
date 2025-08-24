<?php

namespace App\Models;

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
        'category_id',
        'from_account_id',
        'to_account_id',
        'description',
        'attachments',
        'send_total_amount',
        'send_actual_amount',
        'receive_total_amount',
        'receive_actual_amount',
    ];

    /**
     * Casts for JSON + decimals.
     */
    protected $casts = [
        'attachments'          => 'array',
        'send_total_amount'    => 'decimal:2',
        'send_actual_amount'   => 'decimal:2',
        'receive_total_amount' => 'decimal:2',
        'receive_actual_amount'=> 'decimal:2',
    ];

    /**
     * Relationships.
     */
    public function category()
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
