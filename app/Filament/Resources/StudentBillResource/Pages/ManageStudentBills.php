<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentBillResource\Pages;

use App\Filament\Resources\StudentBillResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\FeeCategory;
use App\Services\FinanceService;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentBills extends ManageRecords
{
    protected static string $resource = StudentBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Tagihan Manual'),
            Actions\Action::make('batchGenerate')
                ->label('⚡ Generate Tagihan Massal Rombel')
                ->color('success')
                ->icon('heroicon-o-sparkles')
                ->form([
                    Select::make('academic_year_id')
                        ->label('Tahun Ajaran')
                        ->options(AcademicYear::pluck('name', 'id'))
                        ->default(AcademicYear::where('is_active', true)->value('id'))
                        ->required(),
                    Select::make('fee_category_id')
                        ->label('Kategori Biaya')
                        ->options(FeeCategory::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($category = FeeCategory::find($state)) {
                                $set('amount', $category->default_amount);
                            }
                        }),
                    Select::make('classroom_ids')
                        ->label('Pilih Rombel / Kelas Target')
                        ->options(Classroom::pluck('name', 'id'))
                        ->multiple()
                        ->required(),
                    Select::make('month')
                        ->label('Bulan Tagihan')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ])
                        ->default(now()->month),
                    TextInput::make('year')
                        ->label('Tahun Tagihan')
                        ->numeric()
                        ->default(now()->year)
                        ->required(),
                    TextInput::make('amount')
                        ->label('Nominal per Siswa (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                    DatePicker::make('due_date')
                        ->label('Batas Jatuh Tempo')
                        ->default(now()->addDays(15))
                        ->required(),
                ])
                ->action(function (array $data, FinanceService $financeService) {
                    $count = $financeService->generateBatchBills(
                        (int) $data['academic_year_id'],
                        (int) $data['fee_category_id'],
                        array_map('intval', $data['classroom_ids']),
                        ! empty($data['month']) ? (int) $data['month'] : null,
                        (int) $data['year'],
                        (float) $data['amount'],
                        $data['due_date'] ?? null
                    );

                    Notification::make()
                        ->title("Sukses Men-generate {$count} Tagihan Siswa")
                        ->success()
                        ->send();
                }),
        ];
    }
}
