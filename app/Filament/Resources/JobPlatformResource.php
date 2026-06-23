<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobPlatformResource\Pages;
use App\Models\JobPlatform;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JobPlatformResource extends Resource
{
    protected static ?string $model = JobPlatform::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Работа';

    protected static ?string $navigationLabel = 'Площадки поиска';

    protected static ?string $modelLabel = 'площадка';

    protected static ?string $pluralModelLabel = 'Площадки поиска';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->label('Название')->required(),
                Forms\Components\TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('url')->label('Сайт')->url()->required(),
                Forms\Components\TextInput::make('search_url_template')
                    ->label('Шаблон поиска')
                    ->required()
                    ->helperText('Используй {query} — подставится название профессии и город'),
                Forms\Components\TextInput::make('icon')->label('Эмодзи'),
                Forms\Components\Textarea::make('description')->label('Описание')->columnSpanFull(),
                Forms\Components\TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Активна')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icon')->label(''),
                Tables\Columns\TextColumn::make('name')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('url')->label('URL')->limit(40),
                Tables\Columns\IconColumn::make('is_active')->label('Активна')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobPlatforms::route('/'),
            'create' => Pages\CreateJobPlatform::route('/create'),
            'edit' => Pages\EditJobPlatform::route('/{record}/edit'),
        ];
    }
}
