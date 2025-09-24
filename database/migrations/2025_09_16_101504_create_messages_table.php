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
         Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // sender
            $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('cascade'); // receiver for private messages
            $table->text('message');
            $table->enum('type', ['text', 'image', 'file'])->default('text');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Index for better performance
            $table->index(['user_id', 'receiver_id', 'created_at']);
            $table->index(['receiver_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
