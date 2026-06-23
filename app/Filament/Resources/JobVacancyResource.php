<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobVacancyResource\Pages;
use App\Models\JobVacancy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JobVacancyResource extends Resource
{
    protected static ?string $model = JobVacancy::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Работа';

    protected static ?string $navigationLabel = 'Примеры вакансий';

    protected static ?string $modelLabel = 'вакансия';

    protected static ?string $pluralModelLabel = 'Примеры вакансий';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('profession_id')
                    ->label('Профессия')
                    ->relationship('profession', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('city_id')
                    ->label('Город')
                    ->relationship('city', 'name')
                    ->searchable(),
                Forms\Components\TextInput::make('title')->label('Название')->required(),
                Forms\Components\TextInput::make('company')->label('Компания')->required(),
                Forms\Components\TextInput::make('salary_text')->label('Зарплата (текст)'),
                Forms\Components\Textarea::make('description')->label('Описание')->columnSpanFull(),
                Forms\Components\TextInput::make('external_url')->label('Ссылка')->url()->required(),
                Forms\Components\TextInput::make('source')->label('Источник')->placeholder('hh.ru'),
                Forms\Components\Select::make('experience_level')
                    ->label('Уровень')
                    ->options([
                        'intern' => 'Стажировка',
                        'junior' => 'Junior / без опыта',
                        'middle' => 'Middle',
                    ]),
                Forms\Components\TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Активна')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('profession.name')->label('Профессия')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('city.name')->label('Город')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Вакансия')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('company')->label('Компания')->toggleable(),
                Tables\Columns\TextColumn::make('salary_text')->label('Зарплата'),
                Tables\Columns\IconColumn::make('is_active')->label('Активна')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('city_id')->label('Город')->relationship('city', 'name'),
                Tables\Filters\SelectFilter::make('profession_id')->label('Профессия')->relationship('profession', 'name'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobVacancies::route('/'),
            'create' => Pages\CreateJobVacancy::route('/create'),
            'edit' => Pages\EditJobVacancy::route('/{record}/edit'),
        ];
    }
}
