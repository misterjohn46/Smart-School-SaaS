<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Exports\AttendanceExporter;
use App\Models\Attendance;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ekspor rekapitulasi presensi.
 *
 * ROADMAP sebelumnya mengklaim fitur ini selesai, padahal AttendanceResource
 * tidak punya aksi ekspor sama sekali.
 */
class AttendanceExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    private function recordAttendance(string $status = 'hadir', ?string $notes = null): Attendance
    {
        $student = StudentProfile::firstOrFail();

        return Attendance::create([
            'user_id' => $student->user_id,
            'classroom_id' => $student->classroom_id,
            'date' => now()->toDateString(),
            'time_in' => '06:45:00',
            'status' => $status,
            'method' => 'rfid',
            'notes' => $notes,
        ]);
    }

    public function test_exporter_columns_cover_the_recap_fields(): void
    {
        $labels = array_map(
            static fn ($column): string => (string) $column->getLabel(),
            AttendanceExporter::getColumns()
        );

        foreach (['Tanggal', 'Nama Pengguna', 'NISN', 'Rombel Kelas', 'Status Kehadiran', 'Jam Masuk', 'Jam Pulang', 'Metode Verifikasi'] as $expected) {
            $this->assertContains($expected, $labels);
        }
    }

    public function test_exported_row_contains_the_recap_values(): void
    {
        $student = StudentProfile::firstOrFail();
        $attendance = $this->recordAttendance();

        $row = AttendanceExporter::test()->export($attendance->fresh());

        $this->assertContains($student->user->name, $row);
        $this->assertContains('Hadir', $row);
        $this->assertContains('IoT RFID Standalone (ESP32)', $row);
    }

    public function test_status_and_method_are_written_in_readable_indonesian(): void
    {
        $attendance = $this->recordAttendance('terlambat');

        $row = AttendanceExporter::test()->export($attendance->fresh());

        $this->assertContains('Terlambat', $row);
        $this->assertContains('IoT RFID Standalone (ESP32)', $row);
        $this->assertNotContains('terlambat', $row);
        $this->assertNotContains('rfid', $row);
    }

    public function test_free_text_that_looks_like_a_formula_is_neutralized(): void
    {
        $attendance = $this->recordAttendance('hadir', '=HYPERLINK("http://jahat.test","klik")');

        $row = AttendanceExporter::test()->export($attendance->fresh());

        $notes = collect($row)->first(fn ($value): bool => is_string($value) && str_contains($value, 'HYPERLINK'));

        $this->assertNotNull($notes, 'Kolom catatan tidak ikut terekspor.');
        $this->assertStringStartsWith("'=", $notes);
    }

    public function test_export_query_is_limited_to_the_active_school(): void
    {
        foreach (['admin', 'siswa'] as $role) {
            Role::findOrCreate($role);
        }

        // Sekolah lain beserta satu baris presensinya.
        $otherTenant = new Tenant([
            'id' => (string) Str::uuid(),
            'school_name' => 'SMK Tetangga',
            'npsn' => '55667788',
            'admin_name' => 'Admin Tetangga',
            'admin_email' => 'admin@tetangga.sch.id',
            'admin_phone' => '08129999999',
        ]);
        $otherTenant->requestedDomain = 'tetangga.localhost';
        $otherTenant->save();

        tenancy()->initialize($otherTenant);

        $otherUser = User::create([
            'name' => 'Siswa Tetangga',
            'email' => 'siswa@tetangga.sch.id',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        Attendance::create([
            'user_id' => $otherUser->id,
            'date' => now()->toDateString(),
            'time_in' => '07:00:00',
            'status' => 'hadir',
            'method' => 'rfid',
        ]);

        // Query ekspor dari dalam konteks sekolah tetangga hanya memuat barisnya sendiri.
        $scoped = AttendanceExporter::modifyQuery(Attendance::query());
        $this->assertSame(1, $scoped->count());
        $this->assertSame($otherUser->id, $scoped->firstOrFail()->user_id);

        tenancy()->end();

        // Baris sekolah lama tetap ada di database (dua sekolah, dua baris).
        $this->recordAttendance();
        $this->assertSame(2, Attendance::withoutTenancy()->count());
    }
}
