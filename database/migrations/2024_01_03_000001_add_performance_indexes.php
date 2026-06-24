<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('professions', 'professions_is_active_index', function (Blueprint $table) {
            $table->index('is_active');
        });

        $this->addIndex('quiz_results', 'quiz_results_session_id_index', function (Blueprint $table) {
            $table->index('session_id');
        });

        $this->addIndex('job_vacancies', 'job_vacancies_profession_id_is_active_city_id_index', function (Blueprint $table) {
            $table->index(['profession_id', 'is_active', 'city_id']);
        });

        $this->addIndex('education_programs', 'education_programs_profession_id_institution_id_index', function (Blueprint $table) {
            $table->index(['profession_id', 'institution_id']);
        });

        $this->addIndex('job_platforms', 'job_platforms_is_active_sort_order_index', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        $this->dropIndex('professions', 'professions_is_active_index');
        $this->dropIndex('quiz_results', 'quiz_results_session_id_index');
        $this->dropIndex('job_vacancies', 'job_vacancies_profession_id_is_active_city_id_index');
        $this->dropIndex('education_programs', 'education_programs_profession_id_institution_id_index');
        $this->dropIndex('job_platforms', 'job_platforms_is_active_sort_order_index');
    }

    private function addIndex(string $table, string $indexName, callable $callback): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, $callback);
    }

    private function dropIndex(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $schema = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$schema, $table, $indexName]
        );

        return ! empty($result);
    }
};
