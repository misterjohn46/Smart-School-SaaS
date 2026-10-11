<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SchoolGeofence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSettingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');

        $this->admin = User::where('email', 'admin@sekolah.id')->firstOrFail();
    }

    public function test_admin_can_view_attendance_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/attendance/settings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Attendance/Settings')
            ->has('geofences')
            ->has('activeGeofence')
            ->has('stats')
        );
    }

    public function test_admin_can_create_new_geofence_location(): void
    {
        $response = $this->actingAs($this->admin)->post('/attendance/geofences', [
            'name' => 'Kampus 2 - Bengkel Otomotif',
            'address' => 'Jl. Industri No. 12',
            'latitude' => -6.2100000,
            'longitude' => 106.8500000,
            'radius_meters' => 150,
            'check_in_start' => '06:00:00',
            'check_in_end' => '07:15:00',
            'late_tolerance_minutes' => 15,
            'check_out_start' => '15:00:00',
            'check_out_end' => '18:00:00',
            'work_days' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_geofences', [
            'name' => 'Kampus 2 - Bengkel Otomotif',
            'radius_meters' => 150,
        ]);
    }

    public function test_admin_can_update_geofence_location(): void
    {
        $geofence = SchoolGeofence::first();
        $this->assertNotNull($geofence);

        $id = $geofence->hashid ?? $geofence->id;

        $response = $this->actingAs($this->admin)->put("/attendance/geofences/{$id}", [
            'name' => 'Kampus Utama SMK Terpadu',
            'address' => 'Jl. Merdeka No. 1',
            'latitude' => -6.2088000,
            'longitude' => 106.8456000,
            'radius_meters' => 120,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_geofences', [
            'id' => $geofence->id,
            'name' => 'Kampus Utama SMK Terpadu',
            'radius_meters' => 120,
        ]);
    }

    public function test_admin_can_update_working_hours(): void
    {
        $response = $this->actingAs($this->admin)->post('/attendance/settings/working-hours', [
            'check_in_start' => '05:45:00',
            'check_in_end' => '07:00:00',
            'late_tolerance_minutes' => 10,
            'check_out_start' => '15:30:00',
            'check_out_end' => '18:30:00',
            'work_days' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_geofences', [
            'check_in_end' => '07:00:00',
            'late_tolerance_minutes' => 10,
        ]);
    }

    public function test_admin_can_toggle_geofence_status(): void
    {
        $g1 = SchoolGeofence::first();
        $g2 = SchoolGeofence::create([
            'name' => 'Kampus Cabang',
            'latitude' => -6.2200000,
            'longitude' => 106.8600000,
            'radius_meters' => 100,
            'is_active' => false,
        ]);

        $id = $g2->hashid ?? $g2->id;

        $response = $this->actingAs($this->admin)->post("/attendance/geofences/{$id}/toggle");
        $response->assertRedirect();

        $this->assertTrue($g2->fresh()->is_active);
        $this->assertFalse($g1->fresh()->is_active);
    }
}
