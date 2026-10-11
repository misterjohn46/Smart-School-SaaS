<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\StudentReportRemark;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected Classroom $classroom;
    protected AcademicYear $academicYear;
    protected Subject $subject1;
    protected Subject $subject2;
    protected StudentProfile $student1;
    protected StudentProfile $student2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');

        $this->admin = User::where('email', 'admin@sekolah.id')->firstOrFail();
        $this->teacher = User::where('email', 'guru@sekolah.id')->firstOrFail();

        $this->academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'semester' => 'ganjil',
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
                'is_active' => true,
            ]
        );

        $this->classroom = Classroom::firstOrFail();
        $subjects = Subject::take(2)->get();
        $this->subject1 = $subjects[0];
        $this->subject2 = $subjects[1] ?? Subject::create([
            'code' => 'KDK02',
            'name' => 'Kejuruan 2',
            'passing_grade' => 75.0,
            'is_active' => true,
        ]);

        $this->student1 = StudentProfile::firstOrFail();
        $this->student1->update(['classroom_id' => $this->classroom->id]);

        $user2 = User::factory()->create(['name' => 'Budi Santoso']);
        $this->student2 = StudentProfile::create([
            'user_id' => $user2->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '9988776655',
            'gender' => 'L',
            'full_name' => 'Budi Santoso',
        ]);
    }

    public function test_can_view_reports_and_ledger_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/academic/reports');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Academic/Reports/Index')
            ->has('academicYears')
            ->has('classrooms')
            ->has('subjects')
            ->has('ledgerRows')
            ->has('stats')
        );
    }

    public function test_ledger_calculates_scores_and_ranking_correctly(): void
    {
        // Student 1: Rata-rata 90
        StudentGrade::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject1->id,
            'student_profile_id' => $this->student1->id,
            'teacher_user_id' => $this->teacher->id,
            'assignment_score' => 90.0,
            'quiz_score' => 90.0,
            'midterm_score' => 90.0,
            'final_score' => 90.0,
            'overall_score' => 90.0,
            'letter_grade' => 'A',
            'is_passed' => true,
        ]);

        // Student 2: Rata-rata 70
        StudentGrade::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject1->id,
            'student_profile_id' => $this->student2->id,
            'teacher_user_id' => $this->teacher->id,
            'assignment_score' => 70.0,
            'quiz_score' => 70.0,
            'midterm_score' => 70.0,
            'final_score' => 70.0,
            'overall_score' => 70.0,
            'letter_grade' => 'C',
            'is_passed' => false,
        ]);

        $response = $this->actingAs($this->teacher)->get("/academic/reports?classroom_id={$this->classroom->id}&academic_year_id={$this->academicYear->id}");

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $ledger = $page->toArray()['props']['ledgerRows'];
            $this->assertNotEmpty($ledger);

            // Student 1 harus Rank #1
            $this->assertEquals(1, $ledger[0]['rank']);
            $this->assertEquals(90.0, $ledger[0]['average_score']);

            // Student 2 harus Rank #2
            $this->assertEquals(2, $ledger[1]['rank']);
            $this->assertEquals(70.0, $ledger[1]['average_score']);
        });
    }

    public function test_teacher_can_save_homeroom_remark_and_attendance(): void
    {
        $response = $this->actingAs($this->teacher)->post('/academic/reports/remarks', [
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'student_profile_id' => $this->student1->id,
            'attendance_sick' => 2,
            'attendance_permission' => 1,
            'attendance_unexcused' => 0,
            'homeroom_notes' => 'Prestasi belajar sangat memuaskan, pertahankan di semester mendatang!',
            'extracurriculars' => [
                ['name' => 'Pramuka Wajib', 'grade' => 'A', 'description' => 'Sangat aktif'],
                ['name' => 'Futsal', 'grade' => 'B', 'description' => 'Disiplin'],
            ],
            'promotion_status' => 'naik_kelas',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_report_remarks', [
            'academic_year_id' => $this->academicYear->id,
            'student_profile_id' => $this->student1->id,
            'attendance_sick' => 2,
            'attendance_permission' => 1,
            'promotion_status' => 'naik_kelas',
        ]);
    }
}
