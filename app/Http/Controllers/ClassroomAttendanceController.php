<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\LessonAttendance;
use App\Models\LessonSchedule;
use App\Models\StudentProfile;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomAttendanceController extends Controller
{
    /**
     * Halaman Matriks Presensi Siswa per Kelas & per Jam Pelajaran
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $date = $request->input('date', Carbon::today()->toDateString());
        $carbonDate = Carbon::parse($date);

        $dayMap = [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            0 => 'minggu',
        ];
        $dayOfWeek = $dayMap[$carbonDate->dayOfWeek] ?? 'senin';

        // 1. Daftar Rombel Kelas Aktif
        $classrooms = Classroom::with('department')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'department' => $c->department?->name ?? 'Umum',
                'capacity' => $c->capacity,
            ]);

        // Tentukan kelas yang dipilih
        $selectedClassroomId = $request->input('classroom_id');
        if (! $selectedClassroomId && $classrooms->isNotEmpty()) {
            // Cek jika guru adalah wali kelas
            $homeroom = Classroom::where('homeroom_teacher_id', $user->id)->first();
            $selectedClassroomId = $homeroom ? $homeroom->id : $classrooms->first()['id'];
        }
        $selectedClassroomId = $selectedClassroomId ? (int) $selectedClassroomId : null;

        $selectedClassroom = $selectedClassroomId
            ? Classroom::with(['department', 'homeroomTeacher'])->find($selectedClassroomId)
            : null;

        // 2. Daftar Jadwal Pelajaran untuk Kelas pada Hari Tersebut
        $schedules = collect();
        if ($selectedClassroomId) {
            $schedules = LessonSchedule::with(['subject', 'teacher'])
                ->where('classroom_id', $selectedClassroomId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->orderBy('start_time')
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'subject_name' => $s->subject?->name ?? 'Mata Pelajaran',
                    'teacher_name' => $s->teacher?->name ?? 'Guru Pengajar',
                    'time_slot' => $s->time_slot,
                    'room' => $s->room ?: '-',
                    'order' => $s->order,
                ]);
        }

        $selectedScheduleId = $request->input('schedule_id');
        $selectedScheduleId = $selectedScheduleId ? (int) $selectedScheduleId : null;

        // 3. Daftar Siswa dalam Kelas Terpilih
        $studentsData = collect();
        if ($selectedClassroomId) {
            $students = StudentProfile::with('user')
                ->where('classroom_id', $selectedClassroomId)
                ->orderBy('full_name')
                ->get();

            // Ambil rekaman absensi yang sudah tersimpan untuk kelas, tanggal, dan jadwal ini
            $existingQuery = LessonAttendance::where('classroom_id', $selectedClassroomId)
                ->whereDate('date', $date);

            if ($selectedScheduleId) {
                $existingQuery->where('lesson_schedule_id', $selectedScheduleId);
            }

            $existingAttendances = $existingQuery->get()->keyBy('student_user_id');

            // Juga cek attendances harian sebagai fallback
            $dailyAttendances = Attendance::where('classroom_id', $selectedClassroomId)
                ->whereDate('date', $date)
                ->get()
                ->keyBy('user_id');

            $studentsData = $students->map(function ($sp) use ($existingAttendances, $dailyAttendances) {
                $lessonAtt = $existingAttendances->get($sp->user_id);
                $dailyAtt = $dailyAttendances->get($sp->user_id);

                $status = $lessonAtt?->status ?? $dailyAtt?->status ?? 'hadir';
                $notes = $lessonAtt?->notes ?? $dailyAtt?->notes ?? '';
                $isRecorded = $lessonAtt !== null || $dailyAtt !== null;

                return [
                    'id' => $sp->id,
                    'user_id' => $sp->user_id,
                    'nis' => $sp->nis ?: ($sp->nisn ?: '-'),
                    'nisn' => $sp->nisn ?: '-',
                    'name' => $sp->full_name ?: ($sp->user?->name ?: 'Siswa'),
                    'gender' => $sp->gender ?: 'L',
                    'avatar' => $sp->user?->profile_photo_url ?? null,
                    'status' => $status,
                    'notes' => $notes,
                    'is_recorded' => $isRecorded,
                ];
            });
        }

        // 4. Ringkasan Statistik Presensi Kelas (Live Counters)
        $totalStudents = $studentsData->count();
        $countHadir = $studentsData->where('status', 'hadir')->count();
        $countTerlambat = $studentsData->where('status', 'terlambat')->count();
        $countSakit = $studentsData->where('status', 'sakit')->count();
        $countIzin = $studentsData->where('status', 'izin')->count();
        $countAlpa = $studentsData->where('status', 'alpa')->count();

        $presentEffective = $countHadir + $countTerlambat;
        $attendanceRate = $totalStudents > 0
            ? number_format(($presentEffective / $totalStudents) * 100, 1) . '%'
            : '0%';

        $summary = [
            'total_students' => $totalStudents,
            'hadir' => $countHadir,
            'terlambat' => $countTerlambat,
            'sakit' => $countSakit,
            'izin' => $countIzin,
            'alpa' => $countAlpa,
            'rate' => $attendanceRate,
        ];

        return Inertia::render('Attendance/ClassroomAttendance', [
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClassroom' => $selectedClassroom ? [
                'id' => $selectedClassroom->id,
                'name' => $selectedClassroom->name,
                'department' => $selectedClassroom->department?->name ?? 'Umum',
                'homeroom_teacher' => $selectedClassroom->homeroomTeacher?->name ?? 'Belum ditentukan',
            ] : null,
            'selectedDate' => $date,
            'dayOfWeek' => ucfirst($dayOfWeek),
            'schedules' => $schedules,
            'selectedScheduleId' => $selectedScheduleId,
            'students' => $studentsData->values()->all(),
            'summary' => $summary,
            'canEdit' => true,
        ]);
    }

    /**
     * Menyimpan Batch Presensi Siswa per Kelas & Sinkronisasi Presensi Harian
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'lesson_schedule_id' => ['nullable', 'exists:lesson_schedules,id'],
            'date' => ['required', 'date'],
            'attendances' => ['required', 'array'],
            'attendances.*.student_user_id' => ['required', 'exists:users,id'],
            'attendances.*.status' => ['required', 'in:hadir,terlambat,izin,sakit,alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $classroomId = (int) $validated['classroom_id'];
        $scheduleId = ! empty($validated['lesson_schedule_id']) ? (int) $validated['lesson_schedule_id'] : null;
        $date = $validated['date'];

        DB::transaction(function () use ($validated, $user, $classroomId, $scheduleId, $date) {
            foreach ($validated['attendances'] as $item) {
                $studentUserId = (int) $item['student_user_id'];
                $status = $item['status'];
                $notes = $item['notes'] ?? null;

                // 1. Simpan/Update rekaman absensi per jam pelajaran
                LessonAttendance::updateOrCreate(
                    [
                        'classroom_id' => $classroomId,
                        'lesson_schedule_id' => $scheduleId,
                        'student_user_id' => $studentUserId,
                        'date' => $date,
                    ],
                    [
                        'recorded_by_user_id' => $user->id,
                        'status' => $status,
                        'notes' => $notes,
                    ]
                );

                // 2. Sinkronkan dengan presensi harian siswa (tabel attendances)
                $existingDaily = Attendance::where('user_id', $studentUserId)
                    ->whereDate('date', $date)
                    ->first();

                Attendance::updateOrCreate(
                    [
                        'user_id' => $studentUserId,
                        'date' => $date,
                    ],
                    [
                        'classroom_id' => $classroomId,
                        'status' => $status,
                        'method' => 'manual',
                        'time_in' => in_array($status, ['hadir', 'terlambat'])
                            ? ($existingDaily?->time_in ?? Carbon::now()->format('H:i:s'))
                            : null,
                        'notes' => $notes ?: "Presensi kelas dicatat oleh {$user->name}",
                    ]
                );
            }
        });

        return back()->with('status', 'Presensi kelas tanggal ' . Carbon::parse($date)->translatedFormat('d F Y') . ' berhasil disimpan!');
    }
}
