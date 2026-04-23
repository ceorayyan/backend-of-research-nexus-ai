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
        Schema::table('review_members', function (Blueprint $table) {
            // Add index on email for faster lookups when checking pending invitations
            $table->index('email');
            
            // Add index on status for filtering
            $table->index('status');
            
            // Add composite index for common queries
            $table->index(['review_id', 'email']);
            $table->index(['review_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_members', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status']);
            $table->dropIndex(['review_id', 'email']);
            $table->dropIndex(['review_id', 'status']);
        });
    }
};
