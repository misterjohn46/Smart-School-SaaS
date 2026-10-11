<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    /**
     * Tampilan Master Data Rombongan Belajar (Kelas) SPA
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $gradeLevelId = $request->input('grade_level_id');
        $departmentId = $request->input('department_id');
        $academicYearId = $request->input('academic_year_id');

        $query = Classroom::with([
            'academicYear',
            'department',
            'gradeLevel',
            'homeroomTeacher.teacherProfile',
        ])->withCount('students');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('homeroomTeacher', function ($hq) use ($search) {
                        $hq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($gradeLevelId) {
            $query->where('grade_level_id', $gradeLevelId);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $classrooms = $query->orderBy('name')->paginate(15)->withQueryString();

        // Transform classrooms to include easy-to-use fields in Vue
        $classrooms->getCollection()->transform(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'academic_year_id' => $c->academic_year_id,
                'academic_year_name' => $c->academicYear?->name ?? '-',
                'grade_level_id' => $c->grade_level_id,
                'grade_level_name' => $c->gradeLevel?->name ?? '-',
                'department_id' => $c->department_id,
                'department_name' => $c->department?->name ?? 'Umum',
                'department_code' => $c->department?->code ?? 'UMUM',
                'homeroom_teacher_id' => $c->homeroom_teacher_id,
                'homeroom_teacher_name' => $c->homeroomTeacher?->teacherProfile?->full_name ?? $c->homeroomTeacher?->name ?? 'Belum Ditentukan',
                'homeroom_teacher_nip' => $c->homeroomTeacher?->teacherProfile?->nip ?? '-',
                'capacity' => (int) $c->capacity,
                'students_count' => (int) $c->students_count,
            ];
        });

        $activeYear = AcademicYear::where('is_active', true)->first();

        $stats = [
            'total_classrooms' => Classroom::count(),
            'total_students' => StudentProfile::whereNotNull('classroom_id')->count(),
            'assigned_homeroom' => Classroom::whereNotNull('homeroom_teacher_id')->count(),
            'total_departments' => Department::count(),
        ];

        $academicYears = AcademicYear::orderByDesc('is_active')->orderBy('name')->get(['id', 'name', 'is_active']);
        $gradeLevels = GradeLevel::orderBy('level_numeric')->get(['id', 'name', 'level_numeric']);
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        
        // Daftar Guru untuk Wali Kelas
        $teachers = User::whereHas('roles', function ($q) {
            $q->where('name', 'guru');
        })->orWhereHas('teacherProfile')->with('teacherProfile')->orderBy('name')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->teacherProfile?->full_name ?: $u->name,
                'nip' => $u->teacherProfile?->nip ?? '-',
            ];
        });

        return Inertia::render('Academic/Classrooms/Index', [
            'classrooms' => $classrooms,
            'academicYears' => $academicYears,
            'gradeLevels' => $gradeLevels,
            'departments' => $departments,
            'teachers' => $teachers,
            'activeYear' => $activeYear,
            'filters' => [
                'search' => $search,
                'grade_level_id' => $gradeLevelId ? (int) $gradeLevelId : null,
                'department_id' => $departmentId ? (int) $departmentId : null,
                'academic_year_id' => $academicYearId ? (int) $academicYearId : null,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan Kelas Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'homeroom_teacher_id' => ['nullable', 'exists:users,id'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        Classroom::create($validated);

        return back()->with('status', "Kelas {$validated['name']} berhasil ditambahkan!");
    }

    /**
     * Perbarui Data Kelas
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $classroom = Classroom::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'homeroom_teacher_id' => ['nullable', 'exists:users,id'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $classroom->update($validated);

        return back()->with('status', "Data kelas {$classroom->name} berhasil diperbarui!");
    }

    /**
     * Hapus Kelas dengan Proteksi Siswa Terdaftar
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $classroom = Classroom::withCount('students')->findOrFail($id);

        if ($classroom->students_count > 0) {
            return back()->withErrors([
                'error' => "Kelas {$classroom->name} tidak dapat dihapus karena masih memiliki {$classroom->students_count} siswa terdaftar. Silakan pindahkan siswa terlebih dahulu.",
            ]);
        }

        $name = $classroom->name;
        $classroom->delete();

        return back()->with('status', "Kelas {$name} berhasil dihapus!");
    }
}
