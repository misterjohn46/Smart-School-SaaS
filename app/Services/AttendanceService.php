<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\SchoolGeofence;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    /**
     * Jeda minimum antar tap kartu RFID yang sama (detik).
     *
     * Sesuai ARCHITECTURE.md: mencegah kartu yang sama di-tap berulang kali dalam
     * rentang 3 menit sehingga tidak ada duplikasi / pemalsuan data presensi.
     */
    public const RFID_TAP_COOLDOWN_SECONDS = 180;

    /**
     * Sisa waktu cooldown (detik) sejak tap terakhir pada rekaman presensi.
     */
    protected function remainingTapCooldown(Attendance $attendance, Carbon $now): int
    {
        $lastTapTime = $attendance->time_out ?: $attendance->time_in;

        if (! $lastTapTime) {
            return 0;
        }

        // Kolom `date` di-cast 'date' sehingga selalu Carbon.
        $date = Carbon::parse($attendance->date)->toDateString();

        try {
            $lastTap = Carbon::parse($date.' '.$lastTapTime);
        } catch (\Exception) {
            return 0;
        }

        $elapsed = $lastTap->diffInSeconds($now, false);

        if ($elapsed < 0 || $elapsed >= self::RFID_TAP_COOLDOWN_SECONDS) {
            return 0;
        }

        return (int) ceil(self::RFID_TAP_COOLDOWN_SECONDS - $elapsed);
    }

    /**
     * Memproses tap kartu RFID dari perangkat IoT mikrokontroler (ESP32)
     *
     * @return array{success: bool, beep: string, name: string, role: string, classroom: ?string, time: string, status: string, lcd_line1: string, lcd_line2: string, message?: string}
     */
    public function recordRfidTap(string $rfidUid, AttendanceDevice $device): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        // 1. Cari pemilik RFID UID (Siswa atau Guru)
        $studentProfile = StudentProfile::with(['user', 'classroom', 'parentUser'])->where('rfid_uid', $rfidUid)->first();
        $teacherProfile = $studentProfile ? null : TeacherProfile::with('user')->where('rfid_uid', $rfidUid)->first();

        if (! $studentProfile && ! $teacherProfile) {
            return [
                'success' => false,
                'beep' => 'error',
                'name' => 'TIDAK DIKENAL',
                'role' => 'unknown',
                'classroom' => null,
                'time' => $currentTime,
                'status' => 'Ditolak',
                'lcd_line1' => 'KARTU DITOLAK',
                'lcd_line2' => 'UID: '.substr($rfidUid, 0, 10),
                'message' => 'Kartu RFID belum terdaftar di sistem sekolah.',
            ];
        }

        $user = $studentProfile ? $studentProfile->user : $teacherProfile->user;
        $classroom = $studentProfile ? $studentProfile->classroom : null;
        $name = $studentProfile ? $studentProfile->full_name : $teacherProfile->full_name;
        $role = $studentProfile ? 'siswa' : 'guru';
        $className = $classroom ? $classroom->name : 'Staff/Guru';

        // 2. Cek apakah sudah ada rekaman presensi hari ini
        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();

        // 2b. Anti-double-tap: tolak tap berulang kartu yang sama dalam rentang cooldown
        //     agar kartu yang tersenggol dua kali tidak langsung tercatat sebagai pulang.
        if ($attendance) {
            $remainingCooldown = $this->remainingTapCooldown($attendance, $now);

            if ($remainingCooldown > 0) {
                return [
                    'success' => false,
                    'beep' => 'warning',
                    'name' => $name,
                    'role' => $role,
                    'classroom' => $className,
                    'time' => $currentTime.' WIB',
                    'status' => 'Sudah Tap',
                    'lcd_line1' => substr($name, 0, 16),
                    'lcd_line2' => 'Tunggu '.$remainingCooldown.' detik',
                    'message' => "Kartu baru saja di-tap. Silakan tunggu {$remainingCooldown} detik lagi.",
                ];
            }
        }

        // 3. Logika Jam Masuk vs Jam Pulang
        if (! $attendance) {
            // Jam Masuk (Check-In)
            $geofence = SchoolGeofence::where('is_active', true)->first();
            $checkInEnd = $geofence?->check_in_end ?? '07:15:00';
            $isLate = $currentTime > $checkInEnd;
            $status = $isLate ? 'terlambat' : 'hadir';

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'classroom_id' => $classroom?->id,
                'date' => $today,
                'time_in' => $currentTime,
                'status' => $status,
                'method' => 'rfid',
                'device_id' => $device->id,
                'device_name' => $device->device_name,
                'notes' => "Tap masuk via RFID Reader ({$device->location})",
            ]);

            // Kirim notifikasi WhatsApp ke orang tua jika siswa
            if ($studentProfile && $studentProfile->parent_phone_wa) {
                $this->sendWhatsAppAttendanceAlert($studentProfile, 'masuk', $currentTime, $status);
            }

            return [
                'success' => true,
                'beep' => $status === 'hadir' ? 'success' : 'warning',
                'name' => $name,
                'role' => $role,
                'classroom' => $className,
                'time' => $currentTime.' WIB',
                'status' => ucfirst($status),
                'lcd_line1' => substr($name, 0, 16),
                'lcd_line2' => ucfirst($status).' '.$now->format('H:i').' WIB',
                'message' => "Presensi masuk berhasil dicatat ({$status}).",
            ];
        }

        // Jika sudah tap masuk, dan ini tap kedua
        // Jika belum ada time_out, catat sebagai jam pulang
        if (empty($attendance->time_out)) {
            $attendance->update([
                'time_out' => $currentTime,
            ]);

            if ($studentProfile && $studentProfile->parent_phone_wa) {
                $this->sendWhatsAppAttendanceAlert($studentProfile, 'pulang', $currentTime, 'Pulang');
            }

            return [
                'success' => true,
                'beep' => 'double',
                'name' => $name,
                'role' => $role,
                'classroom' => $className,
                'time' => $currentTime.' WIB',
                'status' => 'Pulang',
                'lcd_line1' => substr($name, 0, 16),
                'lcd_line2' => 'Pulang '.$now->format('H:i').' WIB',
                'message' => 'Presensi pulang berhasil dicatat.',
            ];
        }

        // Sudah tap masuk dan pulang
        return [
            'success' => true,
            'beep' => 'success',
            'name' => $name,
            'role' => $role,
            'classroom' => $className,
            'time' => $currentTime.' WIB',
            'status' => 'Sudah Hadir',
            'lcd_line1' => substr($name, 0, 16),
            'lcd_line2' => 'Presensi Lengkap',
            'message' => 'Anda telah menyelesaikan presensi masuk dan pulang hari ini.',
        ];
    }

    /**
     * Memvalidasi dan mencatat presensi Guru via GPS Geofencing
     */
    public function recordGpsAttendance(User $user, float $latitude, float $longitude, ?string $photoPath = null): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        // Ambil konfigurasi geofence sekolah yang aktif
        $geofence = SchoolGeofence::where('is_active', true)->first();

        if (! $geofence) {
            // Default geofence jika belum di-set
            $geofence = (object) [
                'latitude' => -6.2088000,
                'longitude' => 106.8456000,
                'radius_meters' => 100,
            ];
        }

        // Hitung jarak menggunakan formula Haversine
        $distance = $this->calculateHaversineDistance(
            $latitude,
            $longitude,
            (float) $geofence->latitude,
            (float) $geofence->longitude
        );

        if ($distance > $geofence->radius_meters) {
            $distanceFormatted = number_format($distance, 1);

            return [
                'success' => false,
                'distance_meters' => $distance,
                'message' => "Anda berada di luar radius sekolah ({$distanceFormatted} m dari titik sekolah). Maksimal radius: {$geofence->radius_meters} m.",
            ];
        }

        $checkInEnd = $geofence->check_in_end ?? '07:15:00';
        $isLate = $currentTime > $checkInEnd;
        $status = $isLate ? 'terlambat' : 'hadir';

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            [
                'time_in' => $currentTime,
                'status' => $status,
                'method' => 'gps_mobile',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'photo_path' => $photoPath,
                'notes' => 'Presensi GPS Geofencing (Jarak: '.round($distance).'m)',
            ]
        );

        return [
            'success' => true,
            'distance_meters' => $distance,
            'status' => $status,
            'time' => $currentTime,
            'message' => "Presensi GPS berhasil! Anda berada {$distance}m di dalam area sekolah.",
        ];
    }

    /**
     * Menghitung jarak antara dua koordinat GPS menggunakan formula Haversine (hasil dalam meter)
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Simulasi Webhook / Pengiriman Notifikasi WhatsApp ke Orang Tua
     */
    protected function sendWhatsAppAttendanceAlert(StudentProfile $student, string $type, string $time, string $status): void
    {
        $phone = $student->parent_phone_wa;
        $studentName = $student->full_name;
        $className = $student->classroom?->name ?? '-';

        $message = match ($type) {
            'masuk' => "🔔 *NOTIFIKASI PRESENSI SEKOLAH*\n\nPutra/Putri Anda: *{$studentName}* ({$className})\nTelah hadir dan tap kartu RFID di gerbang sekolah pada pukul *{$time} WIB* (Status: *{$status}*).\n\n_SIAKAD & Smart Attendance Sekolah Terpadu_",
            'pulang' => "🔔 *NOTIFIKASI KEPULANGAN SISWA*\n\nPutra/Putri Anda: *{$studentName}* ({$className})\nTelah menyelesaikan kegiatan belajar dan tap kartu pulang pada pukul *{$time} WIB*.\n\n_SIAKAD & Smart Attendance Sekolah Terpadu_",
            default => "Pemberitahuan presensi untuk {$studentName}.",
        };

        // Catat log pengiriman WhatsApp tanpa membocorkan PII ke berkas log:
        // nomor orang tua dimasker dan isi pesan (berisi nama + kelas siswa) tidak dicatat.
        Log::info('Notifikasi WhatsApp presensi dikirim', [
            'student_profile_id' => $student->id,
            'type' => $type,
            'status' => $status,
            'phone_masked' => $this->maskPhoneNumber((string) $phone),
            'message_length' => mb_strlen($message),
        ]);
    }

    /**
     * Masker nomor telepon untuk keperluan logging (0812****789).
     */
    protected function maskPhoneNumber(string $phone): string
    {
        if ($phone === '') {
            return '-';
        }

        if (mb_strlen($phone) <= 7) {
            return str_repeat('*', mb_strlen($phone));
        }

        return mb_substr($phone, 0, 4).str_repeat('*', 4).mb_substr($phone, -3);
    }
}
