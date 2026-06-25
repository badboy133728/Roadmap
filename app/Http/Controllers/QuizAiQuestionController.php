<?php

namespace App\Http\Controllers;

use App\Services\QuizAiService;
use App\Services\QuizProfileService;
use App\Services\QuizScoringService;
use App\Services\QuizStaticQuestions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuizAiQuestionController extends Controller
{
    public function __invoke(Request $request, QuizAiService $aiService, QuizScoringService $scoringService, QuizProfileService $profileService): JsonResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:50'],
            'about' => ['nullable', 'string', 'max:300'],
            'status' => ['required', 'in:school_9,school_11,student,working,exploring'],
            'priorities' => ['nullable', 'array', 'max:2'],
            'priorities.*' => ['in:money,creativity,people,stability,growth'],
            'static_answers' => ['required', 'array'],
            'static_answers.*' => ['required', 'string'],
            'ai_questions' => ['nullable', 'string'],
            'ai_answers' => ['nullable', 'array'],
            'ai_answers.*' => ['string'],
            'round' => ['nullable', 'integer', 'min:0'],
        ]);

        $profile = $profileService->fromQuizInput([
            'name' => $request->input('name') ? trim($request->input('name')) : null,
            'about' => $request->input('about') ? trim($request->input('about')) : null,
            'status' => $request->input('status'),
            'priorities' => array_values(array_unique($request->input('priorities', []))),
        ]);

        $staticAnswers = $request->input('static_answers', []);
        $aiQuestions = json_decode($request->input('ai_questions', '[]'), true) ?: [];
        $aiAnswers = $request->input('ai_answers', []);
        $round = (int) $request->input('round', 0);

        $this->validateStaticAnswers($staticAnswers);
        $this->validateAiAnswers($aiQuestions, $aiAnswers);

        $scores = $scoringService->accumulateScores(
            QuizStaticQuestions::all(),
            $staticAnswers,
            $aiQuestions,
            $aiAnswers,
        );

        $preview = $scoringService->buildPreview($scores, $profile);
        $context = $aiService->buildAdaptiveContext(
            $profile,
            $staticAnswers,
            $aiQuestions,
            $aiAnswers,
            $preview,
            $round,
        );

        $result = $aiService->generateAdaptiveQuestions($context);

        return response()->json([
            'complete' => $result['complete'],
            'questions' => $result['questions'],
            'clarity' => $result['clarity'],
            'source' => $aiService->isAvailable() ? 'ai' : 'local',
            'message' => $result['message'] ?? null,
        ]);
    }

    private function validateStaticAnswers(array $staticAnswers): void
    {
        foreach (QuizStaticQuestions::all() as $question) {
            $id = $question['id'];
            if (! array_key_exists($id, $staticAnswers)) {
                throw ValidationException::withMessages([
                    'static_answers' => 'Ответь на все базовые вопросы.',
                ]);
            }

            $valid = collect($question['options'] ?? [])
                ->contains(fn ($option) => ($option['id'] ?? null) === $staticAnswers[$id]);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'static_answers' => 'Выбран недопустимый вариант ответа.',
                ]);
            }
        }
    }

    private function validateAiAnswers(array $aiQuestions, array $aiAnswers): void
    {
        foreach ($aiQuestions as $question) {
            $questionId = $question['id'] ?? null;

            if (! $questionId || ! array_key_exists($questionId, $aiAnswers)) {
                throw ValidationException::withMessages([
                    'ai_answers' => 'Ответь на все вопросы текущего блока.',
                ]);
            }

            $valid = collect($question['options'] ?? [])
                ->contains(fn ($option) => ($option['id'] ?? null) === $aiAnswers[$questionId]);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'ai_answers' => 'Выбран недопустимый вариант ответа.',
                ]);
            }
        }
    }
}
