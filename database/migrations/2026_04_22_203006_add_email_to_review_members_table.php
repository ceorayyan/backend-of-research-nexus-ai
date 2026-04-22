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
            // Make user_id nullable for pending invitations
            $table->foreignId('user_id')->nullable()->change();
            
            // Add email field for pending invitations
            $table->string('email')->nullable()->after('user_id');
            
            // Add status field
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending')->after('role');
            
            // Drop the unique constraint
            $table->dropUnique(['review_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_members', function (Blueprint $table) {
            $table->dropColumn(['email', 'status']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->unique(['review_id', 'user_id']);
        });
    }
};
