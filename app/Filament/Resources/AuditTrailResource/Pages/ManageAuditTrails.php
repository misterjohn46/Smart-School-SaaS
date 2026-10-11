<?php

namespace App\Filament\Resources\AuditTrailResource\Pages;

use App\Filament\Resources\AuditTrailResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAuditTrails extends ManageRecords
{
    protected static string $resource = AuditTrailResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Read-only audit log
    }
}
