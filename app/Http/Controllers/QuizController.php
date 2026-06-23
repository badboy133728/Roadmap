<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizResult;
use App\Services\QuizScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function show(): View
    {
        $quiz = Quiz::query()
            ->with(['questions' => fn ($q) => $q->orderBy('sort_order'), 'questions.options'])
            ->where('is_active', true)
            ->first();

        abort_unless($quiz, 503, 'Тест ещё не настроен. Подождите завершения загрузки данных.');

        return view('quiz.show', compact('quiz'));
    }

    public function submit(Request $request, QuizScoringService $scoringService): RedirectResponse
    {
        $quiz = Quiz::query()
            ->with('questions')
            ->where('is_active', true)
            ->firstOrFail();

        $request->validate([
            'name' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:school_9,school_11,student,working,exploring'],
            'priorities' => ['nullable', 'array', 'max:2'],
            'priorities.*' => ['in:money,creativity,people,stability,growth'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'exists:quiz_options,id'],
        ]);

        $profile = [
            'name' => $request->input('name') ? trim($request->input('name')) : null,
            'status' => $request->input('status'),
            'priorities' => array_values(array_unique($request->input('priorities', []))),
        ];

        $answers = $request->input('answers', []);

        $applicableQuestions = $quiz->questions->filter(function ($question) use ($profile) {
            if (empty($question->target_statuses)) {
                return true;
            }

            return in_array($profile['status'], $question->target_statuses, true);
        });

        $questionIds = $applicableQuestions->pluck('id')->all();

        foreach ($questionIds as $questionId) {
            if (! array_key_exists($questionId, $answers)) {
                throw ValidationException::withMessages([
                    'answers' => 'Ответь на все вопросы теста.',
                ]);
            }

            $optionBelongsToQuestion = QuizOption::query()
                ->where('id', $answers[$questionId])
                ->where('quiz_question_id', $questionId)
                ->exists();

            if (! $optionBelongsToQuestion) {
                throw ValidationException::withMessages([
                    'answers' => 'Выбран недопустимый вариант ответа.',
                ]);
            }
        }

        $scored = $scoringService->score($quiz, $answers, $profile);

        $sessionId = (string) Str::uuid();

        QuizResult::create([
            'user_id' => auth()->id(),
            'session_id' => $sessionId,
            'answers' => $answers,
            'recommendations' => [
                'list' => $scored['recommendations'],
                'archetype' => $scored['archetype'],
                'profile' => $scored['profile'],
                'greeting' => $scored['greeting'],
                'interest_scores' => $scored['interest_scores'],
                'interest_profile' => $scored['interest_profile'],
                'status_advice' => $scored['status_advice'],
            ],
        ]);

        return redirect()->route('quiz.result', $sessionId);
    }

    public function result(string $sessionId): View
    {
        $result = QuizResult::query()
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $payload = $result->recommendations ?? [];
        $isLegacy = isset($payload[0]) && is_array($payload[0]);

        return view('quiz.result', [
            'result' => $result,
            'recommendations' => $isLegacy ? $payload : ($payload['list'] ?? []),
            'archetype' => $isLegacy ? null : ($payload['archetype'] ?? null),
            'profile' => $isLegacy ? null : ($payload['profile'] ?? null),
            'greeting' => $isLegacy ? null : ($payload['greeting'] ?? null),
            'interestScores' => $isLegacy ? [] : ($payload['interest_scores'] ?? []),
            'interestProfile' => $isLegacy ? [] : ($payload['interest_profile'] ?? []),
            'statusAdvice' => $isLegacy ? null : ($payload['status_advice'] ?? null),
        ]);
    }
}
