<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $quiz = Quiz::updateOrCreate(
            ['title' => 'Твой личный профориентационный тест'],
            [
                'description' => 'Индивидуальный тест: учитывает твой возраст, приоритеты и интересы.',
                'is_active' => true,
            ]
        );

        $questions = [
            [
                'emoji' => '🎮',
                'hint' => 'Нет правильного ответа — выбирай честно',
                'question' => 'Чем ты любишь заниматься в свободное время?',
                'options' => [
                    ['text' => 'Ковыряюсь в телефоне, компе, играх', 'interests' => ['it' => 3], 'professions' => []],
                    ['text' => 'Общаюсь, помогаю друзьям и близким', 'interests' => ['medicine' => 2, 'education' => 2, 'trade' => 1], 'professions' => []],
                    ['text' => 'Рисую, снимаю, монтирую, творю', 'interests' => ['creative' => 3], 'professions' => []],
                    ['text' => 'Чиню, собираю, мастерю руками', 'interests' => ['engineering' => 2, 'production' => 2], 'professions' => []],
                ],
            ],
            [
                'emoji' => '📚',
                'hint' => 'Вспомни, что давалось легче',
                'question' => 'Какой предмет в школе тебе заходил больше всего?',
                'options' => [
                    ['text' => 'Математика или информатика', 'interests' => ['it' => 2, 'science' => 2, 'engineering' => 1], 'professions' => ['programmist' => 2]],
                    ['text' => 'Биология или химия', 'interests' => ['medicine' => 2, 'science' => 2], 'professions' => []],
                    ['text' => 'Литература, обществознание, история', 'interests' => ['creative' => 2, 'education' => 1, 'law' => 1], 'professions' => []],
                    ['text' => 'Физкультура', 'interests' => ['beauty' => 2, 'security' => 1, 'transport' => 1], 'professions' => []],
                ],
            ],
            [
                'emoji' => '🏢',
                'question' => 'Где тебе комфортнее работать?',
                'options' => [
                    ['text' => 'За компьютером — дома или в офисе', 'interests' => ['it' => 2, 'law' => 1, 'trade' => 1], 'professions' => []],
                    ['text' => 'На улице, в цеху, «в поле»', 'interests' => ['production' => 2, 'engineering' => 1, 'transport' => 1], 'professions' => []],
                    ['text' => 'Среди людей — клиника, школа, сервис', 'interests' => ['medicine' => 3, 'education' => 1], 'professions' => []],
                    ['text' => 'Где угодно — главное удалёнка', 'interests' => ['it' => 2, 'creative' => 2], 'professions' => []],
                ],
            ],
            [
                'emoji' => '⏱️',
                'question' => 'Сколько готов(а) учиться перед работой?',
                'options' => [
                    ['text' => '1–2 года — хочу быстрее начать', 'interests' => ['trade' => 2, 'beauty' => 2, 'transport' => 1], 'professions' => []],
                    ['text' => '4–6 лет — готов(а) в вуз', 'interests' => ['it' => 1, 'medicine' => 2, 'engineering' => 2, 'law' => 1], 'professions' => []],
                    ['text' => 'Учиться всю жизнь — ок', 'interests' => ['science' => 2, 'medicine' => 1, 'education' => 2], 'professions' => []],
                    ['text' => 'Хочу подработку уже сейчас', 'interests' => ['trade' => 2, 'transport' => 2, 'security' => 1], 'professions' => []],
                ],
            ],
            [
                'emoji' => '💭',
                'question' => 'Что тебя больше цепляет?',
                'options' => [
                    ['text' => 'Технологии, нейросети, гаджеты', 'interests' => ['it' => 3, 'engineering' => 1], 'professions' => ['programmist' => 2]],
                    ['text' => 'Природа, живые организмы', 'interests' => ['medicine' => 1, 'science' => 2, 'production' => 1], 'professions' => []],
                    ['text' => 'Люди, эмоции, общение', 'interests' => ['education' => 2, 'trade' => 2, 'beauty' => 1], 'professions' => []],
                    ['text' => 'Правила, документы, порядок', 'interests' => ['law' => 3], 'professions' => ['yurist' => 2, 'buhgalter' => 1]],
                ],
            ],
            [
                'emoji' => '🧠',
                'question' => 'Какая задача тебе ближе?',
                'options' => [
                    ['text' => 'Копать в данных и искать закономерности', 'interests' => ['it' => 2, 'science' => 2, 'law' => 1], 'professions' => ['analitik-dannyh' => 3]],
                    ['text' => 'Создавать что-то красивое', 'interests' => ['creative' => 3, 'beauty' => 1], 'professions' => ['dizayner' => 2]],
                    ['text' => 'Организовывать людей и процессы', 'interests' => ['trade' => 2, 'law' => 1, 'transport' => 1], 'professions' => ['logist' => 2]],
                    ['text' => 'Защищать и обеспечивать безопасность', 'interests' => ['security' => 3], 'professions' => []],
                ],
            ],
            [
                'emoji' => '👶',
                'question' => 'Кем ты мечтал(а) стать в детстве?',
                'options' => [
                    ['text' => 'Врачом, спасателем, волонтёром', 'interests' => ['medicine' => 3, 'security' => 1], 'professions' => []],
                    ['text' => 'Инженером, космонавтом, изобретателем', 'interests' => ['engineering' => 2, 'science' => 2, 'it' => 1], 'professions' => []],
                    ['text' => 'Блогером, артистом, дизайнером', 'interests' => ['creative' => 3], 'professions' => []],
                    ['text' => 'Учителем или тренером', 'interests' => ['education' => 3], 'professions' => ['uchitel' => 1]],
                ],
            ],
            [
                'emoji' => '🎒',
                'target_statuses' => ['school_9'],
                'question' => 'После 9 класса ты скорее пойдёшь…',
                'options' => [
                    ['text' => 'В колледж — хочу профессию побыстрее', 'interests' => ['trade' => 2, 'beauty' => 2, 'production' => 1], 'professions' => []],
                    ['text' => 'В 10 класс — целюсь в вуз', 'interests' => ['it' => 1, 'medicine' => 1, 'law' => 1], 'professions' => []],
                    ['text' => 'Пока не решил(а) — нужна помощь с выбором', 'interests' => ['education' => 1, 'trade' => 1], 'professions' => []],
                    ['text' => 'Хочу совмещать учёбу и подработку', 'interests' => ['trade' => 2, 'transport' => 1, 'creative' => 1], 'professions' => []],
                ],
            ],
            [
                'emoji' => '📝',
                'target_statuses' => ['school_11'],
                'question' => 'К ЕГЭ ты готовишься как…',
                'options' => [
                    ['text' => 'Уже знаю профиль — подбираю предметы под профессию', 'interests' => ['it' => 1, 'medicine' => 1, 'engineering' => 1], 'professions' => []],
                    ['text' => 'Сдаю то, что лучше получается', 'interests' => ['trade' => 1, 'law' => 1], 'professions' => []],
                    ['text' => 'Пока в стрессе — не понимаю, куда поступать', 'interests' => ['education' => 1], 'professions' => []],
                    ['text' => 'Смотрю на проходные баллы и зарплаты', 'interests' => ['it' => 2, 'law' => 1, 'trade' => 1], 'professions' => []],
                ],
            ],
            [
                'emoji' => '💼',
                'target_statuses' => ['working', 'student'],
                'question' => 'Если честно — что тебя не устраивает сейчас?',
                'options' => [
                    ['text' => 'Мало зарабатываю', 'interests' => ['it' => 2, 'trade' => 2, 'law' => 1], 'professions' => []],
                    ['text' => 'Скучно, нет развития', 'interests' => ['it' => 2, 'creative' => 2, 'science' => 1], 'professions' => []],
                    ['text' => 'Не моё — хочу другую сферу', 'interests' => ['education' => 1, 'medicine' => 1, 'creative' => 1], 'professions' => []],
                    ['text' => 'В целом норм, просто смотрю варианты', 'interests' => ['trade' => 1, 'it' => 1], 'professions' => []],
                ],
            ],
        ];

        foreach ($questions as $index => $questionData) {
            $question = QuizQuestion::updateOrCreate(
                ['quiz_id' => $quiz->id, 'sort_order' => $index + 1],
                [
                    'question' => $questionData['question'],
                    'emoji' => $questionData['emoji'] ?? null,
                    'hint' => $questionData['hint'] ?? null,
                    'target_statuses' => $questionData['target_statuses'] ?? null,
                    'question_type' => empty($questionData['target_statuses']) ? 'main' : 'status',
                ]
            );

            foreach ($questionData['options'] as $optionData) {
                QuizOption::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'text' => $optionData['text']],
                    [
                        'interest_scores' => $optionData['interests'],
                        'profession_scores' => $optionData['professions'],
                    ]
                );
            }
        }
    }
}
