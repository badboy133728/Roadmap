<?php

namespace App\Services;

class QuizStaticQuestions
{
    public static function all(): array
    {
        return [
            [
                'id' => 'static_q1',
                'question' => 'Что тебе ближе в свободное время?',
                'emoji' => '⚡',
                'hint' => 'Базовый вопрос — помогает понять твой стиль',
                'options' => [
                    [
                        'id' => 'static_q1_a',
                        'text' => 'Разбираться в технологиях, играх, гаджетах',
                        'interest_scores' => ['it' => 3, 'science' => 1],
                    ],
                    [
                        'id' => 'static_q1_b',
                        'text' => 'Творить — рисовать, снимать, придумывать',
                        'interest_scores' => ['creative' => 3, 'beauty' => 1],
                    ],
                    [
                        'id' => 'static_q1_c',
                        'text' => 'Общаться, помогать друзьям, быть в команде',
                        'interest_scores' => ['medicine' => 2, 'education' => 2, 'trade' => 1],
                    ],
                    [
                        'id' => 'static_q1_d',
                        'text' => 'Делать что-то руками, чинить, собирать',
                        'interest_scores' => ['engineering' => 3, 'production' => 2],
                    ],
                ],
            ],
            [
                'id' => 'static_q2',
                'question' => 'Что для тебя важнее в будущей работе?',
                'emoji' => '🎯',
                'hint' => 'Второй базовый вопрос — про мотивацию',
                'options' => [
                    [
                        'id' => 'static_q2_a',
                        'text' => 'Высокий доход и карьерный рост',
                        'interest_scores' => ['it' => 2, 'law' => 2, 'trade' => 2],
                    ],
                    [
                        'id' => 'static_q2_b',
                        'text' => 'Стабильность и понятные правила',
                        'interest_scores' => ['law' => 2, 'security' => 2, 'medicine' => 1],
                    ],
                    [
                        'id' => 'static_q2_c',
                        'text' => 'Помогать людям и видеть результат',
                        'interest_scores' => ['medicine' => 3, 'education' => 2],
                    ],
                    [
                        'id' => 'static_q2_d',
                        'text' => 'Свобода, творчество, своё дело',
                        'interest_scores' => ['creative' => 3, 'trade' => 1],
                    ],
                ],
            ],
        ];
    }
}
