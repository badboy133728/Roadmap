<?php

namespace App\Http\Controllers;

use App\Models\QuizResult;
use App\Services\QuizAiService;
use Illuminate\Http\JsonResponse;

class QuizInsightController extends Controller
{
    public function __invoke(string $sessionId, QuizAiService $aiService): JsonResponse
    {
        $result = QuizResult::query()
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $payload = $result->recommendations ?? [];

        $cached = $payload['ai_insights'] ?? null;

        if ($aiService->isCompleteInsights($cached, $payload['profile'] ?? null)) {
            return response()->json($cached);
        }

        $insights = $aiService->generate($result, $payload);

        $payload['ai_insights'] = $insights;
        $result->update(['recommendations' => $payload]);

        return response()->json($insights);
    }
}
