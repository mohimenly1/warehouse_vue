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
        Schema::create('disbursement_orders', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_name');
            $table->string('warehouse_address');
            $table->string('recipient_name');
            $table->string('recipient_contact')->nullable();
            $table->string('department')->nullable();
            $table->text('disbursement_reason')->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursement_orders');
    }
};
