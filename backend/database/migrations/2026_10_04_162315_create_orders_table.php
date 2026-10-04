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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_code', 50)->unique();

            $table->foreignId('machine_id')
                ->constrained('machines')
                ->restrictOnDelete();

            $table->string('status', 50)
                ->default('PENDING');

            $table->decimal('total_amount', 12, 2);

            $table->timestamps();

            $table->index('machine_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
