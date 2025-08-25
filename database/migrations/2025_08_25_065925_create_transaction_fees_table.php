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
        Schema::create('transaction_fees', function (Blueprint $table) {
            $table->id();

            // Foreign id WITHOUT constraint
            $table->unsignedBigInteger('transaction_id');
            $table->index('transaction_id'); // optional but useful

            $table->string('name');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('type', ['from', 'to']); // enum order: from, to

            $table->timestamps();

            // optional composite index for common queries
            $table->index(['transaction_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_fees');
    }
};
