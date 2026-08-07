<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('amount_tendered')->nullable()->after('payment_reference');
            $table->unsignedInteger('change_amount')->nullable()->after('amount_tendered');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['amount_tendered', 'change_amount']);
        });
    }
};
