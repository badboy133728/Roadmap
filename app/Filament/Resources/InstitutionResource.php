<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionResource\Pages;
use App\Filament\Resources\InstitutionResource\RelationManagers;
use App\Models\Institution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Образование';

    protected static ?string $navigationLabel = 'Учебные заведения';

    protected static ?string $modelLabel = 'заведение';

    protected static ?string $pluralModelLabel = 'Учебные заведения';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('city_id')
                    ->label('Город')
                    ->relationship('city', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')->label('Название')->required(),
                Forms\Components\Select::make('type')
                    ->label('Тип')
                    ->options([
                        'university' => 'Вуз',
                        'college' => 'Колледж',
                        'courses' => 'Курсы',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('website')->label('Сайт')->url(),
                Forms\Components\Textarea::make('address')->label('Адрес')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('city.name')->label('Город')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Тип'),
                Tables\Columns\TextColumn::make('education_programs_count')->label('Программ')->counts('educationPrograms'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city_id')->label('Город')->relationship('city', 'name'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EducationProgramsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutions::route('/'),
            'create' => Pages\CreateInstitution::route('/create'),
            'edit' => Pages\EditInstitution::route('/{record}/edit'),
        ];
    }
}
