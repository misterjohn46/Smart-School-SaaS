<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SchoolGeofence;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GpsGeofenceAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_haversine_distance_calculation(): void
    {
        $service = new AttendanceService();

        // Jarak antara 2 titik identik harus 0 meter
        $distSame = $service->calculateHaversineDistance(-6.2088, 106.8456, -6.2088, 106.8456);
        $this->assertEquals(0, round($distSame));

        // Jarak ~111 meter ke utara (selisih lat ~0.001 derajat)
        $dist = $service->calculateHaversineDistance(-6.2088, 106.8456, -6.2078, 106.8456);
        $this->assertGreaterThan(100, $dist);
        $this->assertLessThan(120, $dist);
    }

    public function test_teacher_within_geofence_radius_can_attend(): void
    {
        $teacher = User::where('email', 'guru@sekolah.id')->first();
        $this->assertNotNull($teacher);

        $geofence = SchoolGeofence::first();
        $this->assertNotNull($geofence);

        // Koordinat guru berada 20 meter dari titik sekolah (di dalam 100m radius)
        $response = $this->actingAs($teacher)->post('/attendance/gps-face', [
            'latitude' => $geofence->latitude + 0.0001,
            'longitude' => $geofence->longitude + 0.0001,
            'photo' => 'data:image/jpeg;base64,samplephoto123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $teacher->id,
            'method' => 'gps_mobile',
        ]);
    }

    public function test_teacher_outside_geofence_radius_is_rejected(): void
    {
        $teacher = User::where('email', 'guru@sekolah.id')->first();
        $geofence = SchoolGeofence::first();

        // Koordinat guru berada 5 km di luar sekolah
        $response = $this->actingAs($teacher)->post('/attendance/gps-face', [
            'latitude' => $geofence->latitude + 0.05,
            'longitude' => $geofence->longitude + 0.05,
        ]);

        $response->assertSessionHasErrors(['location']);
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $teacher->id,
            'method' => 'gps_mobile',
        ]);
    }
}
