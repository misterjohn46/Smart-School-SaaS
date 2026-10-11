<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IotAttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Endpoint API untuk memproses Tap Kartu RFID dari Mikrokontroler ESP32 via WiFi
     */
    public function tapRfid(Request $request): JsonResponse
    {
        // 1. Validasi Token Perangkat IoT pada Header
        $deviceToken = $request->header('X-Device-Token') ?: $request->input('device_token');

        if (! $deviceToken) {
            return response()->json([
                'success' => false,
                'beep' => 'error',
                'lcd_line1' => 'AUTH ERROR',
                'lcd_line2' => 'NO TOKEN',
                'message' => 'Header X-Device-Token wajib disertakan oleh mikrokontroler.',
            ], 401);
        }

        $device = AttendanceDevice::where('device_token', $deviceToken)->where('is_active', true)->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'beep' => 'error',
                'lcd_line1' => 'AUTH ERROR',
                'lcd_line2' => 'INVALID DEVICE',
                'message' => 'Perangkat IoT tidak terdaftar atau telah dinonaktifkan.',
            ], 401);
        }

        // Update status perangkat (heartbeat ping)
        $device->update([
            'last_ping_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        // 2. Validasi UID Kartu RFID yang dikirim oleh ESP32
        $request->validate([
            'rfid_uid' => ['required', 'string', 'max:50'],
        ]);

        $rfidUid = trim((string) $request->input('rfid_uid'));

        // 3. Proses Logika Presensi Cepat
        $result = $this->attendanceService->recordRfidTap($rfidUid, $device);

        $httpStatus = $result['success'] ? 200 : 422;

        return response()->json($result, $httpStatus);
    }

    /**
     * Ping / Heartbeat endpoint untuk memeriksa koneksi ESP32 ke server
     */
    public function ping(Request $request): JsonResponse
    {
        $deviceToken = $request->header('X-Device-Token') ?: $request->input('device_token');

        $device = AttendanceDevice::where('device_token', $deviceToken)->first();

        if ($device) {
            $device->update([
                'last_ping_at' => now(),
                'ip_address' => $request->ip(),
            ]);
        }

        return response()->json([
            'success' => true,
            'server_time' => now()->format('Y-m-d H:i:s'),
            'device_registered' => (bool) $device,
        ]);
    }
}
