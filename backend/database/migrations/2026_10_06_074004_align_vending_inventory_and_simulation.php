<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * PRODUCTS
         * Tambahkan penanda apakah product merupakan
         * data simulasi atau data produk sebenarnya.
         */
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_simulated')
                ->default(false)
                ->after('image');
        });

        /*
         * MACHINE SLOTS
         *
         * stock lama -> current_qty
         * tambahkan hold_qty untuk stok yang sedang di-hold
         * ketika order dibuat.
         */
        Schema::table('machine_slots', function (Blueprint $table) {
            $table->renameColumn('stock', 'current_qty');

            $table->unsignedInteger('hold_qty')
                ->default(0)
                ->after('current_qty');
        });
    }

    public function down(): void
    {
        Schema::table('machine_slots', function (Blueprint $table) {
            $table->dropColumn('hold_qty');
            $table->renameColumn('current_qty', 'stock');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_simulated');
        });
    }
};