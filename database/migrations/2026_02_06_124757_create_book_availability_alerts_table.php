<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Book;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_availability_alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Book::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['book_id', 'user_id']);
            $table->index(['book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_availability_alerts');
    }
};
