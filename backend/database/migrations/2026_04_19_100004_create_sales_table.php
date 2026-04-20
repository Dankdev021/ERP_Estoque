<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('unit_cost_at_sale', 15, 2);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('sale_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
