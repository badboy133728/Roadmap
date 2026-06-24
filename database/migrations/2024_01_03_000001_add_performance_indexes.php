<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professions', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('quiz_results', function (Blueprint $table) {
            $table->index('session_id');
        });

        Schema::table('job_vacancies', function (Blueprint $table) {
            $table->index(['profession_id', 'is_active', 'city_id']);
        });

        Schema::table('education_programs', function (Blueprint $table) {
            $table->index(['profession_id', 'institution_id']);
        });

        Schema::table('job_platforms', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('professions', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('quiz_results', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
        });

        Schema::table('job_vacancies', function (Blueprint $table) {
            $table->dropIndex(['profession_id', 'is_active', 'city_id']);
        });

        Schema::table('education_programs', function (Blueprint $table) {
            $table->dropIndex(['profession_id', 'institution_id']);
        });

        Schema::table('job_platforms', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'sort_order']);
        });
    }
};
