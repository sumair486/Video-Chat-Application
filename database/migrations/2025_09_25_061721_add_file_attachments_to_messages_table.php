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
        Schema::table('messages', function (Blueprint $table) {
             $table->string('file_name')->nullable()->after('message');
            $table->string('file_path')->nullable()->after('file_name');
            $table->string('file_type')->nullable()->after('file_path'); // mime type
            $table->bigInteger('file_size')->nullable()->after('file_type'); // file size in bytes
            $table->string('original_name')->nullable()->after('file_size'); // original filename
            
            // Update type enum to include more file types
            $table->enum('type', ['text', 'image', 'file', 'document', 'video', 'audio'])->default('text')->change();
            
            // Add index for better performance when filtering by type
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
             $table->dropColumn([
                'file_name',
                'file_path',
                'file_type',
                'file_size',
                'original_name'
            ]);
            
            $table->dropIndex(['type']);
            
            // Revert type enum back to original
            $table->enum('type', ['text', 'image', 'file'])->default('text')->change();
        });
    }
};
