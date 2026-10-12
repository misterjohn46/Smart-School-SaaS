<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasFinanceAccessOnly;
use App\Filament\Resources\StudentBillResource\Pages;
use App\Models\StudentBill;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class StudentBillResource extends Resource
{
    use HasFinanceAccessOnly;

    protected static ?string $model = StudentBill::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan & Billing';

    protected static ?string $navigationLabel = 'Tagihan Siswa (Invoices)';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('student_profile_id')
                    ->label('Siswa')
                    ->relationship('studentProfile', 'full_name')
                    ->searchable()
                    ->required(),
                Select::make('fee_category_id')
                    ->label('Kategori Biaya')
                    ->relationship('feeCategory', 'name')
                    ->searchable()
                    ->required(),
                Select::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('bill_number')
                    ->label('Nomor Invoice')
                    ->default(fn () => 'INV/'.date('Ym').'/'.strtoupper(Str::random(6)))
                    ->required()
                    ->disabled(),
                TextInput::make('title')
                    ->label('Judul Tagihan')
                    ->placeholder('Contoh: SPP Bulan September 2026')
                    ->required(),
                TextInput::make('amount')
                    ->label('Total Nominal Tagihan (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('paid_amount')
                    ->label('Telah Dibayar (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                Select::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Lunas (Unpaid)',
                        'partial' => 'Cicilan (Partial)',
                        'paid' => 'Lunas (Paid)',
                        'waived' => 'Dibebaskan (Beasiswa)',
                    ])
                    ->default('unpaid')
                    ->required(),
                DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->default(now()->addDays(15)),
                Textarea::make('notes')
                    ->label('Catatan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('bill_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->color('primary'),
                TextColumn::make('studentProfile.full_name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentProfile.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),
                TextColumn::make('title')
                    ->label('Pos Tagihan')
                    ->limit(25),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Dibayar')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                        'waived' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'partial' => 'Cicilan',
                        'unpaid' => 'Belum Lunas',
                        'waived' => 'Beasiswa',
                        default => ucfirst($state),
                    }),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Lunas',
                        'partial' => 'Cicilan',
                        'paid' => 'Lunas',
                    ]),
                SelectFilter::make('fee_category_id')
                    ->label('Kategori Biaya')
                    ->relationship('feeCategory', 'name'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStudentBills::route('/'),
        ];
    }
}
