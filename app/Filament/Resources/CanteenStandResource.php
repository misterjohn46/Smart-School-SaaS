<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasSchoolAccessOnly;
use App\Filament\Resources\CanteenStandResource\Pages;
use App\Models\CanteenStand;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CanteenStandResource extends Resource
{
    use HasSchoolAccessOnly;

    protected static ?string $model = CanteenStand::class;

    protected static string|UnitEnum|null $navigationGroup = 'E-Kantin Sekolah';

    protected static ?string $navigationLabel = 'Stand & Vendor Kantin';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nama Stand Kantin')
                    ->placeholder('Contoh: Kantin Barokah Sehat')
                    ->required()
                    ->maxLength(100),
                TextInput::make('stand_number')
                    ->label('Nomor / Blok Stand')
                    ->placeholder('Contoh: Stand 01')
                    ->required()
                    ->maxLength(20),
                Select::make('user_id')
                    ->label('Akun Pengelola / Vendor')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('phone')
                    ->label('Nomor WhatsApp Vendor')
                    ->tel()
                    ->placeholder('081234567890'),
                Toggle::make('is_active')
                    ->label('Buka Hari Ini (Status Aktif)')
                    ->default(true),
                Textarea::make('description')
                    ->label('Deskripsi / Catatan Stand')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stand_number')
                    ->label('No. Stand')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Stand Kantin')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Pengelola Vendor')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Kontak WA')
                    ->placeholder('-'),
                TextColumn::make('total_revenue')
                    ->label('Total Omset')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color('success'),
                IconColumn::make('is_active')
                    ->label('Buka')
                    ->boolean(),
            ])
            ->actions([
                Actions\Action::make('pos')
                    ->label('Buka POS')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('primary')
                    ->url(fn (CanteenStand $record): string => route('canteen.stand.pos', $record->id))
                    ->openUrlInNewTab(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCanteenStands::route('/'),
        ];
    }
}
