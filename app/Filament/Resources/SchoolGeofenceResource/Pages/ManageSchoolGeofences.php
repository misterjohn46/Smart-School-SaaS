<?php

namespace App\Filament\Resources\SchoolGeofenceResource\Pages;

use App\Filament\Resources\SchoolGeofenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolGeofences extends ManageRecords
{
    protected static string $resource = SchoolGeofenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Titik Koordinat Sekolah'),
        ];
    }
}
