<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique()->index();
            $table->string('swift_code', 11)->nullable();           // Typically 8 or 11 chars
            $table->string('name');
            $table->string('account_name')->nullable();

            $table->enum('currency', ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'INR', 'BRL', 'ZAR', 'BDT', 'other'])->default('BDT');         // ISO 4217 currency code
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);

            $table->boolean('is_active')->default(true);
            $table->enum('type', ['bank', 'card', 'mobile'])->default('bank');


            // ✅ Card brand/network enum + 'other'
            $table->enum('card_type', [
                'visa',
                'mastercard',
                'amex',            // American Express
                'discover',
                'unionpay',
                'jcb',
                'diners_club',
                'maestro',
                'visa_electron',
                'rupay',
                'verve',
                'troy',
                'mir',
                'elo',
                'hipercard',
                'bancontact',
                'interac',
                'dankort',
                'bc_card',
                'mada',
                'eftpos',
                'cartes_bancaires',
                'uzcard',
                'humo',
                'other',
            ])->nullable()->default('other');

            $table->string('account_number')->unique()->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_routing_number')->nullable();

            // ⬇️ Renamed to valid snake_case (spaces are invalid as column names)
            $table->date('card_valid_from')->nullable();    // e.g. MM/YY
            $table->date('card_expiry')->nullable();        // e.g. MM/YY
            $table->string('card_cvv')->nullable();
            $table->string('card_pin')->nullable();

            // IBAN length up to 34; Bangladesh doesn’t use IBAN—so keep this nullable
            $table->string('bank_iban', 34)->unique()->nullable();

            $table->string('bank_address')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
