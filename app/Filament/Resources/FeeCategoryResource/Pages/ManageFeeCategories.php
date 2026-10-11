<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeCategoryResource\Pages;

use App\Filament\Resources\FeeCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFeeCategories extends ManageRecords
{
    protected static string $resource = FeeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Pos Biaya'),
        ];
    }
}
