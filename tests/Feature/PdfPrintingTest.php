<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\StudentReportRemark;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Cetak PDF e-Raport & KTS massal.
 *
 * ROADMAP sebelumnya mengklaim keduanya menghasilkan PDF, padahal tidak ada paket
 * PDF terpasang sama sekali sehingga hasilnya bergantung dialog cetak browser.
 */
class PdfPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    private function admin(): User
    {
        return User::where('email', 'admin@sekolah.id')->firstOrFail();
    }

    private function assertIsPdf(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_report_card_pdf_is_generated_for_a_student(): void
    {
        $student = StudentProfile::firstOrFail();
        $year = AcademicYear::where('is_active', true)->firstOrFail();
        $subject = Subject::firstOrFail();

        $calc = StudentGrade::calculateScore(80, 85, 90, 88, (int) $subject->kkm);

        StudentGrade::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $student->classroom_id,
            'student_profile_id' => $student->id,
            'subject_id' => $subject->id,
            'assignment_score' => 80,
            'quiz_score' => 85,
            'midterm_score' => 90,
            'final_score' => 88,
            'overall_score' => $calc['overall_score'],
            'letter_grade' => $calc['letter_grade'],
            'is_passed' => $calc['is_passed'],
        ]);

        StudentReportRemark::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $student->classroom_id,
            'student_profile_id' => $student->id,
            'attendance_sick' => 2,
            'attendance_permission' => 1,
            'attendance_unexcused' => 0,
            'homeroom_notes' => 'Perlu meningkatkan ketepatan waktu.',
            'promotion_status' => 'naik_kelas',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('academic.reports.pdf', $student->hash_id));

        $this->assertIsPdf($response);
    }

    public function test_report_card_pdf_rejects_an_unknown_student(): void
    {
        $this->actingAs($this->admin())
            ->get('/academic/reports/hash-ngawur/pdf')
            ->assertNotFound();
    }

    public function test_report_card_pdf_is_closed_to_students(): void
    {
        $student = StudentProfile::firstOrFail();

        $this->actingAs($student->user)
            ->get(route('academic.reports.pdf', $student->hash_id))
            ->assertForbidden();
    }

    public function test_kts_batch_pdf_is_generated(): void
    {
        $response = $this->actingAs($this->admin())->get(route('kts.batch.pdf'));

        $this->assertIsPdf($response);
    }

    public function test_kts_batch_pdf_can_be_filtered_by_classroom(): void
    {
        $classroomId = StudentProfile::firstOrFail()->classroom_id;

        $response = $this->actingAs($this->admin())
            ->get(route('kts.batch.pdf', ['classroom_id' => $classroomId]));

        $this->assertIsPdf($response);
    }

    public function test_kts_batch_pdf_returns_not_found_when_there_is_nothing_to_print(): void
    {
        StudentProfile::query()->delete();

        $this->actingAs($this->admin())
            ->get(route('kts.batch.pdf'))
            ->assertNotFound();
    }

    public function test_kts_batch_pdf_is_closed_to_students(): void
    {
        $student = StudentProfile::firstOrFail();

        $this->actingAs($student->user)
            ->get(route('kts.batch.pdf'))
            ->assertForbidden();
    }
}
