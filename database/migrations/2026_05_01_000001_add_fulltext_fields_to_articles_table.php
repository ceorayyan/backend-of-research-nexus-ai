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
            $table->string('fulltext_status')->nullable()->after('screening_decision_by'); // 'none', 'included', 'excluded', 'maybe'
            $table->string('fulltext_decision_by')->nullable()->after('fulltext_status');
            $table->text('fulltext_notes')->nullable()->after('fulltext_decision_by');
            $table->json('fulltext_labels')->nullable()->after('fulltext_notes');
            $table->json('fulltext_exclusion_reasons')->nullable()->after('fulltext_labels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'fulltext_status',
                'fulltext_decision_by',
                'fulltext_notes',
                'fulltext_labels',
                'fulltext_exclusion_reasons',
            ]);
        });
    }
};
