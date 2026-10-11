<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLeave;
use App\Models\SchoolGeofence;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Halaman Presensi GPS & AI Face Recognition (Guru & Siswa)
     */
    public function gpsFace(Request $request): Response
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $geofence = SchoolGeofence::where('is_active', true)->first() ?? (object) [
            'name' => 'Kampus Utama SMK',
            'latitude' => -6.2088000,
            'longitude' => 106.8456000,
            'radius_meters' => 100,
        ];

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        return Inertia::render('Attendance/GpsFaceAttendance', [
            'geofence' => $geofence,
            'attendanceToday' => $todayAttendance,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first() ?? 'guru',
            ],
        ]);
    }

    /**
     * Menyimpan presensi berbasis GPS Geofencing + Swafoto
     */
    public function storeGps(Request $request): RedirectResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'photo' => ['nullable', 'string'], // Base64 data URI
        ]);

        $user = $request->user();
        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        $photo = $request->input('photo');

        $result = $this->attendanceService->recordGpsAttendance($user, $lat, $lng, $photo);

        if (! $result['success']) {
            return back()->withErrors(['location' => $result['message']]);
        }

        return back()->with('status', $result['message']);
    }

    /**
     * Halaman Pengajuan Izin / Sakit (E-Leave)
     */
    public function leaveIndex(Request $request): Response
    {
        $user = $request->user();

        $leaves = AttendanceLeave::with('approver')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Attendance/LeaveRequest', [
            'leaves' => $leaves,
        ]);
    }

    /**
     * Simpan pengajuan izin / sakit
     */
    public function storeLeave(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => ['required', 'in:sakit,izin,tugas_luar'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        AttendanceLeave::create([
            'user_id' => $user->id,
            'type' => $request->input('type'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'reason' => $request->input('reason'),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Permohonan izin / sakit berhasil dikirimkan ke pihak sekolah.');
    }
}
