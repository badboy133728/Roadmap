<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait CallsOpenAiJson
{
    protected function callOpenAiJson(string $system, string $userPrompt, float $temperature = 0.7, int $maxTokens = 4000): ?array
    {
        if (! config('ai.enabled') || ! filled(config('ai.openai.api_key'))) {
            return null;
        }

        $timeout = max((int) config('ai.openai.timeout'), 45);

        $response = Http::withToken(config('ai.openai.api_key'))
            ->timeout($timeout)
            ->post(rtrim(config('ai.openai.base_url'), '/') . '/chat/completions', [
                'model' => config('ai.openai.model'),
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI JSON request failed', [
                'service' => static::class,
                'status' => $response->status(),
            ]);

            return null;
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
