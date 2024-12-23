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
        Schema::create('receipt_orders', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_name');
            $table->string('warehouse_address');
            $table->string('supplier_name');
            $table->string('supplier_address');
            $table->string('supplier_contact');
            $table->string('order_number')->unique();
            $table->timestamp('receipt_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_orders');
    }
};
