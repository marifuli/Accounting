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
        Schema::table('expense_income_accounts', function (Blueprint $table) {
            $table->dropForeign(['transaction_category_id']);
            $table->dropColumn('transaction_category_id');
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('app_password')->nullable();
            $table->string('app_pin')->nullable();
            $table->string('secret_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'app_password', 'app_pin', 'secret_note'
            ]);
        });
         Schema::table('expense_income_accounts', function (Blueprint $table) {
            $table->foreignId('transaction_category_id')
                ->constrained('transaction_categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate(); // required
        });
    }
};
