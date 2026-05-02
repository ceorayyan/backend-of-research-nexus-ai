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
        Schema::create('article_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('decision', ['included', 'excluded', 'undecided'])->default('undecided');
            $table->text('notes')->nullable();
            $table->json('labels')->nullable();
            $table->json('exclusion_reasons')->nullable();
            $table->timestamps();
            
            // Ensure one decision per user per article
            $table->unique(['article_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['article_id', 'user_id']);
            $table->index('decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_screenings');
    }
};
