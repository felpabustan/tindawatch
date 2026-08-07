<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ewallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('ewallet_providers')->cascadeOnDelete();
            $table->string('type'); // cash_in|cash_out
            $table->unsignedInteger('amount'); // centavos
            $table->unsignedInteger('service_fee')->default(0); // centavos
            $table->string('customer_ref')->nullable();
            $table->foreignId('processed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ewallet_transactions');
    }
};
