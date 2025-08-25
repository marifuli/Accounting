<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionFee extends Model
{
    use HasFactory;

    protected $table = 'transaction_fees';

    // Optional: enum helpers
    public const TYPE_FROM = 'from';
    public const TYPE_TO   = 'to';

    protected $fillable = [
        'transaction_id',
        'name',
        'amount',
        'type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
