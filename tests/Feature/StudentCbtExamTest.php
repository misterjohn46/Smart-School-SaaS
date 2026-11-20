<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CbtBank;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtSubmission;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentCbtExamTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $studentUser;
    protected StudentProfile $studentProfile;
    protected Classroom $classroom;
    protected Subject $subject;
    protected CbtBank $bank;
    protected CbtExam $exam;
    protected CbtQuestion $mcQuestion;
    protected CbtQuestion $essayQuestion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');

        $this->admin = User::where('email', 'admin@sekolah.id')->firstOrFail();
        $this->teacher = User::where('email', 'guru@sekolah.id')->firstOrFail();
        $this->studentUser = User::where('email', 'siswa@sekolah.id')->firstOrFail();

        $this->studentProfile = StudentProfile::firstOrCreate(
            ['user_id' => $this->studentUser->id],
            [
                'nisn' => '0098765432',
                'nis' => '2026001',
                'full_name' => 'Budi Pratama',
                'gender' => 'L',
                'admission_year' => 2026,
            ]
        );

        $this->classroom = Classroom::firstOrFail();
        $this->studentProfile->update(['classroom_id' => $this->classroom->id]);

        $this->subject = Subject::firstOrFail();

        AcademicYear::query()->update(['is_active' => false]);
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'semester' => 'ganjil',
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
                'is_active' => true,
            ]
        );
        $academicYear->update(['is_active' => true]);

        // Buat Bank Soal
        $this->bank = CbtBank::create([
            'tenant_id' => $this->studentUser->tenant_id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'title' => 'Bank Soal Ujian Tengah Semester',
            'passing_score' => 70,
            'total_questions' => 2,
        ]);

        // Soal 1: Pilihan Ganda (Bobot 50)
        $this->mcQuestion = CbtQuestion::create([
            'tenant_id' => $this->studentUser->tenant_id,
            'cbt_bank_id' => $this->bank->id,
            'question_text' => 'Apa kepanjangan dari CBT?',
            'question_type' => 'multiple_choice',
            'options' => [
                ['key' => 'A', 'text' => 'Computer Based Test'],
                ['key' => 'B', 'text' => 'Central Bank Technology'],
                ['key' => 'C', 'text' => 'Common Basic Training'],
            ],
            'correct_answer' => 'A',
            'explanation' => 'Kunci A adalah singkatan resmi Computer Based Test.',
            'score_weight' => 50,
        ]);

        // Soal 2: Essay (Bobot 50)
        $this->essayQuestion = CbtQuestion::create([
            'tenant_id' => $this->studentUser->tenant_id,
            'cbt_bank_id' => $this->bank->id,
            'question_text' => 'Jelaskan manfaat ujian berbasis komputer!',
            'question_type' => 'essay',
            'options' => null,
            'correct_answer' => null,
            'explanation' => 'Efisiensi waktu, paperless, dan akurasi koreksi.',
            'score_weight' => 50,
        ]);

        // Buat Sesi Ujian Aktif
        $this->exam = CbtExam::create([
            'tenant_id' => $this->studentUser->tenant_id,
            'cbt_bank_id' => $this->bank->id,
            'classroom_id' => $this->classroom->id,
            'title' => 'UTS Online Pemrograman Web',
            'token' => 'EXAM88',
            'duration_minutes' => 60,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(120),
            'randomize_questions' => true,
            'show_results' => true,
            'status' => 'published',
        ]);
    }

    public function test_student_can_view_cbt_catalog(): void
    {
        $response = $this->actingAs($this->studentUser)->get('/portal/cbt');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/Cbt/Index')
            ->has('exams')
            ->has('student')
        );
    }

    public function test_invalid_token_is_rejected(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->post("/portal/cbt/{$this->exam->id}/start", [
                'token' => 'WRONG1',
            ]);

        $response->assertSessionHasErrors(['token']);
    }

    public function test_valid_token_starts_exam_and_creates_submission(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->post("/portal/cbt/{$this->exam->id}/start", [
                'token' => 'EXAM88',
            ]);

        $response->assertRedirect("/portal/cbt/{$this->exam->id}/arena");

        $this->assertDatabaseHas('cbt_submissions', [
            'cbt_exam_id' => $this->exam->id,
            'student_profile_id' => $this->studentProfile->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_arena_does_not_leak_correct_answer_or_explanation(): void
    {
        // Start exam
        $this->actingAs($this->studentUser)
            ->post("/portal/cbt/{$this->exam->id}/start", ['token' => 'EXAM88']);

        $response = $this->actingAs($this->studentUser)->get("/portal/cbt/{$this->exam->id}/arena");

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->component('Portal/Cbt/ExamArena')
                ->has('exam')
                ->has('questions', 2);

            $questions = $page->toArray()['props']['questions'];
            foreach ($questions as $q) {
                $this->assertArrayNotHasKey('correct_answer', $q, 'Kunci jawaban tidak boleh bocor ke client');
                $this->assertArrayNotHasKey('explanation', $q, 'Pembahasan tidak boleh bocor ke client');
                $this->assertArrayHasKey('question_text', $q);
            }
        });
    }

    public function test_autosave_answers_and_doubts(): void
    {
        // Init submission
        $submission = CbtSubmission::create([
            'tenant_id' => $this->studentUser->tenant_id,
            'cbt_exam_id' => $this->exam->id,
            'student_profile_id' => $this->studentProfile->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->postJson("/portal/cbt/{$this->exam->id}/autosave", [
                'question_id' => (string) $this->mcQuestion->id,
                'answer' => 'A',
                'doubts' => [$this->mcQuestion->id],
            ]);

        $response->assertOk();
        $response->assertJson(['status' => 'saved']);

        $submission->refresh();
        $this->assertEquals('A', $submission->answers[$this->mcQuestion->id]);
        $this->assertContains($this->mcQuestion->id, $submission->doubtful_answers);
    }

    public function test_record_anti_cheat_violation(): void
    {
        $submission = CbtSubmission::create([
            'tenant_id' => $this->studentUser->tenant_id,
            'cbt_exam_id' => $this->exam->id,
            'student_profile_id' => $this->studentProfile->id,
            'started_at' => now(),
            'status' => 'in_progress',
            'violations_count' => 0,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->postJson("/portal/cbt/{$this->exam->id}/violation");

        $response->assertOk();
        $response->assertJson([
            'status' => 'recorded',
            'violations_count' => 1,
            'max_allowed' => 3,
        ]);

        $submission->refresh();
        $this->assertEquals(1, $submission->violations_count);
    }

    public function test_finish_exam_grades_objective_part(): void
    {
        $submission = CbtSubmission::create([
            'tenant_id' => $this->studentUser->tenant_id,
            'cbt_exam_id' => $this->exam->id,
            'student_profile_id' => $this->studentProfile->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->post("/portal/cbt/{$this->exam->id}/finish", [
                'answers' => [
                    (string) $this->mcQuestion->id => 'A', // Benar (50 poin)
                    (string) $this->essayQuestion->id => 'CBT sangat efisien.',
                ],
            ]);

        $response->assertRedirect("/portal/cbt/{$this->exam->id}/result");

        $submission->refresh();
        // Karena ada essay, status adalah submitted menunggu koreksi guru
        $this->assertEquals('submitted', $submission->status);
        $this->assertEquals(50.0, (float) $submission->total_score);
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_teacher_can_view_and_grade_essay_submission(): void
    {
        $submission = CbtSubmission::create([
            'tenant_id' => $this->studentUser->tenant_id,
            'cbt_exam_id' => $this->exam->id,
            'student_profile_id' => $this->studentProfile->id,
            'started_at' => now(),
            'submitted_at' => now(),
            'answers' => [
                (string) $this->mcQuestion->id => 'A',
                (string) $this->essayQuestion->id => 'Jawaban essay siswa yang sangat lengkap.',
            ],
            'total_score' => 50.0,
            'status' => 'submitted',
        ]);

        // Guru buka halaman koreksi
        $viewResponse = $this->actingAs($this->teacher)
            ->get("/academic/cbt/submissions/{$submission->id}");

        $viewResponse->assertOk();
        $viewResponse->assertInertia(fn ($page) => $page
            ->component('Academic/Cbt/GradeSubmission')
            ->has('submission')
            ->has('questions')
        );

        // Guru simpan nilai essay (nilai essay 45 dari bobot 50) -> total earned = 50 + 45 = 95
        $gradeResponse = $this->actingAs($this->teacher)
            ->post("/academic/cbt/submissions/{$submission->id}/grade-essay", [
                'essay_scores' => [
                    (string) $this->essayQuestion->id => 45,
                ],
                'teacher_notes' => 'Penjelasan essay sangat bagus dan komprehensif.',
            ]);

        $gradeResponse->assertRedirect('/academic/cbt');

        $submission->refresh();
        $this->assertEquals('graded', $submission->status);
        $this->assertEquals(95.0, (float) $submission->total_score);
        $this->assertTrue($submission->is_passed);
        $this->assertEquals('Penjelasan essay sangat bagus dan komprehensif.', $submission->teacher_notes);
    }

    public function test_export_template_and_import_questions_csv(): void
    {
        // 1. Ekspor template CSV
        $exportResponse = $this->actingAs($this->teacher)
            ->get("/academic/cbt/banks/{$this->bank->id}/export-template");

        $exportResponse->assertOk();
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $exportResponse->baseResponse);

        // 2. Impor berkas CSV ke bank soal
        $csvContent = "question_text,question_type,option_a,option_b,option_c,option_d,correct_answer,explanation,score_weight\n"
            . "\"Apa warna langit siang hari?\",multiple_choice,Biru,Merah,Kuning,Hijau,A,\"Warna biru karena hamburan rayleigh\",10\n"
            . "\"Sebutkan 3 komponen utama CPU!\",essay,,,,,,Jawaban essay CPU,20\n";

        $file = UploadedFile::fake()->createWithContent('soal_baru.csv', $csvContent);

        $importResponse = $this->actingAs($this->teacher)
            ->post("/academic/cbt/banks/{$this->bank->id}/import-questions", [
                'file' => $file,
            ]);

        $importResponse->assertSessionHas('success');

        $this->assertDatabaseHas('cbt_questions', [
            'cbt_bank_id' => $this->bank->id,
            'question_text' => 'Apa warna langit siang hari?',
            'correct_answer' => 'A',
        ]);

        $this->assertDatabaseHas('cbt_questions', [
            'cbt_bank_id' => $this->bank->id,
            'question_text' => 'Sebutkan 3 komponen utama CPU!',
            'question_type' => 'essay',
        ]);
    }
}
