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
     * Halaman Pengajuan & Verifikasi Izin / Sakit (E-Leave)
     */
    public function leaveIndex(Request $request): Response
    {
        $user = $request->user();
        $canReview = (bool) $user?->hasAnyRole(['admin', 'kepala_sekolah', 'operator', 'guru', 'guru_bk']);

        $leaves = AttendanceLeave::with('approver')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (AttendanceLeave $leave) => [
                'id' => $leave->id,
                'type' => $leave->type,
                'start_date' => $leave->start_date?->format('Y-m-d'),
                'end_date' => $leave->end_date?->format('Y-m-d'),
                'reason' => $leave->reason,
                'status' => $leave->status,
                'approver' => $leave->approver ? ['name' => $leave->approver->name] : null,
            ]);

        $allLeaves = [];
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        $statusFilter = $request->input('status', '');
        $typeFilter = $request->input('type', '');
        $search = $request->input('search', '');

        if ($canReview) {
            $query = AttendanceLeave::with([
                'user.studentProfile.classroom',
                'user.teacherProfile',
                'approver',
            ]);

            if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
                $query->where('status', $statusFilter);
            }

            if ($typeFilter && in_array($typeFilter, ['izin', 'sakit', 'tugas_luar'], true)) {
                $query->where('type', $typeFilter);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user.studentProfile', function ($sq) use ($search) {
                            $sq->where('full_name', 'like', "%{$search}%")
                                ->orWhere('nisn', 'like', "%{$search}%");
                        });
                });
            }

            $allLeaves = $query->latest('id')->take(100)->get()->map(function (AttendanceLeave $leave) {
                $student = $leave->user?->studentProfile;
                $teacher = $leave->user?->teacherProfile;
                $startDate = $leave->start_date ? Carbon::parse($leave->start_date) : null;
                $endDate = $leave->end_date ? Carbon::parse($leave->end_date) : null;
                $daysCount = ($startDate && $endDate) ? ($startDate->diffInDays($endDate) + 1) : 1;

                return [
                    'id' => $leave->id,
                    'applicant_name' => $student?->full_name ?? $teacher?->full_name ?? $leave->user?->name ?? 'Pengguna',
                    'applicant_role' => $student ? 'Siswa' : ($teacher ? 'Guru / PTK' : 'Staf'),
                    'applicant_identifier' => $student ? "NISN: {$student->nisn}" : ($teacher?->nip ? "NIP: {$teacher->nip}" : ($leave->user?->email ?? '-')),
                    'classroom_name' => $student?->classroom?->name ?? ($teacher ? 'Dewan Guru' : '-'),
                    'type' => $leave->type,
                    'start_date' => $startDate?->format('Y-m-d'),
                    'end_date' => $endDate?->format('Y-m-d'),
                    'start_date_formatted' => $startDate?->translatedFormat('d M Y') ?? '-',
                    'end_date_formatted' => $endDate?->translatedFormat('d M Y') ?? '-',
                    'days_count' => $daysCount,
                    'reason' => $leave->reason,
                    'status' => $leave->status,
                    'approver_name' => $leave->approver?->name,
                    'created_at_formatted' => $leave->created_at?->diffForHumans() ?? '-',
                ];
            });

            $stats = [
                'total' => AttendanceLeave::count(),
                'pending' => AttendanceLeave::where('status', 'pending')->count(),
                'approved' => AttendanceLeave::where('status', 'approved')->count(),
                'rejected' => AttendanceLeave::where('status', 'rejected')->count(),
            ];
        }

        return Inertia::render('Attendance/LeaveRequest', [
            'leaves' => $leaves,
            'canReview' => $canReview,
            'allLeaves' => $allLeaves,
            'stats' => $stats,
            'filters' => [
                'status' => $statusFilter,
                'type' => $typeFilter,
                'search' => $search,
            ],
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

    /**
     * Verifikasi & Persetujuan / Penolakan Permohonan Izin / Sakit (Oleh Guru / Admin / Kepsek)
     */
    public function reviewLeave(Request $request, int|string $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $leave = AttendanceLeave::with('user.studentProfile')->findOrFail($id);

        $leave->update([
            'status' => $validated['status'],
            'approved_by' => $request->user()->id,
        ]);

        // Jika disetujui, sinkronkan otomatis ke tabel presensi harian (attendances)
        if ($validated['status'] === 'approved' && $leave->start_date && $leave->end_date) {
            $current = Carbon::parse($leave->start_date)->startOfDay();
            $end = Carbon::parse($leave->end_date)->startOfDay();
            $attendanceStatus = $leave->type === 'sakit' ? 'sakit' : 'izin';
            $classroomId = $leave->user?->studentProfile?->classroom_id;
            $daysProcessed = 0;

            while ($current->lte($end) && $daysProcessed < 14) {
                $dateStr = $current->toDateString();

                $existing = Attendance::where('user_id', $leave->user_id)
                    ->whereDate('date', $dateStr)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'status' => $attendanceStatus,
                        'classroom_id' => $classroomId ?? $existing->classroom_id,
                        'notes' => "E-Leave Disetujui ({$leave->type}): {$leave->reason}",
                    ]);
                } else {
                    Attendance::create([
                        'user_id' => $leave->user_id,
                        'classroom_id' => $classroomId,
                        'date' => $dateStr,
                        'status' => $attendanceStatus,
                        'method' => 'manual',
                        'notes' => "E-Leave Disetujui ({$leave->type}): {$leave->reason}",
                    ]);
                }

                $current->addDay();
                $daysProcessed++;
            }
        }

        $label = $validated['status'] === 'approved' ? 'disetujui dan disinkronkan ke buku presensi' : 'ditolak';

        return back()->with('status', "Permohonan izin/sakit berhasil {$label}.");
    }
}
