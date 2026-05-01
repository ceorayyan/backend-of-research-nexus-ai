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
        Schema::create('duplicates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->onDelete('cascade');
            $table->foreignId('article1_id')->constrained('articles')->onDelete('cascade');
            $table->foreignId('article2_id')->constrained('articles')->onDelete('cascade');
            $table->integer('similarity_score')->default(100);
            $table->string('detection_reason')->nullable();
            $table->string('status')->default('unresolved');
            $table->foreignId('marked_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate entries
            $table->unique(['review_id', 'article1_id', 'article2_id'], 'unique_duplicate_pair');
            
            // Indexes for performance
            $table->index('review_id');
            $table->index('status');
            $table->index(['review_id', 'status']);
            $table->index(['article1_id', 'article2_id']);
        });
        
        // Add CHECK constraint to ensure article1_id < article2_id
        // SQLite supports CHECK constraints
        DB::statement('CREATE TRIGGER check_article_order_before_insert
            BEFORE INSERT ON duplicates
            FOR EACH ROW
            WHEN NEW.article1_id >= NEW.article2_id
            BEGIN
                SELECT RAISE(ABORT, "article1_id must be less than article2_id");
            END');
        
        DB::statement('CREATE TRIGGER check_article_order_before_update
            BEFORE UPDATE ON duplicates
            FOR EACH ROW
            WHEN NEW.article1_id >= NEW.article2_id
            BEGIN
                SELECT RAISE(ABORT, "article1_id must be less than article2_id");
            END');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_article_order_before_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_article_order_before_update');
        Schema::dropIfExists('duplicates');
    }
};
