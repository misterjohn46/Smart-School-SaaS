<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\StudentProfile;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IotRfidAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const DEVICE_TOKEN = 'ESP32-GATE-MAIN-001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_tap_rfid_with_valid_token_and_registered_student(): void
    {
        $device = AttendanceDevice::findByToken(self::DEVICE_TOKEN);
        $this->assertNotNull($device);

        $student = StudentProfile::where('nisn', '0051234567')->first();
        $this->assertNotNull($student);
        $this->assertNotNull($student->rfid_uid);

        // Simulasi request dari ESP32
        $response = $this->withHeaders([
            'X-Device-Token' => self::DEVICE_TOKEN,
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

    public function test_second_tap_records_checkout_time_after_cooldown(): void
    {
        $student = StudentProfile::where('nisn', '0051234567')->first();

        Carbon::setTestNow(Carbon::parse('07:00:00'));

        // Tap pertama (Masuk)
        $this->withHeaders(['X-Device-Token' => self::DEVICE_TOKEN])
            ->postJson('/api/v1/attendance/tap-rfid', ['rfid_uid' => $student->rfid_uid]);

        $attendance = Attendance::where('user_id', $student->user_id)->first();
        $this->assertNotNull($attendance->time_in);
        $this->assertNull($attendance->time_out);

        // Lewati jeda anti-double-tap, lalu tap kedua (Pulang)
        Carbon::setTestNow(Carbon::parse('07:00:00')->addSeconds(AttendanceService::RFID_TAP_COOLDOWN_SECONDS + 1));

        $response = $this->withHeaders(['X-Device-Token' => self::DEVICE_TOKEN])
            ->postJson('/api/v1/attendance/tap-rfid', ['rfid_uid' => $student->rfid_uid]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'Pulang',
                'beep' => 'double',
            ]);

        $attendance->refresh();
        $this->assertNotNull($attendance->time_out);

        Carbon::setTestNow();
    }

    public function test_rapid_double_tap_is_rejected_without_recording_checkout(): void
    {
        $student = StudentProfile::where('nisn', '0051234567')->first();

        Carbon::setTestNow(Carbon::parse('07:00:00'));

        $this->withHeaders(['X-Device-Token' => self::DEVICE_TOKEN])
            ->postJson('/api/v1/attendance/tap-rfid', ['rfid_uid' => $student->rfid_uid])
            ->assertStatus(200);

        // Tap kedua hanya 2 detik berselang: harus ditolak, bukan dicatat sebagai pulang
        Carbon::setTestNow(Carbon::parse('07:00:02'));

        $response = $this->withHeaders(['X-Device-Token' => self::DEVICE_TOKEN])
            ->postJson('/api/v1/attendance/tap-rfid', ['rfid_uid' => $student->rfid_uid]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'Sudah Tap',
                'beep' => 'warning',
            ]);

        $this->assertNull(Attendance::where('user_id', $student->user_id)->first()->time_out);

        Carbon::setTestNow();
    }

    public function test_unregistered_rfid_card_is_rejected_with_error_beep(): void
    {
        $response = $this->withHeaders(['X-Device-Token' => self::DEVICE_TOKEN])
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
        $this->postJson('/api/v1/attendance/tap-rfid', [
            'rfid_uid' => 'RFID-SISWA-001',
        ])->assertStatus(401);

        // Token ngawur
        $this->withHeaders(['X-Device-Token' => 'TOKEN-PALSU-123'])
            ->postJson('/api/v1/attendance/tap-rfid', [
                'rfid_uid' => 'RFID-SISWA-001',
            ])->assertStatus(401);
    }

    public function test_device_token_is_not_accepted_from_query_string_or_body(): void
    {
        // Token lewat body/query tidak lagi diterima agar tidak bocor ke access log
        $this->postJson('/api/v1/attendance/tap-rfid', [
            'rfid_uid' => 'RFID-SISWA-001',
            'device_token' => self::DEVICE_TOKEN,
        ])->assertStatus(401);

        $this->getJson('/api/v1/attendance/ping?device_token='.self::DEVICE_TOKEN)
            ->assertStatus(401);
    }

    public function test_device_token_is_stored_as_hash_only(): void
    {
        $device = AttendanceDevice::findByToken(self::DEVICE_TOKEN);

        $this->assertSame(hash('sha256', self::DEVICE_TOKEN), $device->device_token_hash);
        $this->assertStringNotContainsString(self::DEVICE_TOKEN, (string) $device->device_token_preview);
        $this->assertDatabaseMissing('attendance_devices', [
            'device_token_hash' => self::DEVICE_TOKEN,
        ]);
    }

    public function test_regenerate_token_invalidates_the_previous_token(): void
    {
        $device = AttendanceDevice::findByToken(self::DEVICE_TOKEN);
        $newToken = $device->regenerateToken();

        $this->assertNotSame(self::DEVICE_TOKEN, $newToken);
        $this->assertNull(AttendanceDevice::findByToken(self::DEVICE_TOKEN));
        $this->assertNotNull(AttendanceDevice::findByToken($newToken));
    }

    public function test_device_ping_heartbeat_updates_device_status(): void
    {
        $device = AttendanceDevice::findByToken(self::DEVICE_TOKEN);

        $response = $this->withHeaders(['X-Device-Token' => self::DEVICE_TOKEN])
            ->getJson('/api/v1/attendance/ping');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'device_registered' => true,
            ]);

        $device->refresh();
        $this->assertNotNull($device->last_ping_at);
    }

    public function test_ping_does_not_disclose_whether_an_unknown_token_exists(): void
    {
        $this->withHeaders(['X-Device-Token' => 'TOKEN-PALSU-123'])
            ->getJson('/api/v1/attendance/ping')
            ->assertStatus(401)
            ->assertJsonMissing(['device_registered' => false]);
    }
}
