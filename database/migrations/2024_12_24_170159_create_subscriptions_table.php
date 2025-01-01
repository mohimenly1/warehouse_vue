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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('subscription_type'); // 'normal', 'trader', etc.
            $table->decimal('amount', 8, 2); // Subscription amount
            $table->enum('payment_method', ['credit_card', 'voucher', 'bank_transfer']);
            $table->string('voucher_code')->nullable(); // Used if the payment is via voucher
            $table->timestamp('paid_at')->nullable(); // When the user made the payment
            $table->boolean('is_paid')->default(false); // Payment status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
