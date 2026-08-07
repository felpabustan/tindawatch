<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('unit')->default('piece'); // piece|sachet|case|kilo
            $table->unsignedInteger('pieces_per_case')->nullable();
            $table->unsignedInteger('cost_price'); // centavos
            $table->unsignedInteger('sell_price'); // centavos
            $table->integer('stock_qty')->default(0);
            $table->integer('reorder_threshold')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'name']);
            $table->unique(['store_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
