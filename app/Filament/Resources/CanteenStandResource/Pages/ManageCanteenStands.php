<?php

declare(strict_types=1);

namespace App\Filament\Resources\CanteenStandResource\Pages;

use App\Filament\Resources\CanteenStandResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCanteenStands extends ManageRecords
{
    protected static string $resource = CanteenStandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Stand Kantin'),
        ];
    }
}
