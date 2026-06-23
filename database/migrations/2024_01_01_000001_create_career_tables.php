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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('region');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('profession_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('professions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('profession_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('skills')->nullable();
            $table->text('outlook')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('career_path_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profession_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('step_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->timestamps();
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('education_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profession_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('duration_years', 3, 1)->nullable();
            $table->string('study_form');
            $table->timestamps();
        });

        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profession_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['junior', 'middle', 'senior']);
            $table->unsignedInteger('salary_min');
            $table->unsignedInteger('salary_median');
            $table->unsignedInteger('salary_max');
            $table->string('source')->nullable();
            $table->date('updated_at_source')->nullable();
            $table->timestamps();

            $table->unique(['profession_id', 'city_id', 'level']);
        });

        Schema::create('career_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_profession_id')->constrained('professions')->cascadeOnDelete();
            $table->foreignId('to_profession_id')->constrained('professions')->cascadeOnDelete();
            $table->string('difficulty');
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('transition_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_transition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->timestamps();
        });

        Schema::create('interest_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->json('interest_scores')->nullable();
            $table->json('profession_scores')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->json('answers');
            $table->json('recommendations')->nullable();
            $table->timestamps();
        });

        Schema::create('saved_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profession_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('user_favorite_professions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profession_id')->constrained()->cascadeOnDelete();

            $table->unique(['user_id', 'profession_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('password')->constrained()->nullOnDelete();
            $table->foreignId('current_profession_id')->nullable()->after('city_id')->constrained('professions')->nullOnDelete();
            $table->boolean('is_admin')->default(false)->after('current_profession_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('current_profession_id');
            $table->dropColumn('is_admin');
        });

        Schema::dropIfExists('user_favorite_professions');
        Schema::dropIfExists('saved_paths');
        Schema::dropIfExists('quiz_results');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('interest_categories');
        Schema::dropIfExists('transition_steps');
        Schema::dropIfExists('career_transitions');
        Schema::dropIfExists('salaries');
        Schema::dropIfExists('education_programs');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('career_path_steps');
        Schema::dropIfExists('professions');
        Schema::dropIfExists('profession_categories');
        Schema::dropIfExists('cities');
    }
};
