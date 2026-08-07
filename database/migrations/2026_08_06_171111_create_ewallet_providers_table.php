<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ewallet_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('current_float')->default(0); // centavos digital float
            $table->unsignedInteger('low_float_threshold')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ewallet_providers');
    }
};
