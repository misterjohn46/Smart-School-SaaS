<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\LearningMaterial;
use App\Models\LessonSchedule;
use App\Models\SchoolAnnouncement;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningController extends Controller
{
    /**
     * Halaman Terpadu E-Learning (Materi & Tugas Harian) serta Papan Pengumuman Sekolah.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isTeacherOnly = $user && $user->hasRole('guru') && ! $user->hasAnyRole(['admin', 'super_admin', 'operator', 'kepala_sekolah']);
        $canManage = (bool) $user?->hasAnyRole(['admin', 'kepala_sekolah', 'operator', 'guru', 'guru_bk']);

        $studentProfile = StudentProfile::with('classroom')
            ->where('user_id', $user->id)
            ->orWhere('parent_user_id', $user->id)
            ->first();

        $search = $request->input('search', '');
        $classroomId = $request->input('classroom_id', '');
        $subjectId = $request->input('subject_id', '');
        $typeFilter = $request->input('type', '');

        $taughtClassroomIds = collect();
        $taughtSubjectIds = collect();
        $teachingAssignments = [];

        if ($isTeacherOnly) {
            $schedules = LessonSchedule::where('teacher_user_id', $user->id)
                ->where('is_active', true)
                ->get(['classroom_id', 'subject_id']);

            $taughtClassroomIds = $schedules->pluck('classroom_id')->unique()->values();
            $taughtSubjectIds = $schedules->pluck('subject_id')->unique()->values();

            $teachingAssignments = $schedules->groupBy('classroom_id')
                ->map(fn ($g) => $g->pluck('subject_id')->unique()->values()->all())
                ->all();
        }

        $materialsQuery = LearningMaterial::with([
            'subject',
            'classroom',
            'teacher',
            'submissions.studentProfile',
        ]);

        if (! $canManage) {
            $materialsQuery->where('is_published', true);
            if ($studentProfile && $studentProfile->classroom_id) {
                $materialsQuery->where('classroom_id', $studentProfile->classroom_id);
            }
        } else {
            if ($isTeacherOnly) {
                $materialsQuery->where(function ($q) use ($user, $taughtClassroomIds, $taughtSubjectIds) {
                    $q->where('teacher_id', $user->id)
                        ->orWhereIn('classroom_id', $taughtClassroomIds)
                        ->orWhereIn('subject_id', $taughtSubjectIds);
                });
            }

            if ($classroomId) {
                $materialsQuery->where('classroom_id', (int) $classroomId);
            }
        }

        if ($subjectId) {
            $materialsQuery->where('subject_id', (int) $subjectId);
        }

        if ($typeFilter && in_array($typeFilter, ['material', 'assignment'], true)) {
            $materialsQuery->where('type', $typeFilter);
        }

        if ($search) {
            $materialsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $materials = $materialsQuery->latest('id')->get()->map(function (LearningMaterial $item) use ($studentProfile, $canManage) {
            $mySubmission = null;
            if ($studentProfile) {
                $sub = $item->submissions->firstWhere('student_profile_id', $studentProfile->id);
                if ($sub) {
                    $mySubmission = [
                        'id' => $sub->id,
                        'answer_text' => $sub->answer_text,
                        'attachment_url' => $sub->attachment_url,
                        'submitted_at_formatted' => $sub->submitted_at?->format('d M Y, H:i'),
                        'score' => $sub->score !== null ? (float) $sub->score : null,
                        'teacher_feedback' => $sub->teacher_feedback,
                    ];
                }
            }

            return [
                'id' => $item->id,
                'subject_id' => $item->subject_id,
                'subject_name' => $item->subject?->name ?? '-',
                'classroom_id' => $item->classroom_id,
                'classroom_name' => $item->classroom?->name ?? '-',
                'teacher_name' => $item->teacher?->name ?? 'Guru Mapel',
                'type' => $item->type,
                'title' => $item->title,
                'description' => $item->description,
                'reference_url' => $item->reference_url,
                'due_date' => $item->due_date?->format('Y-m-d\TH:i'),
                'due_date_formatted' => $item->due_date?->format('d M Y, H:i'),
                'is_overdue' => $item->due_date ? $item->due_date->isPast() : false,
                'max_score' => (int) $item->max_score,
                'is_published' => (bool) $item->is_published,
                'created_at_formatted' => $item->created_at?->diffForHumans() ?? '-',
                'submissions_count' => $item->submissions->count(),
                'my_submission' => $mySubmission,
                'submissions' => $canManage ? $item->submissions->map(fn (AssignmentSubmission $s) => [
                    'id' => $s->id,
                    'student_profile_id' => $s->student_profile_id,
                    'student_name' => $s->studentProfile?->full_name ?? 'Siswa',
                    'student_nisn' => $s->studentProfile?->nisn ?? '-',
                    'answer_text' => $s->answer_text,
                    'attachment_url' => $s->attachment_url,
                    'submitted_at_formatted' => $s->submitted_at?->format('d M Y, H:i'),
                    'score' => $s->score !== null ? (float) $s->score : null,
                    'teacher_feedback' => $s->teacher_feedback,
                ])->values()->all() : [],
            ];
        });

        // Pengumuman Sekolah
        $announcementsQuery = SchoolAnnouncement::with('author')
            ->orderByDesc('is_pinned')
            ->latest('id');

        if (! $canManage) {
            $announcementsQuery->where('is_published', true);
        }

        $announcements = $announcementsQuery->take(50)->get()->map(fn (SchoolAnnouncement $ann) => [
            'id' => $ann->id,
            'title' => $ann->title,
            'content' => $ann->content,
            'category' => $ann->category,
            'target_audience' => $ann->target_audience,
            'is_pinned' => (bool) $ann->is_pinned,
            'is_published' => (bool) $ann->is_published,
            'author_name' => $ann->author?->name ?? 'Humas Sekolah',
            'created_at_formatted' => $ann->created_at?->format('d M Y, H:i') ?? '-',
        ]);

        $stats = [
            'total_materials' => LearningMaterial::where('type', 'material')->count(),
            'total_assignments' => LearningMaterial::where('type', 'assignment')->count(),
            'total_submissions' => AssignmentSubmission::count(),
            'total_announcements' => SchoolAnnouncement::count(),
        ];

        $classroomsQuery = Classroom::orderBy('name');
        $subjectsQuery = Subject::where('is_active', true)->orderBy('name');

        if ($isTeacherOnly) {
            $classroomsQuery->whereIn('id', $taughtClassroomIds);
            $subjectsQuery->whereIn('id', $taughtSubjectIds);
        }

        $classrooms = $classroomsQuery->get(['id', 'name']);
        $subjects = $subjectsQuery->get(['id', 'name', 'code']);

        return Inertia::render('Academic/Learning/Index', [
            'canManage' => $canManage,
            'isTeacher' => $isTeacherOnly,
            'teachingAssignments' => $teachingAssignments,
            'studentProfile' => $studentProfile ? [
                'id' => $studentProfile->id,
                'full_name' => $studentProfile->full_name,
                'nisn' => $studentProfile->nisn,
                'classroom_name' => $studentProfile->classroom?->name ?? '-',
            ] : null,
            'materials' => $materials,
            'announcements' => $announcements,
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'classroom_id' => $classroomId ? (int) $classroomId : '',
                'subject_id' => $subjectId ? (int) $subjectId : '',
                'type' => $typeFilter,
            ],
        ]);
    }

    /**
     * Publikasikan Materi Belajar atau Tugas Harian Baru.
     */
    public function storeMaterial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'type' => ['required', 'in:material,assignment'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_url' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'max_score' => ['nullable', 'integer', 'min:10', 'max:100'],
            'is_published' => ['boolean'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        LearningMaterial::create([
            'academic_year_id' => $activeYear?->id,
            'subject_id' => $validated['subject_id'],
            'classroom_id' => $validated['classroom_id'],
            'teacher_id' => $request->user()->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'reference_url' => $validated['reference_url'] ?? null,
            'due_date' => $validated['type'] === 'assignment' ? ($validated['due_date'] ?? null) : null,
            'max_score' => $validated['max_score'] ?? 100,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        $label = $validated['type'] === 'assignment' ? 'Tugas kelas' : 'Materi pembelajaran';

        return back()->with('status', "{$label} '{$validated['title']}' berhasil diterbitkan!");
    }

    /**
     * Perbarui Materi Belajar atau Tugas Harian.
     */
    public function updateMaterial(Request $request, int|string $id): RedirectResponse
    {
        $material = LearningMaterial::findOrFail($id);

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'type' => ['required', 'in:material,assignment'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_url' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'max_score' => ['nullable', 'integer', 'min:10', 'max:100'],
            'is_published' => ['boolean'],
        ]);

        $material->update([
            'subject_id' => $validated['subject_id'],
            'classroom_id' => $validated['classroom_id'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'reference_url' => $validated['reference_url'] ?? null,
            'due_date' => $validated['type'] === 'assignment' ? ($validated['due_date'] ?? null) : null,
            'max_score' => $validated['max_score'] ?? 100,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        return back()->with('status', "Konten '{$material->title}' berhasil diperbarui!");
    }

    /**
     * Hapus Materi atau Tugas Harian.
     */
    public function destroyMaterial(int|string $id): RedirectResponse
    {
        $material = LearningMaterial::findOrFail($id);
        $title = $material->title;
        $material->delete();

        return back()->with('status', "Konten '{$title}' berhasil dihapus.");
    }

    /**
     * Pengumpulan Jawaban Tugas oleh Siswa.
     */
    public function submitAssignment(Request $request, int|string $id): RedirectResponse
    {
        $material = LearningMaterial::where('type', 'assignment')->findOrFail($id);

        $validated = $request->validate([
            'answer_text' => ['required', 'string', 'max:5000'],
            'attachment_url' => ['nullable', 'string', 'max:255'],
        ]);

        $studentProfile = StudentProfile::where('user_id', $request->user()->id)->first();
        if (! $studentProfile) {
            return back()->withErrors(['error' => 'Hanya akun siswa yang dapat mengumpulkan jawaban tugas.']);
        }

        AssignmentSubmission::updateOrCreate(
            [
                'learning_material_id' => $material->id,
                'student_profile_id' => $studentProfile->id,
            ],
            [
                'answer_text' => $validated['answer_text'],
                'attachment_url' => $validated['attachment_url'] ?? null,
                'submitted_at' => now(),
            ]
        );

        return back()->with('status', "Jawaban tugas '{$material->title}' berhasil dikirimkan!");
    }

    /**
     * Penilaian Jawaban Tugas Siswa oleh Guru + Sinkronisasi ke Buku Nilai (Opsional).
     */
    public function gradeSubmission(Request $request, int|string $id): RedirectResponse
    {
        $submission = AssignmentSubmission::with('material')->findOrFail($id);

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'teacher_feedback' => ['nullable', 'string', 'max:1000'],
            'sync_to_gradebook' => ['boolean'],
        ]);

        $submission->update([
            'score' => (float) $validated['score'],
            'teacher_feedback' => $validated['teacher_feedback'] ?? null,
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        // Jika guru mencentang sinkronisasi nilai ke Buku Nilai (StudentGrade -> assignment_score)
        if (! empty($validated['sync_to_gradebook']) && $submission->material) {
            $material = $submission->material;
            $activeYear = AcademicYear::where('is_active', true)->first();
            $academicYearId = $material->academic_year_id ?? $activeYear?->id;

            if ($academicYearId) {
                $grade = StudentGrade::firstOrNew([
                    'academic_year_id' => $academicYearId,
                    'classroom_id' => $material->classroom_id,
                    'subject_id' => $material->subject_id,
                    'student_profile_id' => $submission->student_profile_id,
                ]);

                $assignmentScore = (float) $validated['score'];
                $quiz = (float) ($grade->quiz_score ?? $assignmentScore);
                $midterm = (float) ($grade->midterm_score ?? $assignmentScore);
                $final = (float) ($grade->final_score ?? $assignmentScore);
                $overallScore = round(($assignmentScore * 0.2) + ($quiz * 0.2) + ($midterm * 0.3) + ($final * 0.3), 2);

                $grade->teacher_user_id = $request->user()->id;
                $grade->assignment_score = $assignmentScore;
                $grade->overall_score = $overallScore;
                $grade->letter_grade = $overallScore >= 90 ? 'A' : ($overallScore >= 80 ? 'B' : ($overallScore >= 70 ? 'C' : 'D'));
                $grade->is_passed = $overallScore >= 75;
                $grade->save();
            }
        }

        return back()->with('status', 'Nilai tugas siswa berhasil disimpan!');
    }

    /**
     * Publikasikan Pengumuman Sekolah Baru.
     */
    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'in:akademik,keuangan,kegiatan,penting'],
            'target_audience' => ['required', 'in:semua,siswa,guru,orang_tua'],
            'is_pinned' => ['boolean'],
            'is_published' => ['boolean'],
        ]);

        SchoolAnnouncement::create([
            'author_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category' => $validated['category'],
            'target_audience' => $validated['target_audience'],
            'is_pinned' => $validated['is_pinned'] ?? false,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        return back()->with('status', "Pengumuman '{$validated['title']}' berhasil dipublikasikan!");
    }

    /**
     * Perbarui Pengumuman Sekolah.
     */
    public function updateAnnouncement(Request $request, int|string $id): RedirectResponse
    {
        $announcement = SchoolAnnouncement::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'in:akademik,keuangan,kegiatan,penting'],
            'target_audience' => ['required', 'in:semua,siswa,guru,orang_tua'],
            'is_pinned' => ['boolean'],
            'is_published' => ['boolean'],
        ]);

        $announcement->update($validated);

        return back()->with('status', "Pengumuman '{$announcement->title}' berhasil diperbarui!");
    }

    /**
     * Hapus Pengumuman Sekolah.
     */
    public function destroyAnnouncement(int|string $id): RedirectResponse
    {
        $announcement = SchoolAnnouncement::findOrFail($id);
        $title = $announcement->title;
        $announcement->delete();

        return back()->with('status', "Pengumuman '{$title}' berhasil dihapus.");
    }
}
