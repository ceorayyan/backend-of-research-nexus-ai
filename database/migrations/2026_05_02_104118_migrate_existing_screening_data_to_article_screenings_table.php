<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing screening data from articles table to article_screenings table
        // Only migrate articles that have been screened (status is not null and screening_decision_by is not null)
        
        $articles = DB::table('articles')
            ->whereNotNull('status')
            ->whereNotNull('screening_decision_by')
            ->select('id', 'screening_decision_by', 'status', 'screening_notes', 'labels', 'exclusion_reasons', 'created_at', 'updated_at')
            ->get();

        foreach ($articles as $article) {
            // Find user by name (screening_decision_by stores username like "rayyan" or "rayyan2")
            $user = DB::table('users')->where('name', $article->screening_decision_by)->first();
            
            if ($user) {
                // Insert into article_screenings table
                DB::table('article_screenings')->insert([
                    'article_id' => $article->id,
                    'user_id' => $user->id,
                    'decision' => $article->status,
                    'notes' => $article->screening_notes,
                    'labels' => $article->labels,
                    'exclusion_reasons' => $article->exclusion_reasons,
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the article_screenings table
        DB::table('article_screenings')->truncate();
    }
};
