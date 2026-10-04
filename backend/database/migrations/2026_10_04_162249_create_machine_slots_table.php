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
        Schema::create('machine_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('machine_id')
                ->constrained('machines')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->string('slot_code', 20);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('capacity');

            $table->timestamps();

            $table->unique(['machine_id', 'slot_code']);

            $table->index('machine_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_slots');
    }
};
