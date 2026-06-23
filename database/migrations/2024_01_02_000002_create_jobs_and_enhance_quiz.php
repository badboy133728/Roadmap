<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('url');
            $table->string('search_url_template');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profession_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('company');
            $table->string('salary_text')->nullable();
            $table->text('description')->nullable();
            $table->string('external_url');
            $table->string('source')->nullable();
            $table->string('experience_level')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('emoji')->nullable()->after('question');
            $table->string('hint')->nullable()->after('emoji');
            $table->json('target_statuses')->nullable()->after('hint');
            $table->string('question_type')->default('main')->after('target_statuses');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn(['emoji', 'hint', 'target_statuses', 'question_type']);
        });

        Schema::dropIfExists('job_vacancies');
        Schema::dropIfExists('job_platforms');
    }
};
