<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IotRfidAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_tap_rfid_with_valid_token_and_registered_student(): void
    {
        $device = AttendanceDevice::where('device_token', 'ESP32-GATE-MAIN-001')->first();
        $this->assertNotNull($device);

        $student = StudentProfile::where('nisn', '0051234567')->first();
        $this->assertNotNull($student);
        $this->assertNotNull($student->rfid_uid);

        // Simulasi request dari ESP32
        $response = $this->withHeaders([
            'X-Device-Token' => 'ESP32-GATE-MAIN-001',
        ])->postJson('/api/v1/attendance/tap-rfid', [
            'rfid_uid' => $student->rfid_uid,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'name' => $student->full_name,
                'role' => 'siswa',
            ]);

        // Verifikasi format respon untuk display LCD 16x2 dan audio buzzer ESP32
        $data = $response->json();
        $this->assertArrayHasKey('beep', $data);
        $this->assertArrayHasKey('lcd_line1', $data);
        $this->assertArrayHasKey('lcd_line2', $data);
        $this->assertArrayHasKey('time', $data);

        // Verifikasi tersimpan di tabel attendances
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->user_id,
            'method' => 'rfid',
            'device_id' => $device->id,
        ]);
    }

    public function test_second_tap_records_checkout_time(): void
    {
        $device = AttendanceDevice::where('device_token', 'ESP32-GATE-MAIN-001')->first();
        $student = StudentProfile::where('nisn', '0051234567')->first();

        // Tap pertama (Masuk)
        $this->withHeaders(['X-Device-Token' => $device->device_token])
            ->postJson('/api/v1/attendance/tap-rfid', ['rfid_uid' => $student->rfid_uid]);

        $attendance = Attendance::where('user_id', $student->user_id)->first();
        $this->assertNotNull($attendance->time_in);
        $this->assertNull($attendance->time_out);

        // Tap kedua (Pulang)
        $response = $this->withHeaders(['X-Device-Token' => $device->device_token])
            ->postJson('/api/v1/attendance/tap-rfid', ['rfid_uid' => $student->rfid_uid]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'Pulang',
                'beep' => 'double',
            ]);

        $attendance->refresh();
        $this->assertNotNull($attendance->time_out);
    }

    public function test_unregistered_rfid_card_is_rejected_with_error_beep(): void
    {
        $device = AttendanceDevice::where('device_token', 'ESP32-GATE-MAIN-001')->first();

        $response = $this->withHeaders(['X-Device-Token' => $device->device_token])
            ->postJson('/api/v1/attendance/tap-rfid', [
                'rfid_uid' => 'UNKNOWN-CARD-999',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'beep' => 'error',
                'status' => 'Ditolak',
                'lcd_line1' => 'KARTU DITOLAK',
            ]);
    }

    public function test_invalid_or_missing_device_token_is_unauthorized(): void
    {
        // Tanpa token
        $response1 = $this->postJson('/api/v1/attendance/tap-rfid', [
            'rfid_uid' => 'RFID-SISWA-001',
        ]);
        $response1->assertStatus(401);

        // Token ngawur
        $response2 = $this->withHeaders(['X-Device-Token' => 'TOKEN-PALSU-123'])
            ->postJson('/api/v1/attendance/tap-rfid', [
                'rfid_uid' => 'RFID-SISWA-001',
            ]);
        $response2->assertStatus(401);
    }

    public function test_device_ping_heartbeat_updates_device_status(): void
    {
        $device = AttendanceDevice::where('device_token', 'ESP32-GATE-MAIN-001')->first();

        $response = $this->withHeaders(['X-Device-Token' => $device->device_token])
            ->getJson('/api/v1/attendance/ping');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'device_registered' => true,
            ]);

        $device->refresh();
        $this->assertNotNull($device->last_ping_at);
    }
}
