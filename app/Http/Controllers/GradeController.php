<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    /**
     * Tampilan Buku Nilai & Penilaian Akademik Guru (Vue 3 SPA)
     */
    public function index(Request $request): Response
    {
        $classrooms = Classroom::with('department')->orderBy('name')->get(['id', 'name', 'department_id']);
        $subjects = Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'kkm', 'type']);
        $academicYears = AcademicYear::orderByDesc('is_active')->get(['id', 'name', 'semester', 'is_active']);

        $activeYear = AcademicYear::where('is_active', true)->first() ?? $academicYears->first();
        $selectedYearId = $request->input('academic_year_id') ? (int) $request->input('academic_year_id') : $activeYear?->id;

        $selectedClassroomId = $request->input('classroom_id')
            ? (int) $request->input('classroom_id')
            : $classrooms->first()?->id;

        $selectedSubjectId = $request->input('subject_id')
            ? (int) $request->input('subject_id')
            : $subjects->first()?->id;

        $currentSubject = $subjects->firstWhere('id', $selectedSubjectId) ?? $subjects->first();
        $currentClassroom = $classrooms->firstWhere('id', $selectedClassroomId) ?? $classrooms->first();
        $kkm = (int) ($currentSubject?->kkm ?? 75);

        // Ambil seluruh siswa pada kelas terpilih
        $students = $selectedClassroomId
            ? StudentProfile::where('classroom_id', $selectedClassroomId)->orderBy('full_name')->get()
            : collect();

        // Ambil nilai tersimpan
        $gradesMap = ($selectedClassroomId && $selectedSubjectId && $selectedYearId)
            ? StudentGrade::where('academic_year_id', $selectedYearId)
                ->where('classroom_id', $selectedClassroomId)
                ->where('subject_id', $selectedSubjectId)
                ->get()
                ->keyBy('student_profile_id')
            : collect();

        $gradeItems = $students->map(function ($student) use ($gradesMap, $kkm) {
            $grade = $gradesMap->get($student->id);
            $assignment = (float) ($grade?->assignment_score ?? 0);
            $quiz = (float) ($grade?->quiz_score ?? 0);
            $midterm = (float) ($grade?->midterm_score ?? 0);
            $final = (float) ($grade?->final_score ?? 0);

            // Jika sudah ada nilai tersimpan gunakan dari db, jika belum hitung default
            if ($grade && (float) $grade->overall_score > 0) {
                $overall = (float) $grade->overall_score;
                $letter = $grade->letter_grade;
                $isPassed = (bool) $grade->is_passed;
            } else {
                $calc = StudentGrade::calculateScore($assignment, $quiz, $midterm, $final, $kkm);
                $overall = $calc['overall_score'];
                $letter = $calc['letter_grade'];
                $isPassed = $calc['is_passed'];
            }

            return [
                'student_id' => $student->id,
                'name' => $student->full_name,
                'nisn' => $student->nisn,
                'gender' => $student->gender,
                'assignment_score' => $assignment,
                'quiz_score' => $quiz,
                'midterm_score' => $midterm,
                'final_score' => $final,
                'overall_score' => $overall,
                'letter_grade' => $letter,
                'is_passed' => $isPassed,
                'notes' => $grade?->notes ?? '',
            ];
        });

        $gradedStudents = $gradeItems->where('overall_score', '>', 0);

        $stats = [
            'total_students' => $gradeItems->count(),
            'average_score' => $gradedStudents->isNotEmpty() ? round((float) $gradedStudents->avg('overall_score'), 1) : 0,
            'passed_count' => $gradedStudents->where('is_passed', true)->count(),
            'remedial_count' => $gradedStudents->where('is_passed', false)->count(),
            'highest_score' => $gradedStudents->isNotEmpty() ? (float) $gradedStudents->max('overall_score') : 0,
            'lowest_score' => $gradedStudents->isNotEmpty() ? (float) $gradedStudents->min('overall_score') : 0,
        ];

        return Inertia::render('Academic/Grades/Index', [
            'gradeItems' => $gradeItems,
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'academicYears' => $academicYears,
            'currentSubject' => $currentSubject,
            'currentClassroom' => $currentClassroom,
            'activeYear' => $activeYear,
            'filters' => [
                'classroom_id' => $selectedClassroomId,
                'subject_id' => $selectedSubjectId,
                'academic_year_id' => $selectedYearId,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan / Perbarui Nilai Siswa Satu Kelas Sekaligus (Batch Save)
     */
    public function batchStoreOrUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.student_id' => ['required', 'exists:student_profiles,id'],
            'grades.*.assignment_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grades.*.quiz_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grades.*.midterm_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grades.*.final_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grades.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $subject = Subject::findOrFail($validated['subject_id']);
        $kkm = (int) $subject->kkm;
        $teacherUserId = $request->user()->id;

        DB::transaction(function () use ($validated, $kkm, $teacherUserId) {
            foreach ($validated['grades'] as $item) {
                $calc = StudentGrade::calculateScore(
                    (float) $item['assignment_score'],
                    (float) $item['quiz_score'],
                    (float) $item['midterm_score'],
                    (float) $item['final_score'],
                    $kkm
                );

                StudentGrade::updateOrCreate(
                    [
                        'academic_year_id' => $validated['academic_year_id'],
                        'classroom_id' => $validated['classroom_id'],
                        'subject_id' => $validated['subject_id'],
                        'student_profile_id' => $item['student_id'],
                    ],
                    [
                        'teacher_user_id' => $teacherUserId,
                        'assignment_score' => $item['assignment_score'],
                        'quiz_score' => $item['quiz_score'],
                        'midterm_score' => $item['midterm_score'],
                        'final_score' => $item['final_score'],
                        'overall_score' => $calc['overall_score'],
                        'letter_grade' => $calc['letter_grade'],
                        'is_passed' => $calc['is_passed'],
                        'notes' => $item['notes'] ?? null,
                    ]
                );
            }
        });

        return back()->with('status', 'Nilai seluruh siswa berhasil disimpan dan diperbarui!');
    }
}
