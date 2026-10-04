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
    Schema::create('telemetries', function (Blueprint $table) {
        $table->id();

        $table->foreignId('machine_id')
            ->constrained('machines')
            ->cascadeOnDelete();

        $table->decimal('temperature', 5, 2)->nullable();
        $table->string('state', 50);
        $table->string('door_status', 20)->nullable();

        $table->timestamp('created_at');

        $table->index(['machine_id', 'created_at']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetries');
    }
};
