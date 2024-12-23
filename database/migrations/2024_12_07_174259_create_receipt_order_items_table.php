<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('receipt_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_order_id')->constrained('receipt_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity_received');
            $table->decimal('wholesale_price', 15, 2);
            $table->decimal('retail_price', 15, 2);
            $table->string('batch_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('product_type')->default('Wholesale'); // Wholesale or Retail
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_order_items');
    }
};
