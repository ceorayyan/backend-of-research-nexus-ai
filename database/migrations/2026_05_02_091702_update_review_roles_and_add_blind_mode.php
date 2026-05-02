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
        // Add blind_mode to reviews table
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('blind_mode')->default(false)->after('status');
        });

        // Update role enum in review_members table
        // SQLite doesn't support ALTER COLUMN, so we need to handle this differently
        // For now, we'll just update the data and rely on validation in the application
        
        // Convert 'observer' to 'reviewer'
        DB::table('review_members')
            ->where('role', 'observer')
            ->update(['role' => 'reviewer']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('blind_mode');
        });
        
        // Note: Cannot reverse role changes as we don't know which reviewers were originally observers
    }
};
