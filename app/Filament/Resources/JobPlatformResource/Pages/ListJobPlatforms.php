<?php

namespace App\Filament\Resources\JobPlatformResource\Pages;

use App\Filament\Resources\JobPlatformResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobPlatforms extends ListRecords
{
    protected static string $resource = JobPlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
