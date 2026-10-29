<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('from_status')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('order_status_histories')
            ->whereNull('from_status')
            ->update(['from_status' => DB::raw('to_status')]);

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('from_status')->nullable(false)->change();
        });
    }
};
