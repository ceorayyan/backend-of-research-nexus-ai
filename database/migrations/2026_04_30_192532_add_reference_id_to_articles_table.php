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
        // Check if column already exists
        if (!Schema::hasColumn('articles', 'reference_id')) {
            Schema::table('articles', function (Blueprint $table) {
                // Add reference_id column - unique human-readable identifier
                $table->string('reference_id', 20)->nullable()->after('id');
            });

            // Generate reference IDs for existing articles
            $articles = \DB::table('articles')->orderBy('id')->get();
            foreach ($articles as $article) {
                $referenceId = 'STX-' . str_pad($article->id, 8, '0', STR_PAD_LEFT);
                \DB::table('articles')
                    ->where('id', $article->id)
                    ->update(['reference_id' => $referenceId]);
            }

            // Add unique index
            Schema::table('articles', function (Blueprint $table) {
                $table->unique('reference_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['reference_id']);
            $table->dropColumn('reference_id');
        });
    }
};

