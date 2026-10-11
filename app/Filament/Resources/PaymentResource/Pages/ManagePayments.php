<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openPosKasir')
                ->label('Buka POS Kasir SPP')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->url(fn (): string => route('finance.pos'))
                ->openUrlInNewTab(),
        ];
    }
}
