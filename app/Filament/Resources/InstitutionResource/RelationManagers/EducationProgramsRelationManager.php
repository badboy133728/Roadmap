<?php

namespace App\Filament\Resources\InstitutionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EducationProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'educationPrograms';

    protected static ?string $title = 'Программы обучения';

    protected static ?string $modelLabel = 'программа';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('profession_id')
                    ->label('Профессия')
                    ->relationship('profession', 'name')
                    ->searchable(),
                Forms\Components\TextInput::make('name')->label('Название программы')->required(),
                Forms\Components\TextInput::make('duration_years')->label('Лет обучения')->numeric(),
                Forms\Components\TextInput::make('study_form')->label('Форма')->placeholder('очная, заочная'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('profession.name')->label('Профессия'),
                Tables\Columns\TextColumn::make('name')->label('Программа'),
                Tables\Columns\TextColumn::make('duration_years')->label('Лет'),
                Tables\Columns\TextColumn::make('study_form')->label('Форма'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
}
