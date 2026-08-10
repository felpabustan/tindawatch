<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ewallet_day_closes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('ewallet_providers')->cascadeOnDelete();
            $table->date('business_date');
            $table->integer('opening_float');
            $table->integer('closing_float_before_fees');
            $table->unsignedInteger('fees_settled')->default(0);
            $table->integer('closing_float_after_fees');
            $table->integer('opening_cash');
            $table->integer('closing_cash');
            $table->unsignedInteger('cash_in_total')->default(0);
            $table->unsignedInteger('cash_out_total')->default(0);
            $table->unsignedInteger('fees_total')->default(0);
            $table->unsignedInteger('txn_count')->default(0);
            $table->foreignId('closed_by')->constrained('users')->cascadeOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'business_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ewallet_day_closes');
    }
};
