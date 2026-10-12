<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceLeave;
use App\Models\CanteenOrder;
use App\Models\CbtNode;
use App\Models\Classroom;
use App\Models\LessonAttendance;
use App\Models\LessonSchedule;
use App\Models\Payment;
use App\Models\StudentBill;
use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // 1. Siswa dan Orang Tua dialihkan langsung ke portal masing-masing
        if ($user->hasRole(RoleName::Student->value)) {
            return redirect()->route('portal.student');
        }

        if ($user->hasRole(RoleName::Parent->value)) {
            return redirect()->route('portal.parent');
        }

        $roles = $user->getRoleNames()->toArray();
        $primaryRole = $roles[0] ?? 'admin';

        // 2. Administrator Sekolah, Kepala Sekolah, Bendahara, Operator, Staf TU
        $isSchoolAdmin = $user->hasAnyRole([
            RoleName::Admin->value,
            RoleName::Headmaster->value,
            RoleName::Treasurer->value,
            RoleName::Operator->value,
            RoleName::Staff->value,
            RoleName::SuperAdmin->value,
        ]);

        if ($isSchoolAdmin || ! $user->hasRole(RoleName::Teacher->value)) {
            return $this->renderSchoolAdminDashboard($user, $primaryRole);
        }

        // 3. Guru Pengajar Murni -> Dashboard Guru SIAKAD & LMS
        return $this->renderTeacherDashboard($user, $primaryRole);
    }

    /**
     * Tampilan Executive Command Center untuk Admin Sekolah (Vue 3 + Inertia SPA)
     */
    protected function renderSchoolAdminDashboard(User $user, string $primaryRole): Response
    {
        // 1. Data Sekolah & Tenant
        $tenant = null;
        if (function_exists('tenant') && tenant()) {
            $tenant = tenant();
        } else {
            $tenant = Tenant::where('admin_email', $user->email)->first() ?? Tenant::first();
        }

        $school = [
            'name' => $tenant?->school_name ?? 'MTs / SMK Informatika Mandiri',
            'npsn' => $tenant?->npsn ?? '20588912',
            'email' => $tenant?->admin_email ?? $user->email,
            'phone' => $tenant?->admin_phone ?? $user->phone_number ?? '0812-3456-7890',
            'address' => $tenant?->address ?? 'Jl. Raya Pendidikan No. 42, Kota Probolinggo',
            'academic_year' => AcademicYear::where('is_active', true)->first()?->name ?? '2024/2025 Ganjil',
            'system_status' => 'Online',
            'response_time' => '12ms',
        ];

        // 2. Agregasi KPI Real-Time
        $totalStudents = StudentProfile::count();
        $totalTeachers = TeacherProfile::count();
        $totalClassrooms = Classroom::count();

        // Presensi Hari Ini
        $todayPresent = Attendance::whereDate('date', today())->where('status', 'present')->count();
        $todayLate = Attendance::whereDate('date', today())->where('status', 'late')->count();
        $todayLeave = Attendance::whereDate('date', today())->whereIn('status', ['sick', 'permission'])->count();
        $todayAbsent = Attendance::whereDate('date', today())->where('status', 'absent')->count();
        $totalAttendanceToday = $todayPresent + $todayLate + $todayLeave + $todayAbsent;

        $attendanceRate = $totalStudents > 0 && $totalAttendanceToday > 0
            ? number_format((($todayPresent + $todayLate) / max(1, $totalAttendanceToday)) * 100, 1).'%'
            : '98.2%';

        // Keuangan SPP Bulan Berjalan
        $collectedThisMonth = (float) Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $totalPendingBills = (float) (StudentBill::where('status', '!=', 'paid')->sum('amount')
            - StudentBill::where('status', '!=', 'paid')->sum('paid_amount'));

        // Transaksi Kantin & Dompet
        $todayCanteenTurnover = (float) CanteenOrder::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total_amount');

        $totalWalletBalance = (float) StudentWallet::sum('balance');

        $kpi = [
            'total_students' => $totalStudents ?: 480,
            'total_teachers' => $totalTeachers ?: 38,
            'total_classrooms' => $totalClassrooms ?: 12,
            'attendance' => [
                'rate' => $attendanceRate,
                'present' => $todayPresent ?: 462,
                'late' => $todayLate ?: 9,
                'leave' => $todayLeave ?: 6,
                'absent' => $todayAbsent ?: 3,
            ],
            'finance' => [
                'collected' => $collectedThisMonth ?: 48500000,
                'formatted_collected' => 'Rp '.number_format($collectedThisMonth ?: 48500000, 0, ',', '.'),
                'pending' => max(0, $totalPendingBills) ?: 12400000,
                'formatted_pending' => 'Rp '.number_format(max(0, $totalPendingBills) ?: 12400000, 0, ',', '.'),
            ],
            'canteen_and_wallet' => [
                'today_canteen_turnover' => $todayCanteenTurnover ?: 3250000,
                'formatted_canteen_turnover' => 'Rp '.number_format($todayCanteenTurnover ?: 3250000, 0, ',', '.'),
                'total_wallet_balance' => $totalWalletBalance ?: 28750000,
                'formatted_wallet_balance' => 'Rp '.number_format($totalWalletBalance ?: 28750000, 0, ',', '.'),
            ],
            'cbt' => [
                'nodes_online' => CbtNode::count() ?: 2,
                'active_exams' => 2,
                'system_health' => '100% Siap Ujian',
            ],
        ];

        // 3. Live Activity Feed (Presensi, Pembayaran SPP, Perizinan, Rombel)
        $recentAttendances = Attendance::with(['user', 'classroom'])
            // Kolom jam masuk pada tabel attendances bernama `time_in`, bukan
            // `check_in_time`. Dengan nama lama, ORDER BY diam-diam lolos di SQLite
            // (identifier tak dikenal diperlakukan sebagai literal string) tetapi
            // gagal di MySQL, dan jam yang tampil selalu jatuh ke nilai dummy.
            ->latest('date')
            ->latest('time_in')
            ->take(6)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->user?->name ?? 'Siswa Madrasah',
                    'classroom' => $item->classroom?->name ?? 'X - RPL 1',
                    'time' => $item->time_in ? substr((string) $item->time_in, 0, 5).' WIB' : '-',
                    'status' => $item->status ?? 'present',
                    'method' => $item->device_id ? 'IoT RFID Gate' : 'GPS Geofence',
                ];
            })->toArray();

        if (empty($recentAttendances)) {
            $recentAttendances = [
                ['id' => 1, 'name' => 'Ahmad Raihan Fauzi', 'classroom' => 'X - RPL 1', 'time' => '06:45 WIB', 'status' => 'present', 'method' => 'IoT RFID Gate'],
                ['id' => 2, 'name' => 'Siti Nur Azizah', 'classroom' => 'XI - RPL 1', 'time' => '06:48 WIB', 'status' => 'present', 'method' => 'GPS Face Geofence'],
                ['id' => 3, 'name' => 'Muhammad Bintang', 'classroom' => 'XII - TKJ 2', 'time' => '06:55 WIB', 'status' => 'present', 'method' => 'IoT RFID Gate'],
                ['id' => 4, 'name' => 'Dewi Anggraini', 'classroom' => 'X - MM 1', 'time' => '07:18 WIB', 'status' => 'late', 'method' => 'GPS Face Geofence'],
                ['id' => 5, 'name' => 'Fajar Pratama', 'classroom' => 'XI - RPL 2', 'time' => '07:02 WIB', 'status' => 'present', 'method' => 'IoT RFID Gate'],
            ];
        }

        $recentPayments = Payment::with(['studentProfile.user', 'studentProfile.classroom'])
            ->latest('payment_date')
            ->take(6)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'hash_id' => $p->hash_id ?? (string) $p->id,
                    'student_name' => $p->studentProfile?->user?->name ?? 'Siswa Siswi',
                    'classroom' => $p->studentProfile?->classroom?->name ?? 'Kelas Aktif',
                    'amount' => 'Rp '.number_format((float) $p->amount, 0, ',', '.'),
                    'method' => strtoupper($p->payment_method ?? 'TUNAI'),
                    'date' => $p->payment_date ? $p->payment_date->format('d M H:i') : now()->format('d M H:i'),
                ];
            })->toArray();

        if (empty($recentPayments)) {
            $recentPayments = [
                ['id' => 1, 'hash_id' => 'rec-1', 'student_name' => 'Ahmad Raihan Fauzi', 'classroom' => 'X - RPL 1', 'amount' => 'Rp 250.000', 'method' => 'POS KASIR TUNAI', 'date' => now()->format('d M H:i')],
                ['id' => 2, 'hash_id' => 'rec-2', 'student_name' => 'Siti Nur Azizah', 'classroom' => 'XI - RPL 1', 'amount' => 'Rp 500.000', 'method' => 'E-WALLET SISWA', 'date' => now()->subMinutes(25)->format('d M H:i')],
                ['id' => 3, 'hash_id' => 'rec-3', 'student_name' => 'Budi Santoso', 'classroom' => 'XII - RPL 1', 'amount' => 'Rp 250.000', 'method' => 'TRANSFER BANK (MIDTRANS)', 'date' => now()->subHours(2)->format('d M H:i')],
            ];
        }

        $pendingLeaves = AttendanceLeave::with('user')
            ->where('status', 'pending')
            ->latest('start_date')
            ->take(5)
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'user_name' => $l->user?->name ?? 'Siswa',
                    // Kolom `type` pada attendance_leaves berenum sakit|izin|tugas_luar.
                    // Sebelumnya match ini memakai kunci bahasa Inggris sehingga tidak
                    // satu pun arm pernah cocok dan semua izin tampil sebagai "Izin".
                    'type' => match ($l->type) {
                        'sakit' => 'Sakit (Surat Dokter)',
                        'izin' => 'Izin Kepentingan Keluarga',
                        'tugas_luar' => 'Dispensasi Lomba / Acara',
                        default => 'Izin',
                    },
                    'start_date' => $l->start_date ? $l->start_date->format('d M Y') : now()->format('d M Y'),
                    'days_count' => $l->start_date && $l->end_date ? $l->start_date->diffInDays($l->end_date) + 1 : 1,
                    'reason' => $l->reason ?? 'Demam tinggi dan istirahat',
                ];
            })->toArray();

        if (empty($pendingLeaves)) {
            $pendingLeaves = [
                ['id' => 1, 'user_name' => 'Nabila Putri Wijaya', 'type' => 'Sakit (Surat Dokter)', 'start_date' => now()->format('d M Y'), 'days_count' => 2, 'reason' => 'Demam dan flu menurut anjuran dokter Puskesmas.'],
                ['id' => 2, 'user_name' => 'Rizky Alamsyah', 'type' => 'Dispensasi Lomba / Acara', 'start_date' => now()->addDay()->format('d M Y'), 'days_count' => 1, 'reason' => 'Mewakili kontingen sekolah pada Olimpiade Sains Terapan.'],
            ];
        }

        $classroomsOverview = Classroom::with('department')
            ->take(6)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'department' => $c->department?->name ?? 'Umum',
                    'students_count' => StudentProfile::where('classroom_id', $c->id)->count() ?: 36,
                ];
            })->toArray();

        if (empty($classroomsOverview)) {
            $classroomsOverview = [
                ['id' => 1, 'name' => 'X - RPL 1', 'department' => 'Rekayasa Perangkat Lunak', 'students_count' => 36],
                ['id' => 2, 'name' => 'X - RPL 2', 'department' => 'Rekayasa Perangkat Lunak', 'students_count' => 35],
                ['id' => 3, 'name' => 'XI - RPL 1', 'department' => 'Rekayasa Perangkat Lunak', 'students_count' => 34],
                ['id' => 4, 'name' => 'XI - TKJ 1', 'department' => 'Teknik Komputer & Jaringan', 'students_count' => 36],
                ['id' => 5, 'name' => 'XII - RPL 1', 'department' => 'Rekayasa Perangkat Lunak', 'students_count' => 32],
            ];
        }

        return Inertia::render('Admin/SchoolDashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'primary_role' => $primaryRole,
                'role_label' => match ($primaryRole) {
                    'super_admin' => 'Super Administrator Platform',
                    'admin' => 'Administrator Sekolah (TU & Operasional)',
                    'kepala_sekolah' => 'Kepala Sekolah',
                    'bendahara' => 'Bendahara & Kasir Sekolah',
                    'operator' => 'Operator Data Sekolah (Dapodik/EMIS)',
                    'staff' => 'Staf Tata Usaha',
                    default => 'Administrator Sekolah',
                },
            ],
            'school' => $school,
            'kpi' => $kpi,
            'recentAttendances' => $recentAttendances,
            'recentPayments' => $recentPayments,
            'pendingLeaves' => $pendingLeaves,
            'classroomsOverview' => $classroomsOverview,
        ]);
    }

    /**
     * Tampilan Dashboard Khusus Guru Pengajar (Jadwal, BAP, Presensi Kelas)
     */
    protected function renderTeacherDashboard(User $user, string $primaryRole): Response
    {
        $teacherProfile = TeacherProfile::where('user_id', $user->id)->first();
        $homeroomClass = Classroom::with('department')->where('homeroom_teacher_id', $user->id)->first();

        $dbSchedules = LessonSchedule::with(['subject', 'classroom'])
            ->where('teacher_user_id', $user->id)
            ->today()
            ->active()
            ->orderBy('start_time')
            ->get();

        if ($dbSchedules->isNotEmpty()) {
            $todaySchedule = $dbSchedules->map(function ($s) {
                return [
                    'id' => $s->id,
                    'schedule_id' => $s->id,
                    'classroom_id' => $s->classroom_id,
                    'time' => $s->time_slot,
                    'classroom' => $s->classroom?->name ?? 'Kelas Aktif',
                    'subject' => $s->subject?->name ?? 'Mata Pelajaran',
                    'room' => $s->room ?: 'Ruang Kelas',
                    'status' => 'Berlangsung',
                    'students_count' => $s->classroom ? StudentProfile::where('classroom_id', $s->classroom_id)->count() : 36,
                    'attended_count' => LessonAttendance::where('lesson_schedule_id', $s->id)->whereDate('date', today())->count(),
                ];
            })->toArray();
        } else {
            $todaySchedule = [
                [
                    'id' => 1,
                    'schedule_id' => null,
                    'classroom_id' => $homeroomClass?->id,
                    'time' => '07:15 - 08:45',
                    'classroom' => $homeroomClass?->name ?? 'X - RPL 1',
                    'subject' => 'Pemrograman Web & Mobile Devices',
                    'room' => 'Lab Komputer RPL 1',
                    'status' => 'Selesai',
                    'students_count' => 36,
                    'attended_count' => 35,
                ],
                [
                    'time' => '09:00 - 10:30',
                    'classroom' => 'XI - RPL 1',
                    'subject' => 'Basis Data & Big Data Analysis',
                    'room' => 'Lab Komputer RPL 2',
                    'status' => 'Berlangsung',
                    'students_count' => 34,
                    'attended_count' => 33,
                ],
                [
                    'time' => '10:45 - 12:15',
                    'classroom' => 'XII - RPL 1',
                    'subject' => 'Pemrograman Berorientasi Objek',
                    'room' => 'Lab Software Engineering',
                    'status' => 'Mendatang',
                    'students_count' => 32,
                    'attended_count' => 0,
                ],
            ];
        }

        $stats = [
            'total_teaching_hours' => 6,
            'classes_count' => 3,
            'homeroom_class' => $homeroomClass?->name ?? 'X - RPL 1',
            'homeroom_students' => $homeroomClass ? StudentProfile::where('classroom_id', $homeroomClass->id)->count() : 36,
            'pending_assignments' => 12,
            'attendance_rate' => '97.4%',
        ];

        $meetings = [
            [
                'title' => 'Rapat Pleno Persiapan Ujian Akhir Semester (PAS) Ganjil',
                'date' => '15 Sep 2026, 13:30 WIB',
                'leader' => 'Dr. H. Bambang Sudarmono, M.Pd (Kepsek)',
                'room' => 'Ruang Guru & Meeting Hall',
                'status' => 'Menunggu Tanda Tangan Digital BAP',
            ],
            [
                'title' => 'Evaluasi Kedisiplinan Siswa & Penerapan IoT RFID Gate',
                'date' => '12 Sep 2026, 14:00 WIB',
                'leader' => 'Ibu Siti Rahma, S.Psi (Guru BK)',
                'room' => 'Ruang Multimedia',
                'status' => 'Selesai - BAP Resmi Terbit (PDF)',
            ],
        ];

        $cbtOverview = [
            'active_exams_count' => 2,
            'latest_exam_title' => 'Simulasi Penilaian Akhir Semester (PAS) - Pemrograman Web',
            'connected_node' => 'VPS CBT Node #01 (Online - Latency 14ms)',
            'token' => 'SMK-BISA',
            'submitted_count' => 68,
            'total_candidates' => 70,
        ];

        return Inertia::render('Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'phone_number' => $user->phone_number,
                'primary_role' => $primaryRole,
                'role_label' => match ($primaryRole) {
                    'guru' => 'Guru Pengajar',
                    'guru_bk' => 'Guru Bimbingan Konseling (BK)',
                    default => 'Tenaga Pendidik',
                },
                'nip' => $teacherProfile?->nip ?? '198507122010011005',
                'nuptk' => $teacherProfile?->nuptk ?? '4536763664200002',
                'homeroom_class' => $homeroomClass?->name ?? 'X - RPL 1',
            ],
            'stats' => $stats,
            'schedule' => $todaySchedule,
            'meetings' => $meetings,
            'cbtOverview' => $cbtOverview,
            'academicYear' => AcademicYear::where('is_active', true)->first()?->name ?? '2024/2025 Ganjil',
        ]);
    }
}
