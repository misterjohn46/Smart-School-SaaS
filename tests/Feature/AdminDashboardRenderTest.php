<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\SchoolRecentActivityWidget;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard Filament merender seluruh widget hasil discoverWidgets().
 * Test ini menangkap widget yang query / relasinya tidak sesuai skema.
 */
class AdminDashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_school_admin_dashboard_renders_without_error(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->firstOrFail();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_super_admin_dashboard_renders_without_error(): void
    {
        $superAdmin = User::where('email', 'superadmin@platform.id')->firstOrFail();

        $this->actingAs($superAdmin)->get('/admin')->assertOk();
    }

    /**
     * Widget dirender lazily lewat Livewire, sehingga query-nya tidak ikut
     * dieksekusi saat GET /admin. Panggil langsung agar relasi & kolom teruji.
     */
    public function test_recent_activity_widget_query_matches_the_schema(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->firstOrFail();
        $this->actingAs($admin);

        $activities = (new SchoolRecentActivityWidget)->getRecentActivities();

        $this->assertArrayHasKey('attendances', $activities);
        $this->assertArrayHasKey('payments', $activities);
    }

    public function test_recent_activity_widget_renders_rows_from_real_data(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->firstOrFail();
        $this->actingAs($admin);

        $student = StudentProfile::firstOrFail();
        Attendance::create([
            'user_id' => $student->user_id,
            'classroom_id' => $student->classroom_id,
            'date' => now()->toDateString(),
            'time_in' => '06:45:00',
            'status' => 'hadir',
            'method' => 'rfid',
        ]);

        Payment::create([
            'payment_number' => 'PAY/TEST/0001',
            'student_profile_id' => $student->id,
            'user_id' => $admin->id,
            'amount' => 250000,
            'payment_method' => 'cash',
            'payment_date' => now(),
            'status' => 'verified',
        ]);

        $activities = (new SchoolRecentActivityWidget)->getRecentActivities();

        $this->assertCount(1, $activities['attendances']);
        $this->assertSame('hadir', $activities['attendances']->first()->status);

        // Relasi eager load hanya dieksekusi bila ada baris; dengan 1 pembayaran
        // relasi yang salah nama akan melempar RelationNotFoundException.
        $this->assertCount(1, $activities['payments']);
        $this->assertSame($student->full_name, $activities['payments']->first()->studentProfile->full_name);
    }
}
