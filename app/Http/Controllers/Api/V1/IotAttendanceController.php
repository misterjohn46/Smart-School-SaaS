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
        // 1. Autentikasi perangkat. Token hanya diterima lewat header agar tidak
        //    pernah tercatat di access log web server sebagai query string.
        $device = $this->authenticateDevice($request);

        if (! $device instanceof AttendanceDevice) {
            return $device;
        }

        // 2. Validasi UID kartu RFID SEBELUM menulis apa pun ke database.
        $validated = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:50'],
        ]);

        $device->update([
            'last_ping_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        $rfidUid = trim((string) $validated['rfid_uid']);

        // 3. Proses Logika Presensi Cepat
        $result = $this->attendanceService->recordRfidTap($rfidUid, $device);

        $httpStatus = $result['success'] ? 200 : 422;

        return response()->json($result, $httpStatus);
    }

    /**
     * Ping / Heartbeat endpoint untuk memeriksa koneksi ESP32 ke server.
     *
     * Endpoint ini wajib membawa token valid. Sebelumnya endpoint mengembalikan
     * `device_registered: false` untuk token apa pun sehingga dapat dipakai
     * memverifikasi hasil brute force token tanpa autentikasi.
     */
    public function ping(Request $request): JsonResponse
    {
        $device = $this->authenticateDevice($request);

        if (! $device instanceof AttendanceDevice) {
            return $device;
        }

        $device->update([
            'last_ping_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'server_time' => now()->format('Y-m-d H:i:s'),
            'device_registered' => true,
            'device_name' => $device->device_name,
        ]);
    }

    /**
     * Autentikasi perangkat IoT lewat header X-Device-Token.
     *
     * @return AttendanceDevice|JsonResponse Perangkat aktif, atau respon 401 siap kirim.
     */
    protected function authenticateDevice(Request $request): AttendanceDevice|JsonResponse
    {
        $deviceToken = $request->header('X-Device-Token');

        if (! is_string($deviceToken) || $deviceToken === '') {
            return $this->unauthorizedResponse('NO TOKEN', 'Header X-Device-Token wajib disertakan oleh mikrokontroler.');
        }

        $device = AttendanceDevice::findByToken($deviceToken);

        if (! $device || ! $device->is_active) {
            return $this->unauthorizedResponse('INVALID DEVICE', 'Perangkat IoT tidak terdaftar atau telah dinonaktifkan.');
        }

        return $device;
    }

    protected function unauthorizedResponse(string $lcdLine2, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'beep' => 'error',
            'lcd_line1' => 'AUTH ERROR',
            'lcd_line2' => $lcdLine2,
            'message' => $message,
        ], 401);
    }
}
