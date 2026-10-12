<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Attendance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ekspor rekapitulasi presensi ke XLSX / CSV.
 */
class AttendanceExporter extends Exporter
{
    protected static ?string $model = Attendance::class;

    /**
     * Pembatas tenant eksplisit.
     *
     * Ekspor dieksekusi lewat queue job dengan query yang diserialisasi, sehingga
     * tidak boleh bergantung pada konteks tenant di sisi worker. Filter ini
     * ditambahkan sebelum serialisasi agar rekap satu sekolah tidak pernah
     * memuat baris sekolah lain.
     *
     * @param  Builder<Attendance>  $query
     * @return Builder<Attendance>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        if (tenancy()->initialized) {
            $query->where($query->getModel()->qualifyColumn('tenant_id'), tenant()->getTenantKey());
        }

        return $query->with(['user', 'classroom', 'device']);
    }

    /**
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('date')
                ->label('Tanggal'),
            ExportColumn::make('user.name')
                ->label('Nama Pengguna')
                ->formatStateUsing(fn ($state): string => self::neutralizeFormula($state)),
            ExportColumn::make('user.nisn')
                ->label('NISN'),
            ExportColumn::make('classroom.name')
                ->label('Rombel Kelas')
                ->formatStateUsing(fn ($state): string => self::neutralizeFormula($state)),
            ExportColumn::make('status')
                ->label('Status Kehadiran')
                ->formatStateUsing(fn ($state): string => match ($state) {
                    'hadir' => 'Hadir',
                    'terlambat' => 'Terlambat',
                    'izin' => 'Izin',
                    'sakit' => 'Sakit',
                    'alpa' => 'Alpa (Tanpa Keterangan)',
                    default => (string) $state,
                }),
            ExportColumn::make('time_in')
                ->label('Jam Masuk'),
            ExportColumn::make('time_out')
                ->label('Jam Pulang'),
            ExportColumn::make('method')
                ->label('Metode Verifikasi')
                ->formatStateUsing(fn ($state): string => match ($state) {
                    'rfid' => 'IoT RFID Standalone (ESP32)',
                    'qrcode' => 'Scan QR Code Digital',
                    'face_recognition' => 'AI Face Recognition',
                    'gps_mobile' => 'GPS Mobile Geofencing',
                    'manual' => 'Input Manual Guru / TU',
                    default => (string) $state,
                }),
            ExportColumn::make('device.device_name')
                ->label('Perangkat Gerbang')
                ->formatStateUsing(fn ($state): string => self::neutralizeFormula($state)),
            ExportColumn::make('notes')
                ->label('Catatan Keterangan')
                ->formatStateUsing(fn ($state): string => self::neutralizeFormula($state)),
        ];
    }

    public function getFileName(Export $export): string
    {
        $school = tenancy()->initialized
            ? str($tenantName = (string) tenant()->school_name)->slug()->value() ?: 'sekolah'
            : 'pusat';

        return "rekap-presensi-{$school}-".now()->format('Ymd-His');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $rows = number_format($export->successful_rows, 0, ',', '.');
        $failed = $export->getFailedRowsCount();

        $body = "Ekspor rekap presensi selesai: {$rows} baris berhasil diekspor.";

        if ($failed > 0) {
            $body .= ' '.number_format($failed, 0, ',', '.').' baris gagal diekspor.';
        }

        return $body;
    }

    /**
     * Menetralkan nilai yang bisa ditafsirkan spreadsheet sebagai formula.
     *
     * Catatan & nama bersumber dari input pengguna. Nilai yang diawali
     * `=`, `+`, `-`, atau `@` dieksekusi Excel/Sheets sebagai formula
     * (CSV formula injection), sehingga diberi prefix apostrof.
     */
    protected static function neutralizeFormula(mixed $state): string
    {
        $value = (string) ($state ?? '');

        if ($value === '') {
            return '';
        }

        return in_array($value[0], ['=', '+', '-', '@'], true) ? "'".$value : $value;
    }
}
