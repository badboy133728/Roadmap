<?php

namespace App\Filament\Resources\QuizResource\RelationManagers;

use App\Models\ProfessionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Вопросы';

    protected static ?string $modelLabel = 'вопрос';

    public function form(Form $form): Form
    {
        $categories = ProfessionCategory::orderBy('name')->pluck('name', 'slug');

        return $form
            ->schema([
                Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('emoji')
                    ->label('Эмодзи')
                    ->maxLength(10),
                Forms\Components\Textarea::make('question')
                    ->label('Текст вопроса')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('hint')
                    ->label('Подсказка')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Select::make('target_statuses')
                    ->label('Только для статусов')
                    ->multiple()
                    ->options([
                        'school_9' => '9 класс',
                        'school_11' => '10–11 класс',
                        'student' => 'Студент',
                        'working' => 'Работаю',
                        'exploring' => 'Ищу себя',
                    ])
                    ->helperText('Пусто = показывать всем'),
                Forms\Components\Repeater::make('options')
                    ->label('Варианты ответов')
                    ->relationship()
                    ->schema([
                        Forms\Components\Textarea::make('text')
                            ->label('Текст')
                            ->required()
                            ->rows(2),
                        Forms\Components\KeyValue::make('interest_scores')
                            ->label('Баллы по сферам (slug категории)')
                            ->keyLabel('Сфера')
                            ->valueLabel('Баллы')
                            ->helperText('Сферы: it, medicine, creative, law, trade…'),
                        Forms\Components\KeyValue::make('profession_scores')
                            ->label('Баллы по профессиям (slug)')
                            ->keyLabel('Профессия')
                            ->valueLabel('Баллы'),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('emoji')->label(''),
                Tables\Columns\TextColumn::make('question')->label('Вопрос')->limit(60)->searchable(),
                Tables\Columns\TextColumn::make('options_count')->label('Ответов')->counts('options'),
                Tables\Columns\TextColumn::make('target_statuses')
                    ->label('Статусы')
                    ->formatStateUsing(fn ($state) => $state ? implode(', ', $state) : 'все'),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
