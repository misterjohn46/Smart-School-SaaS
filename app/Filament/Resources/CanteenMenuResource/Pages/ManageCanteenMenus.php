<?php

declare(strict_types=1);

namespace App\Filament\Resources\CanteenMenuResource\Pages;

use App\Filament\Resources\CanteenMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCanteenMenus extends ManageRecords
{
    protected static string $resource = CanteenMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Menu Kantin'),
        ];
    }
}
