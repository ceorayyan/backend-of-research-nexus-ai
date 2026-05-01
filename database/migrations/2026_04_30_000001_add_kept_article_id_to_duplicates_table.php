<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duplicates', function (Blueprint $table) {
            // Tracks which article was kept when status = 'resolved'
            // null = not yet resolved or both kept
            $table->foreignId('kept_article_id')
                ->nullable()
                ->after('marked_by_user_id')
                ->constrained('articles')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('duplicates', function (Blueprint $table) {
            $table->dropForeign(['kept_article_id']);
            $table->dropColumn('kept_article_id');
        });
    }
};
