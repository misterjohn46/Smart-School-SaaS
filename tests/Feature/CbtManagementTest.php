<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CbtBank;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtSubmission;
use App\Models\Classroom;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CbtManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected Subject $subject;
    protected Classroom $classroom;
    protected StudentProfile $student;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');

        $this->admin = User::where('email', 'admin@sekolah.id')->firstOrFail();
        $this->teacher = User::where('email', 'guru@sekolah.id')->firstOrFail();

        AcademicYear::query()->update(['is_active' => false]);
        $this->academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'semester' => 'ganjil',
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
                'is_active' => true,
            ]
        );
        $this->academicYear->update(['is_active' => true]);

        $this->classroom = Classroom::firstOrFail();
        $this->subject = Subject::firstOrFail();
        $this->student = StudentProfile::firstOrFail();
    }

    public function test_can_view_cbt_index_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/academic/cbt');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Academic/Cbt/Index')
            ->has('exams')
            ->has('banks')
            ->has('submissions')
            ->has('subjects')
            ->has('classrooms')
            ->has('stats')
        );
    }

    public function test_can_create_and_update_bank_soal(): void
    {
        $response = $this->actingAs($this->teacher)->post('/academic/cbt/banks', [
            'title' => 'Bank Soal Pemrograman Dasar Web',
            'subject_id' => $this->subject->id,
            'passing_score' => 78.0,
            'description' => 'Materi HTML, CSS, dan Javascript Dasar',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cbt_banks', [
            'title' => 'Bank Soal Pemrograman Dasar Web',
            'subject_id' => $this->subject->id,
            'passing_score' => 78.0,
        ]);

        $bank = CbtBank::where('title', 'Bank Soal Pemrograman Dasar Web')->firstOrFail();

        $updateResponse = $this->actingAs($this->teacher)->put("/academic/cbt/banks/{$bank->id}", [
            'title' => 'Bank Soal Pemrograman Web Lanjutan',
            'subject_id' => $this->subject->id,
            'passing_score' => 80.0,
            'description' => 'Revisi deskripsi materi',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('cbt_banks', [
            'id' => $bank->id,
            'title' => 'Bank Soal Pemrograman Web Lanjutan',
            'passing_score' => 80.0,
        ]);
    }

    public function test_can_add_question_to_bank_soal(): void
    {
        $bank = CbtBank::create([
            'title' => 'Bank Soal Desain Grafis',
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'passing_score' => 75.0,
            'total_questions' => 0,
        ]);

        $response = $this->actingAs($this->teacher)->post("/academic/cbt/banks/{$bank->id}/questions", [
            'question_text' => 'Format file gambar yang mendukung transparansi alfa adalah?',
            'question_type' => 'multiple_choice',
            'options' => [
                ['key' => 'A', 'text' => 'JPEG'],
                ['key' => 'B', 'text' => 'PNG'],
                ['key' => 'C', 'text' => 'BMP'],
                ['key' => 'D', 'text' => 'TIFF'],
            ],
            'correct_answer' => 'B',
            'explanation' => 'PNG mendukung transparansi kanal alfa.',
            'score_weight' => 20,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cbt_questions', [
            'cbt_bank_id' => $bank->id,
            'correct_answer' => 'B',
            'score_weight' => 20,
        ]);

        $this->assertEquals(1, $bank->fresh()->total_questions);
    }

    public function test_can_create_exam_session_and_generate_token(): void
    {
        $bank = CbtBank::create([
            'title' => 'Bank Soal Jaringan Dasar',
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'passing_score' => 75.0,
            'total_questions' => 5,
        ]);

        $response = $this->actingAs($this->teacher)->post('/academic/cbt/exams', [
            'cbt_bank_id' => $bank->id,
            'classroom_id' => $this->classroom->id,
            'title' => 'Ulangan Harian 1 Jaringan Dasar',
            'duration_minutes' => 60,
            'randomize_questions' => true,
            'status' => 'published',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cbt_exams', [
            'cbt_bank_id' => $bank->id,
            'classroom_id' => $this->classroom->id,
            'title' => 'Ulangan Harian 1 Jaringan Dasar',
        ]);

        $exam = CbtExam::where('title', 'Ulangan Harian 1 Jaringan Dasar')->firstOrFail();
        $this->assertNotEmpty($exam->token);
        $this->assertEquals(6, strlen($exam->token));

        // Test regenerate token
        $oldToken = $exam->token;
        $regenResponse = $this->actingAs($this->teacher)->post("/academic/cbt/exams/{$exam->id}/regenerate-token");
        $regenResponse->assertRedirect();

        $this->assertNotEquals($oldToken, $exam->fresh()->token);
    }

    public function test_can_simulate_student_exam_submission_and_auto_score(): void
    {
        $bank = CbtBank::create([
            'title' => 'Bank Soal Pemrograman Dasar',
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'passing_score' => 75.0,
            'total_questions' => 2,
        ]);

        $q1 = CbtQuestion::create([
            'cbt_bank_id' => $bank->id,
            'question_text' => 'Tag HTML untuk membuat judul utama adalah?',
            'question_type' => 'multiple_choice',
            'options' => [
                ['key' => 'A', 'text' => '<h1>'],
                ['key' => 'B', 'text' => '<p>'],
            ],
            'correct_answer' => 'A',
            'score_weight' => 50,
        ]);

        $q2 = CbtQuestion::create([
            'cbt_bank_id' => $bank->id,
            'question_text' => 'CSS singkatan dari Cascading Style Sheets?',
            'question_type' => 'true_false',
            'options' => [
                ['key' => 'A', 'text' => 'Benar'],
                ['key' => 'B', 'text' => 'Salah'],
            ],
            'correct_answer' => 'A',
            'score_weight' => 50,
        ]);

        $exam = CbtExam::create([
            'cbt_bank_id' => $bank->id,
            'classroom_id' => $this->classroom->id,
            'title' => 'Kuis Kilat Pemrograman',
            'token' => 'ABC123',
            'duration_minutes' => 30,
            'status' => 'published',
        ]);

        // Kirim jawaban benar untuk kedua soal (harus dapat skor 100 & tuntas)
        $response = $this->actingAs($this->teacher)->post("/academic/cbt/exams/{$exam->id}/submit-simulation", [
            'student_profile_id' => $this->student->id,
            'answers' => [
                $q1->id => 'A',
                $q2->id => 'A',
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cbt_submissions', [
            'cbt_exam_id' => $exam->id,
            'student_profile_id' => $this->student->id,
            'total_score' => 100.0,
            'is_passed' => true,
        ]);
    }

    public function test_can_push_cbt_grades_to_student_gradebook(): void
    {
        $bank = CbtBank::create([
            'title' => 'Bank Soal Basis Data',
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'passing_score' => 75.0,
            'total_questions' => 1,
        ]);

        $exam = CbtExam::create([
            'cbt_bank_id' => $bank->id,
            'classroom_id' => $this->classroom->id,
            'title' => 'UH 1 Basis Data SQL',
            'token' => 'XYZ888',
            'duration_minutes' => 45,
            'status' => 'completed',
        ]);

        CbtSubmission::create([
            'cbt_exam_id' => $exam->id,
            'student_profile_id' => $this->student->id,
            'total_score' => 88.0,
            'is_passed' => true,
            'status' => 'submitted',
        ]);

        // Push nilai CBT ke Buku Nilai (E-Rapor) sebagai quiz_score
        $response = $this->actingAs($this->teacher)->post("/academic/cbt/exams/{$exam->id}/push-grades", [
            'target_grade_type' => 'quiz_score',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('student_grades', [
            'academic_year_id' => $this->academicYear->id,
            'student_profile_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'quiz_score' => 88.0,
        ]);
    }
}
