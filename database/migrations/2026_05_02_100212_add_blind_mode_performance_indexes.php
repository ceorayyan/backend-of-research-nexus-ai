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
        Schema::table('articles', function (Blueprint $table) {
            // Add indexes for blind mode filtering performance
            $table->index('screening_decision_by', 'idx_articles_screening_decision_by');
            $table->index('fulltext_decision_by', 'idx_articles_fulltext_decision_by');
            
            // Add composite indexes for efficient blind mode queries
            $table->index(['review_id', 'screening_decision_by'], 'idx_articles_review_screening');
            $table->index(['review_id', 'fulltext_decision_by'], 'idx_articles_review_fulltext');
        });

        Schema::table('reviews', function (Blueprint $table) {
            // Add index on blind_mode for query performance
            $table->index('blind_mode', 'idx_reviews_blind_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Drop indexes in reverse order
            $table->dropIndex('idx_articles_review_fulltext');
            $table->dropIndex('idx_articles_review_screening');
            $table->dropIndex('idx_articles_fulltext_decision_by');
            $table->dropIndex('idx_articles_screening_decision_by');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_blind_mode');
        });
    }
};
