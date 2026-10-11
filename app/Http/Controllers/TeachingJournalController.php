<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\LessonSchedule;
use App\Models\Subject;
use App\Models\TeachingJournal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeachingJournalController extends Controller
{
    /**
     * Tampilan Daftar Jurnal Mengajar & Agenda Guru (Vue 3 SPA)
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isTeacherOnly = $user->hasRole('guru') && ! $user->hasRole('admin');

        $teacherId = $request->input('teacher_user_id');
        if ($isTeacherOnly) {
            $teacherId = $user->id;
        }

        $classroomId = $request->input('classroom_id');
        $subjectId = $request->input('subject_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        $query = TeachingJournal::with([
            'classroom.department',
            'subject',
            'teacher.teacherProfile',
            'lessonSchedule',
            'verifiedBy',
        ]);

        if ($teacherId) {
            $query->where('teacher_user_id', $teacherId);
        }

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('topic_title', 'like', "%{$search}%")
                    ->orWhere('learning_activity', 'like', "%{$search}%")
                    ->orWhere('class_notes', 'like', "%{$search}%");
            });
        }

        $journals = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString();

        $journals->getCollection()->transform(function ($j) {
            return [
                'id' => $j->id,
                'date' => $j->date->format('Y-m-d'),
                'date_formatted' => $j->date->format('d M Y'),
                'time_slot' => $j->time_slot ?? '-',
                'topic_title' => $j->topic_title,
                'teaching_method' => $j->teaching_method,
                'learning_activity' => $j->learning_activity,
                'class_notes' => $j->class_notes ?? '-',
                'attendance_present' => (int) $j->attendance_present,
                'attendance_sick' => (int) $j->attendance_sick,
                'attendance_permission' => (int) $j->attendance_permission,
                'attendance_absent' => (int) $j->attendance_absent,
                'status' => $j->status,
                'substitute_teacher_name' => $j->substitute_teacher_name,
                'is_verified' => (bool) $j->verified_by_user_id,
                'verified_by_name' => $j->verifiedBy?->name ?? null,
                'verified_at' => $j->verified_at?->format('d M Y H:i') ?? null,
                'classroom_id' => $j->classroom_id,
                'classroom_name' => $j->classroom?->name ?? 'Kelas',
                'subject_id' => $j->subject_id,
                'subject_name' => $j->subject?->name ?? 'Mata Pelajaran',
                'subject_code' => $j->subject?->code ?? '-',
                'teacher_name' => $j->teacher?->teacherProfile?->full_name ?? $j->teacher?->name ?? 'Guru',
                'academic_year_id' => $j->academic_year_id,
            ];
        });

        // Deteksi Jadwal Mengajar Hari Ini untuk Quick Slot
        $dayMap = [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
        ];
        $todayDay = $dayMap[now()->dayOfWeek] ?? 'senin';
        $todayDate = now()->toDateString();

        $todayTeacherId = $teacherId ?: $user->id;
        $todaySchedules = LessonSchedule::where('teacher_user_id', $todayTeacherId)
            ->where('day_of_week', $todayDay)
            ->where('is_active', true)
            ->with(['classroom.department', 'subject'])
            ->orderBy('start_time')
            ->get()
            ->map(function ($sch) use ($todayDate, $todayTeacherId) {
                $alreadyFilled = TeachingJournal::where('teacher_user_id', $todayTeacherId)
                    ->where('classroom_id', $sch->classroom_id)
                    ->where('subject_id', $sch->subject_id)
                    ->whereDate('date', $todayDate)
                    ->exists();

                return [
                    'schedule_id' => $sch->id,
                    'classroom_id' => $sch->classroom_id,
                    'classroom_name' => $sch->classroom?->name ?? '-',
                    'subject_id' => $sch->subject_id,
                    'time_slot' => "Jam Ke-{$sch->order} (" . substr((string) $sch->start_time, 0, 5) . ' - ' . substr((string) $sch->end_time, 0, 5) . ')',
                    'room' => $sch->room,
                    'already_filled' => $alreadyFilled,
                ];
            });

        $activeYear = AcademicYear::where('is_active', true)->first();

        $stats = [
            'total_journals' => TeachingJournal::count(),
            'this_month' => TeachingJournal::whereMonth('date', now()->month)->whereYear('date', now()->year)->count(),
            'verified_count' => TeachingJournal::whereNotNull('verified_by_user_id')->count(),
            'special_status' => TeachingJournal::whereIn('status', ['pengganti', 'daring', 'tugas_mandiri'])->count(),
        ];

        $classrooms = Classroom::with('department')->orderBy('name')->get(['id', 'name', 'department_id']);
        $subjects = Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'kkm']);
        $teachers = User::whereHas('roles', fn ($q) => $q->where('name', 'guru'))
            ->orWhereHas('teacherProfile')
            ->with('teacherProfile')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->teacherProfile?->full_name ?: $u->name,
                'nip' => $u->teacherProfile?->nip ?? '-',
            ]);

        $academicYears = AcademicYear::orderByDesc('is_active')->get(['id', 'name', 'is_active']);

        return Inertia::render('Academic/Journals/Index', [
            'journals' => $journals,
            'todaySchedules' => $todaySchedules,
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'isTeacherOnly' => $isTeacherOnly,
            'filters' => [
                'teacher_user_id' => $teacherId ? (int) $teacherId : null,
                'classroom_id' => $classroomId ? (int) $classroomId : null,
                'subject_id' => $subjectId ? (int) $subjectId : null,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan Jurnal Mengajar Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_user_id' => ['nullable', 'exists:users,id'],
            'lesson_schedule_id' => ['nullable', 'exists:lesson_schedules,id'],
            'date' => ['required', 'date'],
            'time_slot' => ['nullable', 'string', 'max:50'],
            'topic_title' => ['required', 'string', 'max:255'],
            'teaching_method' => ['required', 'string', 'max:50'],
            'learning_activity' => ['required', 'string'],
            'class_notes' => ['nullable', 'string'],
            'attendance_present' => ['nullable', 'integer', 'min:0'],
            'attendance_sick' => ['nullable', 'integer', 'min:0'],
            'attendance_permission' => ['nullable', 'integer', 'min:0'],
            'attendance_absent' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:terlaksana,tugas_mandiri,pengganti,daring'],
            'substitute_teacher_name' => ['nullable', 'string', 'max:100'],
        ]);

        if (empty($validated['teacher_user_id'])) {
            $validated['teacher_user_id'] = $request->user()->id;
        }

        TeachingJournal::create($validated);

        return back()->with('status', 'Jurnal mengajar berhasil disimpan!');
    }

    /**
     * Perbarui Jurnal Mengajar
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $journal = TeachingJournal::findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_user_id' => ['nullable', 'exists:users,id'],
            'lesson_schedule_id' => ['nullable', 'exists:lesson_schedules,id'],
            'date' => ['required', 'date'],
            'time_slot' => ['nullable', 'string', 'max:50'],
            'topic_title' => ['required', 'string', 'max:255'],
            'teaching_method' => ['required', 'string', 'max:50'],
            'learning_activity' => ['required', 'string'],
            'class_notes' => ['nullable', 'string'],
            'attendance_present' => ['nullable', 'integer', 'min:0'],
            'attendance_sick' => ['nullable', 'integer', 'min:0'],
            'attendance_permission' => ['nullable', 'integer', 'min:0'],
            'attendance_absent' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:terlaksana,tugas_mandiri,pengganti,daring'],
            'substitute_teacher_name' => ['nullable', 'string', 'max:100'],
        ]);

        $journal->update($validated);

        return back()->with('status', 'Jurnal mengajar berhasil diperbarui!');
    }

    /**
     * Hapus Catatan Jurnal Mengajar
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $journal = TeachingJournal::findOrFail($id);
        $journal->delete();

        return back()->with('status', 'Jurnal mengajar berhasil dihapus!');
    }

    /**
     * Verifikasi Supervisi oleh Kepala Sekolah / Waka Kurikulum
     */
    public function verify(Request $request, int|string $id): RedirectResponse
    {
        $journal = TeachingJournal::findOrFail($id);

        $journal->update([
            'verified_by_user_id' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('status', 'Jurnal mengajar telah diverifikasi supervisi akademik!');
    }
}
