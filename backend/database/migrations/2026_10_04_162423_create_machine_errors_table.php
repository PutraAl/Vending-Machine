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
    Schema::create('machine_errors', function (Blueprint $table) {
        $table->id();

        $table->foreignId('machine_id')
            ->constrained('machines')
            ->cascadeOnDelete();

        $table->string('error_code', 100);
        $table->text('message');
        $table->string('severity', 20);

        $table->timestamp('created_at');
        $table->timestamp('resolved_at')->nullable();

        $table->index(['machine_id', 'created_at']);
        $table->index('severity');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_errors');
    }
};
