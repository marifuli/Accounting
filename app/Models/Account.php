<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'code',
        'swift_code',
        'name',
        'account_name',
        'is_active',
        'type',
        'card_type',
        'account_number',
        'bank_name',
        'bank_routing_number',
        'card_valid_from',
        'card_expiry',
        'card_cvv',
        'card_pin',
        'bank_iban',
        'bank_address',
        'description',    
    ];
}
