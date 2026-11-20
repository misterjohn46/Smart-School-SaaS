<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CbtBank;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtSubmission;
use App\Models\Classroom;
use App\Models\LessonSchedule;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Services\CbtDocxImporterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CbtController extends Controller
{
    /**
     * Tampilkan halaman utama CBT (Ujian Online, Bank Soal, Hasil & Simulator)
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isTeacherOnly = $user && $user->hasRole('guru') && ! $user->hasAnyRole(['admin', 'super_admin', 'operator', 'kepala_sekolah']);

        $examsQuery = CbtExam::with(['bank.subject', 'classroom'])
            ->withCount('submissions')
            ->latest();

        $banksQuery = CbtBank::with(['subject', 'teacher', 'questions'])
            ->withCount('questions')
            ->latest();

        $subjectsQuery = Subject::where('is_active', true)->orderBy('name');
        $classroomsQuery = Classroom::orderBy('name');

        $submissionsQuery = CbtSubmission::with([
            'exam.bank.subject',
            'studentProfile.user',
            'studentProfile.classroom',
        ])
            ->latest();

        if ($isTeacherOnly) {
            $schedules = LessonSchedule::where('teacher_user_id', $user->id)
                ->where('is_active', true)
                ->get(['classroom_id', 'subject_id']);

            $taughtClassroomIds = $schedules->pluck('classroom_id')->unique()->values();
            $taughtSubjectIds = $schedules->pluck('subject_id')->unique()->values();

            $subjectsQuery->whereIn('id', $taughtSubjectIds);
            $classroomsQuery->whereIn('id', $taughtClassroomIds);

            $banksQuery->where(function ($q) use ($user, $taughtSubjectIds) {
                $q->where('teacher_user_id', $user->id)
                    ->orWhereIn('subject_id', $taughtSubjectIds);
            });

            $examsQuery->where(function ($q) use ($taughtClassroomIds, $taughtSubjectIds, $user) {
                $q->whereIn('classroom_id', $taughtClassroomIds)
                    ->orWhereHas('bank', function ($bq) use ($user, $taughtSubjectIds) {
                        $bq->where('teacher_user_id', $user->id)
                            ->orWhereIn('subject_id', $taughtSubjectIds);
                    });
            });

            $submissionsQuery->whereHas('exam', function ($eq) use ($taughtClassroomIds, $taughtSubjectIds, $user) {
                $eq->whereIn('classroom_id', $taughtClassroomIds)
                    ->orWhereHas('bank', function ($bq) use ($user, $taughtSubjectIds) {
                        $bq->where('teacher_user_id', $user->id)
                            ->orWhereIn('subject_id', $taughtSubjectIds);
                    });
            });
        }

        $exams = $examsQuery->get();
        $banks = $banksQuery->get();
        $subjects = $subjectsQuery->get(['id', 'code', 'name']);
        $classrooms = $classroomsQuery->get(['id', 'name']);
        $submissions = $submissionsQuery->take(100)->get();
        $students = StudentProfile::with(['user', 'classroom'])->get();

        // Metrik Agregat CBT
        $totalSubmissions = $submissionsQuery->count();
        $passedCount = (clone $submissionsQuery)->where('is_passed', true)->count();
        $passRate = $totalSubmissions > 0 ? round(($passedCount / $totalSubmissions) * 100, 1) : 0;

        return Inertia::render('Academic/Cbt/Index', [
            'isTeacher' => $isTeacherOnly,
            'exams' => $exams,
            'banks' => $banks,
            'submissions' => $submissions,
            'subjects' => $subjects,
            'classrooms' => $classrooms,
            'students' => $students,
            'stats' => [
                'total_exams' => $exams->count(),
                'active_exams' => $exams->whereIn('status', ['published', 'ongoing'])->count(),
                'total_banks' => $banks->count(),
                'total_questions' => CbtQuestion::whereIn('cbt_bank_id', $banks->pluck('id'))->count(),
                'total_submissions' => $totalSubmissions,
                'pass_rate' => $passRate,
            ],
        ]);
    }

    /**
     * Simpan Bank Soal Baru
     */
    public function storeBank(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        CbtBank::create([
            'title' => $validated['title'],
            'subject_id' => $validated['subject_id'],
            'teacher_user_id' => $request->user()->id,
            'passing_score' => (float) $validated['passing_score'],
            'description' => $validated['description'] ?? null,
            'total_questions' => 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Bank Soal baru berhasil dibuat.');
    }

    /**
     * Perbarui Bank Soal
     */
    public function updateBank(Request $request, string|int $id): RedirectResponse
    {
        $bank = $this->resolveBank($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $bank->update([
            'title' => $validated['title'],
            'subject_id' => $validated['subject_id'],
            'passing_score' => (float) $validated['passing_score'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', "Bank soal '{$bank->title}' berhasil diperbarui.");
    }

    /**
     * Hapus Bank Soal
     */
    public function destroyBank(string|int $id): RedirectResponse
    {
        $bank = $this->resolveBank($id);
        $title = $bank->title;
        $bank->delete();

        return back()->with('success', "Bank soal '{$title}' berhasil dihapus.");
    }

    /**
     * Simpan Butir Soal Baru ke Bank Soal
     */
    public function storeQuestion(Request $request, string|int $bankId): RedirectResponse
    {
        $bank = $this->resolveBank($bankId);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:multiple_choice,essay,true_false,short_answer,matching'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
            'score_weight' => ['required', 'integer', 'min:1'],
        ]);

        $bank->questions()->create([
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'options' => $validated['options'] ?? null,
            'correct_answer' => $validated['correct_answer'] ?? null,
            'explanation' => $validated['explanation'] ?? null,
            'score_weight' => (int) $validated['score_weight'],
        ]);

        $bank->update([
            'total_questions' => $bank->questions()->count(),
        ]);

        return back()->with('success', 'Butir soal baru berhasil ditambahkan.');
    }

    /**
     * Perbarui Butir Soal
     */
    public function updateQuestion(Request $request, string|int $id): RedirectResponse
    {
        $question = $this->resolveQuestion($id);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:multiple_choice,essay,true_false,short_answer,matching'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
            'score_weight' => ['required', 'integer', 'min:1'],
        ]);

        $question->update([
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'options' => $validated['options'] ?? null,
            'correct_answer' => $validated['correct_answer'] ?? null,
            'explanation' => $validated['explanation'] ?? null,
            'score_weight' => (int) $validated['score_weight'],
        ]);

        return back()->with('success', 'Butir soal berhasil diperbarui.');
    }

    /**
     * Hapus Butir Soal
     */
    public function destroyQuestion(string|int $id): RedirectResponse
    {
        $question = $this->resolveQuestion($id);
        $bank = $question->bank;
        $question->delete();

        if ($bank) {
            $bank->update([
                'total_questions' => $bank->questions()->count(),
            ]);
        }

        return back()->with('success', 'Butir soal berhasil dihapus.');
    }

    /**
     * Terbitkan Jadwal / Sesi Ujian CBT
     */
    public function storeExam(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cbt_bank_id' => ['required', 'exists:cbt_banks,id'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'max:150'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:360'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
            'randomize_questions' => ['nullable', 'boolean'],
            'randomize_options' => ['nullable', 'boolean'],
            'show_results' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published,ongoing,completed'],
        ]);

        CbtExam::create([
            'cbt_bank_id' => $validated['cbt_bank_id'],
            'classroom_id' => $validated['classroom_id'] ?? null,
            'title' => $validated['title'],
            'token' => CbtExam::generateToken(),
            'duration_minutes' => (int) $validated['duration_minutes'],
            'start_time' => ! empty($validated['start_time']) ? Carbon::parse($validated['start_time']) : null,
            'end_time' => ! empty($validated['end_time']) ? Carbon::parse($validated['end_time']) : null,
            'randomize_questions' => $request->boolean('randomize_questions', true),
            'randomize_options' => $request->boolean('randomize_options', false),
            'show_results' => $request->boolean('show_results', true),
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Sesi ujian online baru berhasil dibuat dan token ujian diterbitkan.');
    }

    /**
     * Perbarui Sesi Ujian
     */
    public function updateExam(Request $request, string|int $id): RedirectResponse
    {
        $exam = $this->resolveExam($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:360'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
            'randomize_questions' => ['nullable', 'boolean'],
            'show_results' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published,ongoing,completed'],
        ]);

        $exam->update([
            'title' => $validated['title'],
            'classroom_id' => $validated['classroom_id'] ?? null,
            'duration_minutes' => (int) $validated['duration_minutes'],
            'start_time' => ! empty($validated['start_time']) ? Carbon::parse($validated['start_time']) : null,
            'end_time' => ! empty($validated['end_time']) ? Carbon::parse($validated['end_time']) : null,
            'randomize_questions' => $request->boolean('randomize_questions', true),
            'show_results' => $request->boolean('show_results', true),
            'status' => $validated['status'],
        ]);

        return back()->with('success', "Sesi ujian '{$exam->title}' berhasil diperbarui.");
    }

    /**
     * Regenerate Token Ujian
     */
    public function regenerateToken(string|int $id): RedirectResponse
    {
        $exam = $this->resolveExam($id);
        $newToken = CbtExam::generateToken();
        $exam->update(['token' => $newToken]);

        return back()->with('success', "Token ujian untuk '{$exam->title}' diperbarui: {$newToken}");
    }

    /**
     * Hapus Sesi Ujian
     */
    public function destroyExam(string|int $id): RedirectResponse
    {
        $exam = $this->resolveExam($id);
        $title = $exam->title;
        $exam->delete();

        return back()->with('success', "Sesi ujian '{$title}' berhasil dihapus.");
    }

    /**
     * Sinkronisasi Nilai CBT ke Buku Nilai Siswa (student_grades)
     */
    public function pushGradesToGradebook(Request $request, string|int $id): RedirectResponse
    {
        $exam = $this->resolveExam($id);
        $bank = $exam->bank;
        $subjectId = $bank->subject_id;
        $targetColumn = $request->input('target_grade_type', 'quiz_score'); // 'quiz_score' | 'midterm_score' | 'final_score'

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return back()->withErrors(['error' => 'Tidak ada Tahun Ajaran aktif di sistem untuk pengisian Buku Nilai.']);
        }

        $submissions = $exam->submissions()->with('studentProfile')->get();
        if ($submissions->isEmpty()) {
            return back()->withErrors(['error' => 'Belum ada data nilai peserta pada sesi ujian ini.']);
        }

        $syncedCount = 0;
        foreach ($submissions as $sub) {
            $student = $sub->studentProfile;
            if (! $student || ! $student->classroom_id) {
                continue;
            }

            $grade = StudentGrade::firstOrNew([
                'academic_year_id' => $activeYear->id,
                'classroom_id' => $student->classroom_id,
                'subject_id' => $subjectId,
                'student_profile_id' => $student->id,
            ], [
                'teacher_user_id' => $exam->bank->teacher_user_id,
                'assignment_score' => 80.0,
                'quiz_score' => 80.0,
                'midterm_score' => 80.0,
                'final_score' => 80.0,
            ]);

            // Set kolom target
            if ($targetColumn === 'quiz_score') {
                $grade->quiz_score = $sub->total_score;
            } elseif ($targetColumn === 'midterm_score') {
                $grade->midterm_score = $sub->total_score;
            } elseif ($targetColumn === 'final_score') {
                $grade->final_score = $sub->total_score;
            }

            // Hitung kalkulasi akhir
            $calc = StudentGrade::calculateScore(
                (float) $grade->assignment_score,
                (float) $grade->quiz_score,
                (float) $grade->midterm_score,
                (float) $grade->final_score,
                (int) ($bank->subject->kkm ?? 75)
            );

            $grade->overall_score = $calc['overall_score'];
            $grade->letter_grade = $calc['letter_grade'];
            $grade->is_passed = $calc['is_passed'];
            $grade->notes = "Nilai disinkronkan dari CBT: {$exam->title} ({$sub->total_score} poin)";
            $grade->save();

            $syncedCount++;
        }

        return back()->with('success', "Berhasil menyinkronkan {$syncedCount} nilai ujian ke Buku Nilai Siswa ({$targetColumn})!");
    }

    /**
     * Simulator Ujian Siswa (Tes pengerjaan soal dan scoring otomatis)
     */
    public function submitSimulation(Request $request, string|int $examId): RedirectResponse
    {
        $exam = $this->resolveExam($examId);
        $bank = $exam->bank()->with('questions')->firstOrFail();

        $validated = $request->validate([
            'student_profile_id' => ['required', 'exists:student_profiles,id'],
            'answers' => ['required', 'array'], // {"question_id": "A"}
        ]);

        $questions = $bank->questions;
        $totalPossibleWeight = $questions->sum('score_weight') ?: 100;
        $earnedWeight = 0;

        foreach ($questions as $q) {
            $userAns = $validated['answers'][$q->id] ?? null;
            if ($userAns && strtoupper((string) $userAns) === strtoupper((string) $q->correct_answer)) {
                $earnedWeight += $q->score_weight;
            }
        }

        // Kalkulasi skor skala 0 - 100
        $finalScore = round(($earnedWeight / $totalPossibleWeight) * 100, 1);
        $isPassed = $finalScore >= $bank->passing_score;

        CbtSubmission::updateOrCreate([
            'cbt_exam_id' => $exam->id,
            'student_profile_id' => $validated['student_profile_id'],
        ], [
            'started_at' => Carbon::now()->subMinutes(15),
            'submitted_at' => Carbon::now(),
            'answers' => $validated['answers'],
            'total_score' => $finalScore,
            'is_passed' => $isPassed,
            'status' => 'submitted',
        ]);

        return back()->with('success', "Simulasi pengerjaan ujian berhasil! Skor diperoleh: {$finalScore} / 100 (".($isPassed ? 'TUNTAS' : 'REMEDIAL').')');
    }

    // =========================================================================
    // SISI SISWA: PORTAL & ARENA UJIAN CBT DENGAN FITUR ANTI-CHEAT BROWSER
    // =========================================================================

    /**
     * Daftar ujian yang dialokasikan untuk siswa (Portal Siswa)
     */
    public function studentIndex(Request $request): Response|RedirectResponse
    {
        $student = $request->user()->studentProfile;
        if (! $student) {
            return redirect()->route('dashboard')->withErrors(['error' => 'Profil siswa tidak ditemukan untuk akun ini.']);
        }

        $exams = CbtExam::where(function ($q) use ($student) {
            $q->whereNull('classroom_id')
                ->orWhere('classroom_id', $student->classroom_id);
        })
            ->whereIn('status', ['published', 'ongoing', 'completed'])
            ->with(['bank.subject', 'submissions' => fn ($q) => $q->where('student_profile_id', $student->id)])
            ->latest()
            ->get()
            ->map(function ($exam) {
                $sub = $exam->submissions->first();
                $examStatus = 'ready'; // ready | in_progress | completed
                if ($sub) {
                    if ($sub->status === 'in_progress') {
                        $examStatus = 'in_progress';
                    } else {
                        $examStatus = 'completed';
                    }
                }

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'subject' => $exam->bank?->subject?->name ?? 'Mata Pelajaran',
                    'duration_minutes' => $exam->duration_minutes,
                    'total_questions' => $exam->bank?->total_questions ?? 0,
                    'passing_score' => $exam->bank?->passing_score ?? 75,
                    'start_time' => $exam->start_time?->toISOString(),
                    'end_time' => $exam->end_time?->toISOString(),
                    'student_status' => $examStatus,
                    'score' => $sub?->total_score,
                    'is_passed' => $sub?->is_passed,
                    'violations_count' => $sub?->violations_count ?? 0,
                    'submitted_at' => $sub?->submitted_at?->translatedFormat('d M Y, H:i'),
                ];
            });

        return Inertia::render('Portal/Cbt/Index', [
            'exams' => $exams,
            'student' => [
                'name' => $request->user()->name,
                'nisn' => $student->nisn,
                'classroom' => $student->classroom?->name ?? '-',
            ],
        ]);
    }

    /**
     * Validasi Token & Mulai Sesi Ujian Siswa
     */
    public function startExam(Request $request, string|int $id): RedirectResponse
    {
        $exam = $this->resolveExam($id);
        $student = $request->user()->studentProfile;

        if (! $student) {
            return back()->withErrors(['token' => 'Profil siswa tidak ditemukan untuk akun ini.']);
        }

        if ($exam->classroom_id && $exam->classroom_id !== $student->classroom_id) {
            return back()->withErrors(['token' => 'Sesi ujian ini tidak dialokasikan untuk kelas Anda.']);
        }

        $tokenInput = strtoupper(trim((string) $request->input('token', '')));
        if ($tokenInput !== strtoupper(trim($exam->token))) {
            return back()->withErrors(['token' => 'Token ujian tidak cocok atau telah kedaluwarsa.']);
        }

        $submission = CbtSubmission::firstOrCreate([
            'cbt_exam_id' => $exam->id,
            'student_profile_id' => $student->id,
        ], [
            'started_at' => Carbon::now(),
            'status' => 'in_progress',
            'answers' => [],
            'doubtful_answers' => [],
            'violations_count' => 0,
            'total_score' => 0,
        ]);

        if (in_array($submission->status, ['submitted', 'graded'])) {
            return redirect()->route('portal.cbt.result', $exam->id);
        }

        return redirect()->route('portal.cbt.arena', $exam->id);
    }

    /**
     * Arena / Ruang Ujian CBT Siswa dengan Proteksi Anti-Cheat
     */
    public function examArena(Request $request, string|int $id): Response|RedirectResponse
    {
        $exam = $this->resolveExam($id);
        $student = $request->user()->studentProfile;

        if (! $student) {
            return redirect()->route('portal.student');
        }

        $submission = CbtSubmission::where('cbt_exam_id', $exam->id)
            ->where('student_profile_id', $student->id)
            ->first();

        if (! $submission) {
            return redirect()->route('portal.cbt.index')->withErrors(['error' => 'Anda belum menginisialisasi sesi ujian ini dengan token.']);
        }

        if (in_array($submission->status, ['submitted', 'graded'])) {
            return redirect()->route('portal.cbt.result', $exam->id);
        }

        // Hitung sisa waktu mundur
        $totalSeconds = $exam->duration_minutes * 60;
        $elapsedSeconds = Carbon::now()->diffInSeconds($submission->started_at ?? Carbon::now());
        $remainingSeconds = max(0, $totalSeconds - $elapsedSeconds);

        if ($remainingSeconds <= 0) {
            return $this->processFinishExam($exam, $submission, $submission->answers ?? []);
        }

        // Ambil butir soal TANPA kunci jawaban & pembahasan (Pencegahan Inspeksi Kode)
        $questions = $exam->bank->questions()
            ->get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'question_type' => $q->question_type,
                    'options' => $q->options,
                    'score_weight' => $q->score_weight,
                ];
            });

        if ($exam->randomize_questions) {
            // Acak deterministik per siswa agar urutan tetap sama jika halaman di-refresh
            $questions = $questions->sortBy(fn ($q) => md5($q['id'].'-'.$student->id))->values();
        }

        return Inertia::render('Portal/Cbt/ExamArena', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->bank?->subject?->name ?? 'Mata Pelajaran',
                'duration_minutes' => $exam->duration_minutes,
                'remaining_seconds' => $remainingSeconds,
            ],
            'questions' => $questions,
            'savedAnswers' => $submission->answers ?? (object) [],
            'savedDoubts' => $submission->doubtful_answers ?? [],
            'violationsCount' => $submission->violations_count ?? 0,
            'student' => [
                'name' => $request->user()->name,
                'nisn' => $student->nisn,
                'classroom' => $student->classroom?->name ?? '-',
            ],
        ]);
    }

    /**
     * Auto-Save Jawaban Real-Time (AJAX dari Arena Ujian)
     */
    public function autosaveAnswer(Request $request, string|int $id): JsonResponse
    {
        $exam = $this->resolveExam($id);
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['error' => 'Akses tidak sah'], 403);
        }

        $submission = CbtSubmission::where('cbt_exam_id', $exam->id)
            ->where('student_profile_id', $student->id)
            ->first();

        if (! $submission || in_array($submission->status, ['submitted', 'graded'])) {
            return response()->json(['error' => 'Sesi ujian telah ditutup'], 400);
        }

        $answers = $submission->answers ?? [];
        if ($request->has('question_id')) {
            $qId = (string) $request->input('question_id');
            $answers[$qId] = $request->input('answer');
        }

        $doubts = $request->input('doubts', $submission->doubtful_answers ?? []);

        $submission->update([
            'answers' => $answers,
            'doubtful_answers' => $doubts,
        ]);

        return response()->json(['status' => 'saved', 'saved_at' => Carbon::now()->toTimeString()]);
    }

    /**
     * Catat Pelanggaran Anti-Cheat (Ganti Tab / Keluar Fullscreen)
     */
    public function recordViolation(Request $request, string|int $id): JsonResponse
    {
        $exam = $this->resolveExam($id);
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['error' => 'Akses tidak sah'], 403);
        }

        $submission = CbtSubmission::where('cbt_exam_id', $exam->id)
            ->where('student_profile_id', $student->id)
            ->first();

        if (! $submission || in_array($submission->status, ['submitted', 'graded'])) {
            return response()->json(['error' => 'Sesi ujian telah selesai'], 400);
        }

        $newCount = ($submission->violations_count ?? 0) + 1;
        $submission->update(['violations_count' => $newCount]);

        return response()->json([
            'status' => 'recorded',
            'violations_count' => $newCount,
            'max_allowed' => 3,
        ]);
    }

    /**
     * Selesaikan Ujian & Hitung Penilaian Otomatis
     */
    public function finishExam(Request $request, string|int $id): RedirectResponse
    {
        $exam = $this->resolveExam($id);
        $student = $request->user()->studentProfile;

        if (! $student) {
            return redirect()->route('portal.student');
        }

        $submission = CbtSubmission::where('cbt_exam_id', $exam->id)
            ->where('student_profile_id', $student->id)
            ->firstOrFail();

        $answers = $request->input('answers', $submission->answers ?? []);

        return $this->processFinishExam($exam, $submission, $answers);
    }

    /**
     * Proses kalkulasi skor ujian
     */
    protected function processFinishExam(CbtExam $exam, CbtSubmission $submission, array $answers): RedirectResponse
    {
        $bank = $exam->bank()->with('questions')->firstOrFail();
        $questions = $bank->questions;

        $totalPossibleWeight = $questions->sum('score_weight') ?: 100;
        $earnedWeight = 0;
        $hasEssay = false;

        foreach ($questions as $q) {
            if ($q->question_type === 'essay') {
                $hasEssay = true;
                continue;
            }

            $userAns = $answers[$q->id] ?? null;
            $earnedWeight += $this->evaluateQuestionScore($q, $userAns);
        }

        $finalScore = round(($earnedWeight / $totalPossibleWeight) * 100, 1);
        $isPassed = $finalScore >= $bank->passing_score;

        $submission->update([
            'submitted_at' => Carbon::now(),
            'answers' => $answers,
            'total_score' => $finalScore,
            'is_passed' => $isPassed,
            'status' => $hasEssay ? 'submitted' : 'graded',
        ]);

        return redirect()->route('portal.cbt.result', $exam->id)->with('success', 'Ujian Anda telah berhasil dikirimkan!');
    }

    /**
     * Hitung perolehan poin otomatis untuk butir soal objektif (PG, True/False, Short Answer, Matching)
     */
    protected function evaluateQuestionScore(CbtQuestion $q, mixed $userAns): float
    {
        if ($userAns === null || $userAns === '') {
            return 0.0;
        }

        // 1. Benar / Salah (True / False)
        if ($q->question_type === 'true_false') {
            $userNorm = in_array(strtoupper(trim((string) $userAns)), ['TRUE', 'BENAR', 'B'], true) ? 'TRUE' : 'FALSE';
            $corrNorm = in_array(strtoupper(trim((string) $q->correct_answer)), ['TRUE', 'BENAR', 'B'], true) ? 'TRUE' : 'FALSE';
            return ($userNorm === $corrNorm) ? (float) $q->score_weight : 0.0;
        }

        // 2. Jawaban Singkat (Short Answer)
        if ($q->question_type === 'short_answer') {
            $cleanUser = strtolower(trim((string) $userAns));
            $possibleAnswers = array_map(function ($a) {
                return strtolower(trim($a));
            }, preg_split('/[\/\|]/', (string) $q->correct_answer));

            return in_array($cleanUser, $possibleAnswers, true) ? (float) $q->score_weight : 0.0;
        }

        // 3. Menjodohkan (Matching)
        if ($q->question_type === 'matching') {
            $correctPairs = array_filter(array_map('trim', explode(',', strtoupper((string) $q->correct_answer))));
            $totalPairs = count($correctPairs);
            if ($totalPairs === 0) {
                return 0.0;
            }

            $userPairs = is_array($userAns)
                ? array_filter(array_map('trim', array_map('strtoupper', $userAns)))
                : array_filter(array_map('trim', explode(',', strtoupper((string) $userAns))));

            $matchedCount = 0;
            foreach ($correctPairs as $cp) {
                if (in_array($cp, $userPairs, true)) {
                    $matchedCount++;
                }
            }

            return round(($matchedCount / $totalPairs) * $q->score_weight, 2);
        }

        // 4. Pilihan Ganda Biasa & Kompleks (Multiple Choice)
        $correctKeys = array_filter(array_map('trim', explode(',', strtoupper((string) $q->correct_answer))));
        sort($correctKeys);
        $normalizedCorrect = implode(',', $correctKeys);

        if (is_array($userAns)) {
            $userKeys = array_filter(array_map('trim', array_map('strtoupper', $userAns)));
        } else {
            $userKeys = array_filter(array_map('trim', explode(',', strtoupper((string) $userAns))));
        }
        sort($userKeys);
        $normalizedUser = implode(',', $userKeys);

        return ($normalizedUser !== '' && $normalizedUser === $normalizedCorrect) ? (float) $q->score_weight : 0.0;
    }

    /**
     * Halaman Hasil Ujian Siswa
     */
    public function examResult(Request $request, string|int $id): Response|RedirectResponse
    {
        $exam = $this->resolveExam($id);
        $student = $request->user()->studentProfile;

        if (! $student) {
            return redirect()->route('portal.student');
        }

        $submission = CbtSubmission::where('cbt_exam_id', $exam->id)
            ->where('student_profile_id', $student->id)
            ->firstOrFail();

        $bank = $exam->bank()->with('questions')->first();
        $questionsCount = $bank?->questions?->count() ?? 0;
        $answeredCount = count($submission->answers ?? []);

        return Inertia::render('Portal/Cbt/Result', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $bank?->subject?->name ?? 'Mata Pelajaran',
                'passing_score' => $bank?->passing_score ?? 75,
                'show_results' => $exam->show_results,
            ],
            'submission' => [
                'total_score' => $submission->total_score,
                'is_passed' => $submission->is_passed,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at?->translatedFormat('d M Y, H:i:s'),
                'started_at' => $submission->started_at?->translatedFormat('d M Y, H:i:s'),
                'violations_count' => $submission->violations_count ?? 0,
                'answered_count' => $answeredCount,
                'total_questions' => $questionsCount,
                'teacher_notes' => $submission->teacher_notes,
            ],
            'student' => [
                'name' => $request->user()->name,
                'nisn' => $student->nisn,
                'classroom' => $student->classroom?->name ?? '-',
            ],
        ]);
    }

    // =========================================================================
    // SISI GURU / ADMIN: KOREKSI MANUAL SOAL ESSAY & IMPOR/EKSPOR SOAL
    // =========================================================================

    /**
     * Tampilkan lembar koreksi ujian siswa untuk soal Essay
     */
    public function showSubmission(string|int $id): Response
    {
        $submission = $this->resolveSubmission($id)->load([
            'exam.bank.subject',
            'studentProfile.user',
            'studentProfile.classroom',
        ]);

        $bank = $submission->exam->bank()->with('questions')->firstOrFail();
        $questions = $bank->questions;

        return Inertia::render('Academic/Cbt/GradeSubmission', [
            'submission' => $submission,
            'questions' => $questions,
        ]);
    }

    /**
     * Simpan nilai essay guru dan perbarui nilai total akhir
     */
    public function gradeEssay(Request $request, string|int $id): RedirectResponse
    {
        $submission = $this->resolveSubmission($id)->load('exam.bank.questions');
        $questions = $submission->exam->bank->questions;

        $validated = $request->validate([
            'essay_scores' => ['required', 'array'],
            'teacher_notes' => ['nullable', 'string'],
        ]);

        $essayScores = $validated['essay_scores'];
        $totalPossibleWeight = $questions->sum('score_weight') ?: 100;
        $earnedWeight = 0;

        foreach ($questions as $q) {
            if ($q->question_type === 'essay') {
                $earnedWeight += (float) ($essayScores[$q->id] ?? 0);
            } else {
                $userAns = $submission->answers[$q->id] ?? null;
                $earnedWeight += $this->evaluateQuestionScore($q, $userAns);
            }
        }

        $finalScore = round(($earnedWeight / $totalPossibleWeight) * 100, 1);
        $isPassed = $finalScore >= $submission->exam->bank->passing_score;

        $submission->update([
            'essay_scores' => $essayScores,
            'teacher_notes' => $validated['teacher_notes'] ?? null,
            'total_score' => $finalScore,
            'is_passed' => $isPassed,
            'status' => 'graded',
        ]);

        return redirect()->route('academic.cbt.index')->with('success', "Penilaian essay untuk siswa {$submission->studentProfile->user->name} berhasil disimpan! Nilai akhir: {$finalScore}");
    }

    /**
     * Download Template CSV untuk Butir Soal Bank Soal
     */
    public function exportBankTemplate(string|int $id): StreamedResponse
    {
        $bank = $this->resolveBank($id);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"template_soal_{$bank->id}.csv\"",
        ];

        return response()->stream(function () {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['question_text', 'question_type', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation', 'score_weight']);

            fputcsv($output, [
                'Apa kepanjangan dari HTML?',
                'multiple_choice',
                'Hyper Text Markup Language',
                'High Tech Multi Language',
                'Hyperlink and Text Mode',
                'Home Tool Markup Language',
                'A',
                'HTML adalah standar markup web.',
                '10',
            ]);

            fputcsv($output, [
                'Jelaskan cara kerja protokol HTTP!',
                'essay',
                '',
                '',
                '',
                '',
                '',
                'Format jawaban essay siswa.',
                '20',
            ]);

            fclose($output);
        }, 200, $headers);
    }

    /**
     * Impor Soal Massal dari Berkas Word (.docx) atau CSV ke Bank Soal
     */
    public function importQuestions(Request $request, string|int $id): RedirectResponse
    {
        $bank = $this->resolveBank($id);

        $request->validate([
            'file' => ['required', 'file', 'max:25600'],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        // 1. Jika berkas adalah dokumen Word (.docx)
        if (in_array($ext, ['docx', 'doc'])) {
            try {
                $service = app(CbtDocxImporterService::class);
                $result = $service->import($file, $bank);

                $msg = "Berhasil mengimpor {$result['total_imported']} butir soal beserta gambarnya dari berkas Word ke Bank Soal '{$bank->title}'!";
                if (! empty($result['errors'])) {
                    $msg .= ' Beberapa butir dilewati: ' . implode('; ', array_slice($result['errors'], 0, 3));
                }

                return back()->with('success', $msg);
            } catch (\Throwable $e) {
                return back()->withErrors(['file' => 'Gagal membaca berkas Word: ' . $e->getMessage()]);
            }
        }

        // 2. Jika berkas adalah CSV / Plain Text
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            $questionText = $row[0];
            $questionType = in_array($row[1] ?? '', ['multiple_choice', 'essay', 'true_false']) ? $row[1] : 'multiple_choice';
            $options = null;

            if ($questionType === 'multiple_choice') {
                $options = [
                    ['key' => 'A', 'text' => $row[2] ?? ''],
                    ['key' => 'B', 'text' => $row[3] ?? ''],
                    ['key' => 'C', 'text' => $row[4] ?? ''],
                    ['key' => 'D', 'text' => $row[5] ?? ''],
                ];
            }

            $correctAnswer = ! empty($row[6]) ? strtoupper(trim($row[6])) : null;
            $explanation = $row[7] ?? null;
            $scoreWeight = ! empty($row[8]) ? (int) $row[8] : 10;

            $bank->questions()->create([
                'question_text' => $questionText,
                'question_type' => $questionType,
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation,
                'score_weight' => $scoreWeight,
            ]);

            $imported++;
        }

        fclose($handle);

        $bank->update(['total_questions' => $bank->questions()->count()]);

        return back()->with('success', "Berhasil mengimpor {$imported} butir soal ke dalam Bank Soal {$bank->title}!");
    }

    /**
     * Unduh Template Naskah Soal Word (.docx) Resmi
     */
    public function downloadDocxTemplate(CbtDocxImporterService $service): BinaryFileResponse
    {
        $filePath = $service->generateTemplateDocx();

        return response()->download($filePath, 'Template_Soal_CBT_Sekolah.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    protected function resolveBank(string|int $id): CbtBank
    {
        if (is_numeric($id)) {
            return CbtBank::findOrFail((int) $id);
        }

        return CbtBank::findByHashId((string) $id) ?? CbtBank::findOrFail($id);
    }

    protected function resolveQuestion(string|int $id): CbtQuestion
    {
        if (is_numeric($id)) {
            return CbtQuestion::findOrFail((int) $id);
        }

        return CbtQuestion::findByHashId((string) $id) ?? CbtQuestion::findOrFail($id);
    }

    protected function resolveExam(string|int $id): CbtExam
    {
        if (is_numeric($id)) {
            return CbtExam::findOrFail((int) $id);
        }

        return CbtExam::findByHashId((string) $id) ?? CbtExam::findOrFail($id);
    }

    protected function resolveSubmission(string|int $id): CbtSubmission
    {
        if (is_numeric($id)) {
            return CbtSubmission::findOrFail((int) $id);
        }

        return CbtSubmission::findByHashId((string) $id) ?? CbtSubmission::findOrFail($id);
    }
}
