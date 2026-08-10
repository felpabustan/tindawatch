<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ewallet_providers', function (Blueprint $table) {
            $table->integer('cash_on_hand')->default(0)->after('current_float');
        });
    }

    public function down(): void
    {
        Schema::table('ewallet_providers', function (Blueprint $table) {
            $table->dropColumn('cash_on_hand');
        });
    }
};
