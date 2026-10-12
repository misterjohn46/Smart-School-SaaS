<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasSchoolAccessOnly;
use App\Filament\Resources\KtsTemplateResource\Pages;
use App\Models\KtsTemplate;
use Filament\Actions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class KtsTemplateResource extends Resource
{
    use HasSchoolAccessOnly;

    protected static ?string $model = KtsTemplate::class;

    protected static string|UnitEnum|null $navigationGroup = 'KTS & Kartu Digital';

    protected static ?string $navigationLabel = 'Template KTS (ISO CR-80)';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('template_name')
                    ->label('Nama Template')
                    ->required()
                    ->maxLength(100),
                ColorPicker::make('primary_color')
                    ->label('Warna Dominan (Header / Background)')
                    ->default('#4f46e5')
                    ->required(),
                ColorPicker::make('secondary_color')
                    ->label('Warna Aksen (Badge / Garis)')
                    ->default('#f59e0b')
                    ->required(),
                Select::make('background_pattern')
                    ->label('Motif Latar Belakang Kartu')
                    ->options([
                        'geometric' => 'Geometris Modern',
                        'minimalist' => 'Minimalis Elegan',
                        'wave' => 'Gelombang Dinamis',
                    ])
                    ->default('geometric')
                    ->required(),
                TextInput::make('signatory_name')
                    ->label('Nama Pejabat Penandatangan')
                    ->default('Dr. H. Bambang Sudarmono, M.Pd')
                    ->required(),
                TextInput::make('signatory_title')
                    ->label('Jabatan Penandatangan')
                    ->default('Kepala Sekolah')
                    ->required(),
                Toggle::make('show_rfid_chip')
                    ->label('Tampilkan Ikon Chip / RFID Frequency')
                    ->default(true),
                Toggle::make('show_barcode')
                    ->label('Tampilkan Barcode NISN di Sisi Belakang')
                    ->default(true),
                Toggle::make('is_active')
                    ->label('Jadikan Template Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('template_name')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('primary_color')
                    ->label('Warna Utama'),
                ColorColumn::make('secondary_color')
                    ->label('Warna Aksen'),
                TextColumn::make('signatory_name')
                    ->label('Penandatangan')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                Actions\Action::make('preview')
                    ->label('Preview Kartu')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url('/kts/preview', shouldOpenInNewTab: true),
                Actions\Action::make('batch_print')
                    ->label('Cetak Massal A4')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url('/kts/batch-print', shouldOpenInNewTab: true),

                Actions\Action::make('batch_print_pdf')
                    ->label('Unduh PDF (ISO CR-80)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url('/kts/batch-print/pdf', shouldOpenInNewTab: true),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKtsTemplates::route('/'),
        ];
    }
}
