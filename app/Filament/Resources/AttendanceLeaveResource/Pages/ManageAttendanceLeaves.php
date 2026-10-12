<?php

namespace App\Filament\Resources\AttendanceLeaveResource\Pages;

use App\Filament\Resources\AttendanceLeaveResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAttendanceLeaves extends ManageRecords
{
    protected static string $resource = AttendanceLeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
