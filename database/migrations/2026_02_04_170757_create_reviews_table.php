<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Request as BookRequest;
use App\Models\Book;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(BookRequest::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Book::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();

            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->unique('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
