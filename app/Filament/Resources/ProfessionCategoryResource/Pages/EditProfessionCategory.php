<?php

namespace App\Filament\Resources\ProfessionCategoryResource\Pages;

use App\Filament\Resources\ProfessionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProfessionCategory extends EditRecord
{
    protected static string $resource = ProfessionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
