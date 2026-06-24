<?php

return [

  'enabled' => env('AI_ENABLED', true),

  'provider' => env('AI_PROVIDER', 'openai'),

  'openai' => [
      'api_key' => env('OPENAI_API_KEY'),
      'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
      'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
      'timeout' => (int) env('AI_TIMEOUT', 25),
  ],

  'follow_up_questions' => (int) env('AI_FOLLOW_UP_QUESTIONS', 4),

  'clarity_threshold' => (int) env('AI_CLARITY_THRESHOLD', 72),

  'max_ai_rounds' => (int) env('AI_MAX_ROUNDS', 5),

  'max_ai_questions_total' => (int) env('AI_MAX_QUESTIONS_TOTAL', 12),

];
