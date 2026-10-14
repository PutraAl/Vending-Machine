
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispense_reconciliations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dispense_id')
                ->constrained('dispenses')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('decision', 50);
            $table->text('reason');
            $table->text('evidence')->nullable();

            $table->string('status_before', 50);
            $table->string('status_after', 50)->nullable();

            $table->timestamps();

            $table->index('decision');
            $table->index(['dispense_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispense_reconciliations');
    }
};
