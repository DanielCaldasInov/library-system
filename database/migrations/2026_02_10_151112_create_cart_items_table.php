<?php

use App\Models\Book;
use App\Models\Cart;
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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Cart::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Book::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('qty')->default(1);

            $table->timestamps();

            $table->unique(['cart_id', 'book_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
