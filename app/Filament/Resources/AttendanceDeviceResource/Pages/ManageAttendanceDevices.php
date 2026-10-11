<?php

namespace App\Filament\Resources\AttendanceDeviceResource\Pages;

use App\Filament\Resources\AttendanceDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAttendanceDevices extends ManageRecords
{
    protected static string $resource = AttendanceDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Daftarkan Perangkat ESP32 Baru'),
        ];
    }
}
