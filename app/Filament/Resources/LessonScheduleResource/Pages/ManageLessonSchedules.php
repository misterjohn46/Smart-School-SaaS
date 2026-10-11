<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonScheduleResource\Pages;

use App\Filament\Resources\LessonScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLessonSchedules extends ManageRecords
{
    protected static string $resource = LessonScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Jadwal Pelajaran'),
        ];
    }
}
