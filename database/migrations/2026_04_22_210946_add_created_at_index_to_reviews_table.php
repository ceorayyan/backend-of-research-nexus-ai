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
        Schema::table('reviews', function (Blueprint $table) {
            // Add index on created_at for faster ordering
            $table->index('created_at');
        });
        
        Schema::table('articles', function (Blueprint $table) {
            // Add index on created_at for faster ordering
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
        
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
