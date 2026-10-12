<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAttendances extends ManageRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Ekspor mengikuti filter & pencarian tabel yang sedang aktif.
            Actions\ExportAction::make()
                ->label('Ekspor Rekap')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->exporter(AttendanceExporter::class),
            Actions\CreateAction::make()->label('Input Presensi Manual'),
        ];
    }
}
