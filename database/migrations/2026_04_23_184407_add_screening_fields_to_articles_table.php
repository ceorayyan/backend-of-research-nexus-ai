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
            $table->enum('status', ['included', 'excluded', 'undecided'])->default('undecided')->after('file_path');
            $table->text('screening_notes')->nullable()->after('status');
            $table->json('keywords')->nullable()->after('screening_notes');
            $table->string('journal')->nullable()->after('keywords');
            $table->integer('year')->nullable()->after('journal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['status', 'screening_notes', 'keywords', 'journal', 'year']);
        });
    }
};
