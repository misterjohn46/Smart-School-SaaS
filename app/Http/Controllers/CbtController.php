<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CbtBank;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtSubmission;
use App\Models\Classroom;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CbtController extends Controller
{
    /**
     * Tampilkan halaman utama CBT (Ujian Online, Bank Soal, Hasil & Simulator)
     */
    public function index(Request $request): Response
    {
        $exams = CbtExam::with(['bank.subject', 'classroom'])
            ->withCount('submissions')
            ->latest()
            ->get();

        $banks = CbtBank::with(['subject', 'teacher', 'questions'])
            ->withCount('questions')
            ->latest()
            ->get();

        $submissions = CbtSubmission::with([
            'exam.bank.subject',
            'studentProfile.user',
            'studentProfile.classroom',
        ])
            ->latest()
            ->take(100)
            ->get();

        $subjects = Subject::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $classrooms = Classroom::orderBy('name')->get(['id', 'name']);
        $students = StudentProfile::with(['user', 'classroom'])->get();

        // Metrik Agregat CBT
        $totalSubmissions = CbtSubmission::count();
        $passedCount = CbtSubmission::where('is_passed', true)->count();
        $passRate = $totalSubmissions > 0 ? round(($passedCount / $totalSubmissions) * 100, 1) : 0;

        return Inertia::render('Academic/Cbt/Index', [
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
                'total_questions' => CbtQuestion::count(),
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
            'question_type' => ['required', 'in:multiple_choice,essay,true_false'],
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
            'question_type' => ['required', 'in:multiple_choice,essay,true_false'],
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
}
