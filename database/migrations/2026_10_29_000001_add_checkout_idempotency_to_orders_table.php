<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('checkout_key')->nullable()->unique();
            $table->timestamp('stock_restored_at')->nullable();
        });

        DB::table('orders')->where('status', 'cancelado')->whereNull('stock_restored_at')->update([
            'stock_restored_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['checkout_key']);
            $table->dropColumn(['checkout_key', 'stock_restored_at']);
        });
    }
};
