<?php

declare(strict_types=1);

namespace App\Filament\Resources\CbtNodeResource\Pages;

use App\Filament\Resources\CbtNodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCbtNodes extends ManageRecords
{
    protected static string $resource = CbtNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Node CBT Baru'),
        ];
    }
}
