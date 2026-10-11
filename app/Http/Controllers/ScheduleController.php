<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\LessonSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $classrooms = Classroom::with('department')->orderBy('name')->get();

        $selectedClassroomId = $request->input('classroom_id') 
            ? (int) $request->input('classroom_id') 
            : $classrooms->first()?->id;

        $query = LessonSchedule::with(['subject', 'teacher', 'classroom.department'])
            ->where('is_active', true);

        if ($activeYear) {
            $query->where('academic_year_id', $activeYear->id);
        }

        if ($selectedClassroomId) {
            $query->where('classroom_id', $selectedClassroomId);
        }

        $schedules = $query->orderBy('start_time')->get();

        // Kelompokkan per hari (Senin s/d Sabtu)
        $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
        $groupedSchedules = [];

        foreach ($days as $day) {
            $groupedSchedules[$day] = $schedules->where('day_of_week', $day)->map(function ($item) {
                return [
                    'id' => $item->id,
                    'time' => $item->time_slot,
                    'start_time' => substr((string) $item->start_time, 0, 5),
                    'end_time' => substr((string) $item->end_time, 0, 5),
                    'subject' => $item->subject?->name ?? 'Mata Pelajaran',
                    'subject_code' => $item->subject?->code ?? '-',
                    'subject_type' => $item->subject?->type ?? 'umum',
                    'teacher' => $item->teacher?->name ?? 'Guru Pengajar',
                    'room' => $item->room ?: 'Ruang Kelas',
                    'classroom' => $item->classroom?->name ?? '-',
                    'order' => $item->order,
                ];
            })->values()->toArray();
        }

        // Jika belum ada jadwal tersimpan di database, berikan fallback mockup rapi
        $totalFound = $schedules->count();
        if ($totalFound === 0) {
            $groupedSchedules = $this->getMockupSchedules();
        }

        $selectedClass = $classrooms->firstWhere('id', $selectedClassroomId);

        $subjects = \App\Models\Subject::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'type']);
        $teachers = \App\Models\User::role(\App\Enums\RoleName::Teacher->value)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Academic/Schedules', [
            'classrooms' => $classrooms->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'department' => $c->department?->name ?? 'Umum',
            ]),
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClassroom' => $selectedClass ? [
                'id' => $selectedClass->id,
                'name' => $selectedClass->name,
                'department' => $selectedClass->department?->name ?? 'Umum',
            ] : null,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'schedules' => $groupedSchedules,
            'academicYear' => $activeYear?->name ?? '2024/2025 Ganjil',
            'isMockup' => $totalFound === 0,
        ]);
    }

    /**
     * Simpan jadwal baru secara instan dari modal matriks
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_user_id' => ['required', 'exists:users,id'],
            'day_of_week' => ['required', 'in:senin,selasa,rabu,kamis,jumat,sabtu'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'room' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        if (empty($validated['academic_year_id'])) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            $validated['academic_year_id'] = $activeYear?->id ?? 1;
        }

        if (empty($validated['order'])) {
            $validated['order'] = 1;
        }

        $validated['is_active'] = true;

        LessonSchedule::create($validated);

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil ditambahkan!');
    }

    /**
     * Hapus jadwal pelajaran secara langsung
     */
    public function destroy(int|string $id): \Illuminate\Http\RedirectResponse
    {
        $schedule = LessonSchedule::findOrFail($id);
        $schedule->delete();

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil dihapus!');
    }

    /**
     * Fallback template mockup jika admin belum menginput jadwal
     */
    protected function getMockupSchedules(): array
    {
        return [
            'senin' => [
                ['id' => 1, 'time' => '07:15 - 08:45', 'start_time' => '07:15', 'end_time' => '08:45', 'subject' => 'Upacara Bendera & Literasi', 'subject_code' => 'UPC', 'subject_type' => 'umum', 'teacher' => 'Tim Kesiswaan & Wali Kelas', 'room' => 'Lapangan Utama', 'classroom' => 'X - RPL 1', 'order' => 1],
                ['id' => 2, 'time' => '09:00 - 10:30', 'start_time' => '09:00', 'end_time' => '10:30', 'subject' => 'Pemrograman Web & Perangkat Bergerak', 'subject_code' => 'RPL-01', 'subject_type' => 'kejuruan', 'teacher' => 'Ahmad Fauzi, S.Kom', 'room' => 'Lab Software 1', 'classroom' => 'X - RPL 1', 'order' => 2],
                ['id' => 3, 'time' => '10:45 - 12:15', 'start_time' => '10:45', 'end_time' => '12:15', 'subject' => 'Basis Data Relasional & SQL', 'subject_code' => 'RPL-02', 'subject_type' => 'kejuruan', 'teacher' => 'Siti Rahma, M.Kom', 'room' => 'Lab Software 2', 'classroom' => 'X - RPL 1', 'order' => 3],
            ],
            'selasa' => [
                ['id' => 4, 'time' => '07:15 - 08:45', 'start_time' => '07:15', 'end_time' => '08:45', 'subject' => 'Matematika Terapan', 'subject_code' => 'MAT-01', 'subject_type' => 'umum', 'teacher' => 'Drs. H. Mulyadi', 'room' => 'Ruang 101', 'classroom' => 'X - RPL 1', 'order' => 1],
                ['id' => 5, 'time' => '09:00 - 10:30', 'start_time' => '09:00', 'end_time' => '10:30', 'subject' => 'Bahasa Inggris Komunikasi Bisnis', 'subject_code' => 'ING-01', 'subject_type' => 'umum', 'teacher' => 'Sarah Wijaya, S.Pd', 'room' => 'Ruang 101', 'classroom' => 'X - RPL 1', 'order' => 2],
            ],
            'rabu' => [
                ['id' => 6, 'time' => '07:15 - 09:30', 'start_time' => '07:15', 'end_time' => '09:30', 'subject' => 'Pemodelan Perangkat Lunak (UML & Agile)', 'subject_code' => 'RPL-03', 'subject_type' => 'kejuruan', 'teacher' => 'Ahmad Fauzi, S.Kom', 'room' => 'Lab Software 1', 'classroom' => 'X - RPL 1', 'order' => 1],
                ['id' => 7, 'time' => '10:00 - 12:15', 'start_time' => '10:00', 'end_time' => '12:15', 'subject' => 'Pendidikan Pancasila & Kewarganegaraan', 'subject_code' => 'PPKN', 'subject_type' => 'umum', 'teacher' => 'Dra. Hj. Nurhayati', 'room' => 'Ruang 101', 'classroom' => 'X - RPL 1', 'order' => 2],
            ],
            'kamis' => [
                ['id' => 8, 'time' => '07:15 - 09:00', 'start_time' => '07:15', 'end_time' => '09:00', 'subject' => 'Pendidikan Agama & Budi Pekerti', 'subject_code' => 'PAI', 'subject_type' => 'umum', 'teacher' => 'Ust. Muhammad Sholeh, M.Pd.I', 'room' => 'Masjid Sekolah / Ruang 101', 'classroom' => 'X - RPL 1', 'order' => 1],
                ['id' => 9, 'time' => '09:30 - 12:00', 'start_time' => '09:30', 'end_time' => '12:00', 'subject' => 'Projek Kreatif & Kewirausahaan (Digital Product)', 'subject_code' => 'PKK', 'subject_type' => 'kejuruan', 'teacher' => 'Bambang Irawan, S.E', 'room' => 'Lab Komputer 1', 'classroom' => 'X - RPL 1', 'order' => 2],
            ],
            'jumat' => [
                ['id' => 10, 'time' => '07:00 - 08:00', 'start_time' => '07:00', 'end_time' => '08:00', 'subject' => 'Senam Sehat & Kegiatan Religi Pagi', 'subject_code' => 'SNM', 'subject_type' => 'umum', 'teacher' => 'Guru Pembina', 'room' => 'Lapangan Utama', 'classroom' => 'X - RPL 1', 'order' => 1],
                ['id' => 11, 'time' => '08:15 - 10:45', 'start_time' => '08:15', 'end_time' => '10:45', 'subject' => 'Dasar Desain Grafis & UI/UX', 'subject_code' => 'DKV-01', 'subject_type' => 'kejuruan', 'teacher' => 'Rian Hidayat, S.Ds', 'room' => 'Lab Multimedia', 'classroom' => 'X - RPL 1', 'order' => 2],
            ],
            'sabtu' => [
                ['id' => 12, 'time' => '07:30 - 10:30', 'start_time' => '07:30', 'end_time' => '10:30', 'subject' => 'Kegiatan Ekstrakurikuler Wajib & Minat Bakat', 'subject_code' => 'EKS', 'subject_type' => 'mulok', 'teacher' => 'Instruktur Ekstrakurikuler', 'room' => 'Gedung Serbaguna', 'classroom' => 'X - RPL 1', 'order' => 1],
            ],
        ];
    }
}
