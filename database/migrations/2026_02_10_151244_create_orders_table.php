<?php

use App\Models\User;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status')->default('pending_payment');
            // pending_payment | paid | canceled | expired

            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('currency', 3)->default('eur');

            $table->string('delivery_name');
            $table->string('delivery_address_line1');
            $table->string('delivery_address_line2')->nullable();
            $table->string('delivery_zip');
            $table->string('delivery_city');
            $table->string('delivery_country', 2);

            $table->string('stripe_checkout_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();

            $table->index(['user_id', 'status']);
            $table->index('stripe_checkout_session_id');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
