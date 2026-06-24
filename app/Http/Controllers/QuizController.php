<?php

namespace App\Http\Controllers;

use App\Models\QuizResult;
use App\Services\QuizAiService;
use App\Services\QuizScoringService;
use App\Services\QuizStaticQuestions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function show(): View
    {
        return view('quiz.show', [
            'staticQuestions' => QuizStaticQuestions::all(),
        ]);
    }

    public function submit(Request $request, QuizScoringService $scoringService): RedirectResponse
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
            'ai_answers.*' => ['required', 'string'],
        ]);

        $profile = [
            'name' => $request->input('name') ? trim($request->input('name')) : null,
            'about' => $request->input('about') ? trim($request->input('about')) : null,
            'status' => $request->input('status'),
            'priorities' => array_values(array_unique($request->input('priorities', []))),
        ];

        $staticAnswers = $request->input('static_answers', []);
        $aiQuestions = json_decode($request->input('ai_questions', '[]'), true) ?: [];
        $aiAnswers = $request->input('ai_answers', []);

        $this->validateStaticAnswers($staticAnswers);
        $this->validateAiAnswers($aiQuestions, $aiAnswers);

        $scored = $scoringService->scoreAdaptive(
            $profile,
            QuizStaticQuestions::all(),
            $staticAnswers,
            $aiQuestions,
            $aiAnswers,
        );

        $sessionId = (string) Str::uuid();

        QuizResult::create([
            'user_id' => auth()->id(),
            'session_id' => $sessionId,
            'answers' => $staticAnswers,
            'recommendations' => [
                'list' => $scored['recommendations'],
                'archetype' => $scored['archetype'],
                'profile' => $scored['profile'],
                'greeting' => $scored['greeting'],
                'interest_scores' => $scored['interest_scores'],
                'interest_profile' => $scored['interest_profile'],
                'status_advice' => $scored['status_advice'],
                'ai_questions' => $aiQuestions,
                'ai_answers' => $aiAnswers,
            ],
        ]);

        return redirect()->route('quiz.result', $sessionId);
    }

    public function result(string $sessionId, QuizAiService $aiService): View
    {
        $result = QuizResult::query()
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $payload = $result->recommendations ?? [];
        $isLegacy = isset($payload[0]) && is_array($payload[0]);

        if (! $isLegacy) {
            $cached = $payload['ai_insights'] ?? null;

            if (! $aiService->isCompleteInsights($cached)) {
                $payload['ai_insights'] = $aiService->generate($result, $payload);
                $result->update(['recommendations' => $payload]);
            }
        }

        return view('quiz.result', [
            'result' => $result,
            'sessionId' => $sessionId,
            'recommendations' => $isLegacy ? $payload : ($payload['list'] ?? []),
            'archetype' => $isLegacy ? null : ($payload['archetype'] ?? null),
            'profile' => $isLegacy ? null : ($payload['profile'] ?? null),
            'greeting' => $isLegacy ? null : ($payload['greeting'] ?? null),
            'interestScores' => $isLegacy ? [] : ($payload['interest_scores'] ?? []),
            'interestProfile' => $isLegacy ? [] : ($payload['interest_profile'] ?? []),
            'statusAdvice' => $isLegacy ? null : ($payload['status_advice'] ?? null),
            'aiInsights' => $isLegacy ? null : ($payload['ai_insights'] ?? null),
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
                    'ai_answers' => 'Ответь на все уточняющие вопросы.',
                ]);
            }

            $selectedId = $aiAnswers[$questionId];
            $valid = collect($question['options'] ?? [])
                ->contains(fn ($option) => ($option['id'] ?? null) === $selectedId);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'ai_answers' => 'Выбран недопустимый вариант в уточняющем вопросе.',
                ]);
            }
        }
    }
}
