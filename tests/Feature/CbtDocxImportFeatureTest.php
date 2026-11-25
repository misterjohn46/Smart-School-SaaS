<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CbtBank;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtSubmission;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use App\Services\CbtDocxImporterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CbtDocxImportFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $studentUser;

    protected StudentProfile $studentProfile;

    protected CbtBank $bank;

    protected Subject $subject;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'guru']);
        Role::firstOrCreate(['name' => 'siswa']);

        $this->teacher = User::factory()->create(['name' => 'Guru Pengajar']);
        $this->teacher->assignRole('guru');

        $this->subject = Subject::create([
            'code' => 'KIM-01',
            'name' => 'Kimia Industri',
            'kkm' => 75,
            'type' => 'umum',
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $dept = Department::create([
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Tingkat X',
            'level_numeric' => 10,
        ]);

        $this->classroom = Classroom::create([
            'name' => 'X - RPL 1',
            'academic_year_id' => $academicYear->id,
            'department_id' => $dept->id,
            'grade_level_id' => $gradeLevel->id,
            'homeroom_teacher_id' => $this->teacher->id,
        ]);

        $this->studentUser = User::factory()->create(['name' => 'Budi Santoso']);
        $this->studentUser->assignRole('siswa');

        $this->studentProfile = StudentProfile::create([
            'user_id' => $this->studentUser->id,
            'classroom_id' => $this->classroom->id,
            'full_name' => 'Budi Santoso',
            'nisn' => '1234567890',
            'gender' => 'L',
        ]);

        $this->bank = CbtBank::create([
            'title' => 'Bank Soal Kimia Dasar',
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'passing_score' => 75,
            'total_questions' => 0,
            'is_active' => true,
        ]);
    }

    public function test_teacher_can_download_docx_template(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('academic.cbt.template-docx'));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename=Template_Soal_CBT_Sekolah.docx');
    }

    public function test_teacher_can_import_docx_file_into_bank(): void
    {
        $service = new CbtDocxImporterService;
        $tempDocx = $service->generateTemplateDocx();

        $uploadedFile = new UploadedFile(
            $tempDocx,
            'Naskah_Soal_Kimia.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $response = $this->actingAs($this->teacher)
            ->post(route('academic.cbt.banks.import-questions', $this->bank->id), [
                'file' => $uploadedFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('cbt_questions', 8);
        $this->assertSame(8, $this->bank->fresh()->total_questions);

        @unlink($tempDocx);
    }

    public function test_complex_multiple_choice_grading_evaluates_correctly(): void
    {
        // Buat soal pilihan ganda kompleks (Kunci: A, C)
        $qComplex = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_text' => 'Pilihlah 2 unsur logam di bawah ini!',
            'question_type' => 'multiple_choice',
            'options' => [
                ['key' => 'A', 'text' => 'Besi (Fe)'],
                ['key' => 'B', 'text' => 'Oksigen (O)'],
                ['key' => 'C', 'text' => 'Tembaga (Cu)'],
                ['key' => 'D', 'text' => 'Nitrogen (N)'],
            ],
            'correct_answer' => 'A, C',
            'score_weight' => 50,
        ]);

        // Buat soal pilihan ganda tunggal (Kunci: B)
        $qSingle = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_text' => 'Lambang kimia dari air adalah...',
            'question_type' => 'multiple_choice',
            'options' => [
                ['key' => 'A', 'text' => 'CO2'],
                ['key' => 'B', 'text' => 'H2O'],
                ['key' => 'C', 'text' => 'NaCl'],
                ['key' => 'D', 'text' => 'O2'],
            ],
            'correct_answer' => 'B',
            'score_weight' => 50,
        ]);

        $this->bank->update(['total_questions' => 2]);

        $exam = CbtExam::create([
            'cbt_bank_id' => $this->bank->id,
            'classroom_id' => $this->classroom->id,
            'title' => 'Ujian Kimia Semester 1',
            'token' => 'KIM123',
            'duration_minutes' => 60,
            'status' => 'published',
        ]);

        $submission = CbtSubmission::create([
            'cbt_exam_id' => $exam->id,
            'student_profile_id' => $this->studentProfile->id,
            'status' => 'in_progress',
            'total_score' => 0,
            'answers' => [],
        ]);

        // Siswa menjawab C, A untuk soal 1 (urutan terbalik tetap benar), dan B untuk soal 2
        $response = $this->actingAs($this->studentUser)
            ->post(route('portal.cbt.finish', $exam->id), [
                'answers' => [
                    $qComplex->id => ['C', 'A'],
                    $qSingle->id => 'B',
                ],
            ]);

        $response->assertRedirect(route('portal.cbt.result', $exam->id));

        $submission->refresh();
        $this->assertEquals(100.0, (float) $submission->total_score);
        $this->assertTrue((bool) $submission->is_passed);
        $this->assertSame('graded', $submission->status);
    }

    public function test_short_answer_and_matching_auto_grading(): void
    {
        $qShort = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_text' => 'Nama ibu kota baru Indonesia adalah...',
            'question_type' => 'short_answer',
            'correct_answer' => 'Nusantara / IKN',
            'score_weight' => 50,
        ]);

        $qMatching = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_text' => 'Jodohkanlah premis berikut!',
            'question_type' => 'matching',
            'correct_answer' => '1-B, 2-A',
            'score_weight' => 50,
        ]);

        $exam = CbtExam::create([
            'cbt_bank_id' => $this->bank->id,
            'classroom_id' => $this->classroom->id,
            'title' => 'Ujian AKM Literasi',
            'token' => 'AKM789',
            'duration_minutes' => 60,
            'status' => 'published',
        ]);

        $submission = CbtSubmission::create([
            'cbt_exam_id' => $exam->id,
            'student_profile_id' => $this->studentProfile->id,
            'status' => 'in_progress',
            'total_score' => 0,
            'answers' => [],
        ]);

        // Siswa menjawab 'nusantara' (huruf kecil) dan '1-B, 2-A'
        $response = $this->actingAs($this->studentUser)
            ->post(route('portal.cbt.finish', $exam->id), [
                'answers' => [
                    $qShort->id => 'nusantara ',
                    $qMatching->id => '1-B, 2-A',
                ],
            ]);

        $response->assertRedirect(route('portal.cbt.result', $exam->id));

        $submission->refresh();
        $this->assertEquals(100.0, (float) $submission->total_score);
        $this->assertSame('graded', $submission->status);
    }
}
