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
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign('transactions_from_account_id_foreign'); // drop FK
            // optionally also make the column nullable if you want
            $table->unsignedBigInteger('from_account_id')->nullable()->change();
            $table->dropForeign('transactions_to_account_id_foreign'); // drop FK
            // optionally also make the column nullable if you want
            $table->unsignedBigInteger('to_account_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('from_account_id')
                  ->references('id')
                  ->on('accounts')
                  ->nullOnDelete(); // restore original FK
            $table->foreign('to_account_id')
                  ->references('id')
                  ->on('accounts')
                  ->nullOnDelete(); // restore original FK
        });
    }
};
