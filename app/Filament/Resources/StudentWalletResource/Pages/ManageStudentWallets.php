<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentWalletResource\Pages;

use App\Filament\Resources\StudentWalletResource;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentWallets extends ManageRecords
{
    protected static string $resource = StudentWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
