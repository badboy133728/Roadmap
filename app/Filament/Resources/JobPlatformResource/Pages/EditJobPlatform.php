<?php

namespace App\Filament\Resources\JobPlatformResource\Pages;

use App\Filament\Resources\JobPlatformResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobPlatform extends EditRecord
{
    protected static string $resource = JobPlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
