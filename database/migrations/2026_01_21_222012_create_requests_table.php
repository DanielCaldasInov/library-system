<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('number')->unique();

            $table->foreignId('book_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('citizen_name');
            $table->string('citizen_email')->nullable();
            $table->string('citizen_photo')->nullable();

            $table->string('book_name');
            $table->string('book_cover')->nullable();

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->string('status')->default('active')->index();

            $table->unsignedSmallInteger('days_elapsed')->nullable();

            $table->foreignId('received_by_admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['book_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['requested_at']);
            $table->index(['due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
