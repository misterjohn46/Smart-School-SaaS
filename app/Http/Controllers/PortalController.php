<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\StudentBill;
use App\Models\StudentProfile;
use App\Models\StudentWallet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    /**
     * Dashboard Portal Siswa (Mobile-First)
     */
    public function student(Request $request): Response
    {
        $user = $request->user();

        // Ambil profil siswa terhubung
        $profile = StudentProfile::with(['classroom.homeroomTeacher', 'classroom.department', 'classroom.academicYear'])
            ->where('user_id', $user->id)
            ->first();

        // Data dummy dinamis untuk demonstrasi interaktif
        $todaySchedule = [
            [
                'time' => '07:15 - 08:45',
                'subject' => 'Pemrograman Web & Mobile Devices',
                'teacher' => 'Bpk. Ahmad Fauzi, S.Pd',
                'room' => 'Lab Komputer RPL 1',
                'status' => 'Selesai',
            ],
            [
                'time' => '09:00 - 10:30',
                'subject' => 'Basis Data & Big Data Analysis',
                'teacher' => 'Ibu Sri Wahyuni, M.Kom',
                'room' => 'Lab Komputer RPL 2',
                'status' => 'Berlangsung',
            ],
            [
                'time' => '10:45 - 12:15',
                'subject' => 'Matematika Terapan & Diskrit',
                'teacher' => 'Drs. H. Mulyono',
                'room' => 'Ruang Teori X-1',
                'status' => 'Mendatang',
            ],
        ];

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $attendanceToday = [
            'status' => $todayAttendance ? ucfirst($todayAttendance->status) : 'Belum Presensi',
            'check_in_time' => $todayAttendance?->time_in ? date('H:i', strtotime($todayAttendance->time_in)).' WIB' : '-',
            'method' => $todayAttendance?->method ? strtoupper($todayAttendance->method) : 'RFID Gate Standalone (ESP32)',
            'rfid_uid' => $profile?->rfid_uid ?? 'RFID-SISWA-001',
        ];

        // Ambil Dompet Siswa dari database
        $studentWallet = $profile ? StudentWallet::where('student_profile_id', $profile->id)->first() : null;
        $wallet = [
            'balance' => (float) ($studentWallet?->balance ?? 85000),
            'daily_limit' => (float) ($studentWallet?->daily_limit ?? 50000),
            'spent_today' => (float) ($studentWallet?->spent_today ?? 0),
            'account_number' => $studentWallet?->account_number ?? 'WAL-0051234567',
        ];

        // Ambil Tagihan SPP Siswa dari database
        $studentBills = $profile ? StudentBill::where('student_profile_id', $profile->id)->with('feeCategory')->get() : collect();
        $unpaidBillsCount = $studentBills->whereIn('status', ['unpaid', 'partial'])->count();
        $totalArrears = $studentBills->whereIn('status', ['unpaid', 'partial'])->sum(fn ($b) => $b->remaining_amount);

        $cbtExam = [
            'is_active' => true,
            'title' => 'Simulasi Penilaian Akhir Semester (PAS)',
            'subject' => 'Pemrograman Web',
            'duration_minutes' => 90,
            'token' => 'SMK-BISA',
            'mode' => 'Semi-Offline ANBK & Exambro APK',
        ];

        return Inertia::render('Portal/StudentDashboard', [
            'student' => [
                'id' => $profile?->id,
                'name' => $user->name,
                'email' => $user->email,
                'nisn' => $profile?->nisn ?? $user->nisn ?? '0051234567',
                'classroom' => $profile?->classroom?->name ?? 'X - RPL 1',
                'department' => $profile?->classroom?->department?->name ?? 'Rekayasa Perangkat Lunak',
                'homeroom_teacher' => $profile?->classroom?->homeroomTeacher?->name ?? 'Bpk. Ahmad Fauzi, S.Pd',
            ],
            'attendance' => $attendanceToday,
            'schedule' => $todaySchedule,
            'wallet' => $wallet,
            'cbtExam' => $cbtExam,
            'finances' => [
                'unpaid_count' => $unpaidBillsCount,
                'total_arrears' => (float) $totalArrears,
                'bills' => $studentBills->map(fn ($b) => [
                    'title' => $b->title,
                    'amount' => (float) $b->amount,
                    'paid_amount' => (float) $b->paid_amount,
                    'remaining' => (float) $b->remaining_amount,
                    'status' => $b->status,
                ]),
            ],
        ]);
    }

    /**
     * Dashboard Portal Orang Tua / Wali
     */
    public function parent(Request $request): Response
    {
        $user = $request->user();

        // Ambil data putra/putri yang terhubung dengan orang tua ini
        $children = StudentProfile::with(['classroom.homeroomTeacher', 'classroom.department', 'user', 'wallet', 'bills.feeCategory'])
            ->where('parent_user_id', $user->id)
            ->get()
            ->map(function ($child) {
                $todayAttendance = Attendance::where('user_id', $child->user_id)
                    ->whereDate('date', now()->toDateString())
                    ->first();

                $unpaidBills = $child->bills->whereIn('status', ['unpaid', 'partial']);
                $totalTunggakan = $unpaidBills->sum(fn ($b) => $b->remaining_amount);

                return [
                    'id' => $child->id,
                    'name' => $child->full_name,
                    'nisn' => $child->nisn,
                    'classroom' => $child->classroom?->name ?? 'X - RPL 1',
                    'homeroom_teacher' => $child->classroom?->homeroomTeacher?->name ?? 'Bpk. Ahmad Fauzi, S.Pd',
                    'rfid_uid' => $child->rfid_uid,
                    'attendance_today' => [
                        'status' => $todayAttendance ? ucfirst($todayAttendance->status) : 'Hadir Tepat Waktu',
                        'time' => $todayAttendance?->time_in ? date('H:i', strtotime($todayAttendance->time_in)).' WIB' : '06:48 WIB',
                        'gate' => 'Gerbang Utama Siswa',
                    ],
                    'bills' => [
                        'spp_status' => $unpaidBills->isEmpty() ? 'Lunas (Bulan Berjalan)' : 'Ada '.$unpaidBills->count().' Tunggakan',
                        'total_arrears' => (float) $totalTunggakan,
                        'savings_balance' => (float) ($child->wallet?->balance ?? 125000),
                        'daily_limit' => (float) ($child->wallet?->daily_limit ?? 50000),
                    ],
                    'bk_notes' => [
                        [
                            'date' => '10 Sep 2026',
                            'category' => 'Prestasi',
                            'note' => 'Juara 2 Lomba Web Design Tingkat Kota Jakarta',
                            'points' => '+25 Poin Karakter',
                        ],
                    ],
                ];
            });

        return Inertia::render('Portal/ParentDashboard', [
            'parent' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? '081234567890',
            ],
            'children' => $children,
        ]);
    }
}
