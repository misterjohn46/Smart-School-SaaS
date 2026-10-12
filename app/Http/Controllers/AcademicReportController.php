<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\StudentReportRemark;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicReportController extends Controller
{
    /**
     * Tampilkan Halaman Leger Nilai & Buku Rapor Siswa
     */
    public function index(Request $request): Response
    {
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $activeYear = AcademicYear::where('is_active', true)->first() ?? $academicYears->first();

        $selectedYearId = $request->filled('academic_year_id')
            ? (int) $request->input('academic_year_id')
            : ($activeYear?->id ?? 0);

        $classrooms = Classroom::orderBy('name')->get();
        $selectedClassroomId = $request->filled('classroom_id')
            ? (int) $request->input('classroom_id')
            : ($classrooms->first()?->id ?? 0);

        $selectedClassroom = $classrooms->firstWhere('id', $selectedClassroomId);
        // Kolom KKM pada tabel subjects bernama `kkm`, bukan `passing_grade`.
        // Dengan nama lama, SELECT diam-diam lolos di SQLite (identifier tak dikenal
        // dalam kutip ganda diperlakukan sebagai literal string) tetapi gagal di MySQL.
        $subjects = Subject::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'kkm']);

        // Data Siswa dalam Rombel
        $students = [];
        $ledgerRows = [];
        $remarksMap = [];
        $stats = [
            'total_students' => 0,
            'class_average' => 0,
            'highest_score' => 0,
            'lowest_score' => 0,
            'pass_percentage' => 0,
        ];

        if ($selectedClassroom && $selectedYearId) {
            $studentProfiles = StudentProfile::with('user')
                ->where('classroom_id', $selectedClassroomId)
                ->orderBy('full_name')
                ->get();

            $grades = StudentGrade::where('academic_year_id', $selectedYearId)
                ->where('classroom_id', $selectedClassroomId)
                ->get();

            $remarks = StudentReportRemark::where('academic_year_id', $selectedYearId)
                ->where('classroom_id', $selectedClassroomId)
                ->get();

            foreach ($remarks as $rem) {
                $remarksMap[$rem->student_profile_id] = $rem;
            }

            // Bangun Matriks Leger
            $tempRows = [];
            foreach ($studentProfiles as $student) {
                $studentGrades = $grades->where('student_profile_id', $student->id);
                $gradeMap = [];
                $totalScore = 0;
                $subjectCount = 0;
                $passedCount = 0;

                foreach ($subjects as $sub) {
                    $grade = $studentGrades->firstWhere('subject_id', $sub->id);
                    if ($grade) {
                        $gradeMap[$sub->id] = [
                            'overall_score' => (float) $grade->overall_score,
                            'letter_grade' => $grade->letter_grade,
                            'is_passed' => (bool) $grade->is_passed,
                            'assignment_score' => (float) $grade->assignment_score,
                            'quiz_score' => (float) $grade->quiz_score,
                            'midterm_score' => (float) $grade->midterm_score,
                            'final_score' => (float) $grade->final_score,
                        ];
                        $totalScore += (float) $grade->overall_score;
                        $subjectCount++;
                        if ($grade->is_passed) {
                            $passedCount++;
                        }
                    } else {
                        $gradeMap[$sub->id] = null;
                    }
                }

                $avgScore = $subjectCount > 0 ? round($totalScore / $subjectCount, 2) : 0;
                $remark = $remarksMap[$student->id] ?? null;

                $tempRows[] = [
                    'student_id' => $student->id,
                    // Dipakai tautan unduh PDF rapor (route academic.reports.pdf).
                    'hash_id' => $student->hash_id,
                    'nisn' => $student->nisn,
                    'name' => $student->full_name ?? $student->user?->name ?? 'Siswa',
                    'gender' => $student->gender ?? 'L',
                    'grades' => $gradeMap,
                    'total_score' => $totalScore,
                    'average_score' => $avgScore,
                    'subject_count' => $subjectCount,
                    'passed_count' => $passedCount,
                    'remark' => $remark,
                ];
            }

            // Kalkulasi Ranking Paralel Kelas (Berdasarkan Nilai Rata-rata Tertinggi)
            usort($tempRows, fn ($a, $b) => $b['average_score'] <=> $a['average_score']);

            $rank = 1;
            foreach ($tempRows as &$row) {
                $row['rank'] = $rank++;
            }
            unset($row);

            $ledgerRows = $tempRows;
            $students = $studentProfiles;

            // Statistik Kelas
            if (count($ledgerRows) > 0) {
                $averages = array_column($ledgerRows, 'average_score');
                $stats['total_students'] = count($ledgerRows);
                $stats['class_average'] = round(array_sum($averages) / count($averages), 2);
                $stats['highest_score'] = max($averages);
                $stats['lowest_score'] = min($averages);

                $allPassed = array_filter($ledgerRows, fn ($r) => $r['subject_count'] > 0 && $r['passed_count'] === $r['subject_count']);
                $stats['pass_percentage'] = round((count($allPassed) / count($ledgerRows)) * 100, 1);
            }
        }

        return Inertia::render('Academic/Reports/Index', [
            'academicYears' => $academicYears,
            'selectedYearId' => $selectedYearId,
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClassroom' => $selectedClassroom,
            'subjects' => $subjects,
            'ledgerRows' => $ledgerRows,
            'students' => $students,
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan / Perbarui Catatan Wali Kelas & Ekstrakurikuler Rapor
     */
    public function storeRemark(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'student_profile_id' => ['required', 'exists:student_profiles,id'],
            'attendance_sick' => ['nullable', 'integer', 'min:0'],
            'attendance_permission' => ['nullable', 'integer', 'min:0'],
            'attendance_unexcused' => ['nullable', 'integer', 'min:0'],
            'homeroom_notes' => ['nullable', 'string'],
            'extracurriculars' => ['nullable', 'array'],
            'promotion_status' => ['required', 'in:naik_kelas,tinggal_kelas,lulus,belum_lulus,dalam_proses'],
        ]);

        StudentReportRemark::updateOrCreate([
            'academic_year_id' => $validated['academic_year_id'],
            'student_profile_id' => $validated['student_profile_id'],
        ], [
            'classroom_id' => $validated['classroom_id'],
            'teacher_user_id' => $request->user()->id,
            'attendance_sick' => (int) ($validated['attendance_sick'] ?? 0),
            'attendance_permission' => (int) ($validated['attendance_permission'] ?? 0),
            'attendance_unexcused' => (int) ($validated['attendance_unexcused'] ?? 0),
            'homeroom_notes' => $validated['homeroom_notes'] ?? null,
            'extracurriculars' => $validated['extracurriculars'] ?? null,
            'promotion_status' => $validated['promotion_status'],
        ]);

        return back()->with('success', 'Catatan wali kelas dan absensi rapor berhasil disimpan.');
    }

    /**
     * Cetak e-Raport satu siswa sebagai berkas PDF.
     *
     * Nilai, predikat, dan status ketuntasan memakai perhitungan yang sama dengan
     * buku nilai (StudentGrade::calculateScore) agar rapor tidak pernah berbeda
     * dengan leger.
     */
    public function printReportCard(Request $request, string $hashId): HttpResponse
    {
        $student = StudentProfile::with(['user', 'classroom.department', 'classroom.gradeLevel'])
            ->whereHashId($hashId)
            ->firstOrFail();

        $academicYear = $request->filled('academic_year_id')
            ? AcademicYear::findOrFail((int) $request->input('academic_year_id'))
            : (AcademicYear::where('is_active', true)->firstOrFail());

        $subjects = Subject::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'kkm']);

        $grades = StudentGrade::where('academic_year_id', $academicYear->id)
            ->where('student_profile_id', $student->id)
            ->get()
            ->keyBy('subject_id');

        $rows = [];
        $totalScore = 0.0;
        $scoredCount = 0;
        $passedCount = 0;

        foreach ($subjects as $subject) {
            $grade = $grades->get($subject->id);

            if (! $grade) {
                continue;
            }

            $overall = (float) $grade->overall_score;
            $totalScore += $overall;
            $scoredCount++;

            if ($grade->is_passed) {
                $passedCount++;
            }

            $rows[] = [
                'code' => $subject->code,
                'name' => $subject->name,
                'kkm' => (int) $subject->kkm,
                'assignment' => (float) $grade->assignment_score,
                'quiz' => (float) $grade->quiz_score,
                'midterm' => (float) $grade->midterm_score,
                'final' => (float) $grade->final_score,
                'overall' => $overall,
                'letter' => $grade->letter_grade,
                'is_passed' => (bool) $grade->is_passed,
            ];
        }

        $remark = StudentReportRemark::where('academic_year_id', $academicYear->id)
            ->where('student_profile_id', $student->id)
            ->first();

        $pdf = Pdf::loadView('pdf.report-card', [
            'student' => $student,
            'academicYear' => $academicYear,
            'rows' => $rows,
            'average' => $scoredCount > 0 ? round($totalScore / $scoredCount, 2) : 0.0,
            'scoredCount' => $scoredCount,
            'passedCount' => $passedCount,
            'remark' => $remark,
            'schoolName' => tenancy()->initialized ? (string) tenant()->school_name : config('app.name'),
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $fileName = 'e-raport-'.str($student->nisn ?: $student->id)->slug().'-'.$academicYear->id.'.pdf';

        return $pdf->stream($fileName);
    }
}
