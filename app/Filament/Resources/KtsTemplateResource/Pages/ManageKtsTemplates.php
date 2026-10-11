<?php

namespace App\Filament\Resources\KtsTemplateResource\Pages;

use App\Filament\Resources\KtsTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKtsTemplates extends ManageRecords
{
    protected static string $resource = KtsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Template Desain KTS Baru'),
        ];
    }
}
