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
        Schema::table('users', function (Blueprint $table) {
            $table->json('face_descriptors')->nullable()->after('password');
            $table->string('face_image_path')->nullable()->after('face_descriptors');
            $table->boolean('face_auth_enabled')->default(false)->after('face_image_path');
            $table->timestamp('face_enrolled_at')->nullable()->after('face_auth_enabled');
            $table->integer('face_auth_attempts')->default(0)->after('face_enrolled_at');
            $table->timestamp('face_auth_locked_until')->nullable()->after('face_auth_attempts');
        });

          Schema::create('face_auth_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('attempt_type', ['enrollment', 'login', 'verification']);
            $table->boolean('success');
            $table->string('ip_address');
            $table->text('user_agent')->nullable();
            $table->json('face_data')->nullable(); // Store confidence scores, etc.
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attempt_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropColumn([
                'face_descriptors',
                'face_image_path',
                'face_auth_enabled',
                'face_enrolled_at',
                'face_auth_attempts',
                'face_auth_locked_until'
            ]);
        });

         Schema::dropIfExists('face_auth_logs');
    }
};
