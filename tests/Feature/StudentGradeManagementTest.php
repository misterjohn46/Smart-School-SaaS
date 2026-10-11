<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentGradeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected Classroom $classroom;
    protected Subject $subject;
    protected AcademicYear $academicYear;
    protected StudentProfile $student1;
    protected StudentProfile $student2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);
        Role::firstOrCreate(['name' => 'siswa']);

        $this->teacher = User::factory()->create(['name' => 'Pak Joko Widodo, M.Kom', 'email' => 'guru@sekolah.id']);
        $this->teacher->assignRole('guru');

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $dept = Department::create(['name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);
        $grade = GradeLevel::create(['name' => 'Kelas 10', 'level_numeric' => 10]);

        $this->classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'department_id' => $dept->id,
            'grade_level_id' => $grade->id,
            'name' => 'X - RPL 1',
            'capacity' => 36,
        ]);

        $this->subject = Subject::create([
            'code' => 'RPL-01',
            'name' => 'Pemrograman Web',
            'kkm' => 75,
            'type' => 'kejuruan',
            'is_active' => true,
        ]);

        $user1 = User::factory()->create(['name' => 'Budi Santoso']);
        $user1->assignRole('siswa');
        $this->student1 = StudentProfile::create([
            'user_id' => $user1->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '1001001',
            'full_name' => 'Budi Santoso',
            'gender' => 'L',
        ]);

        $user2 = User::factory()->create(['name' => 'Siti Rahma']);
        $user2->assignRole('siswa');
        $this->student2 = StudentProfile::create([
            'user_id' => $user2->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '1001002',
            'full_name' => 'Siti Rahma',
            'gender' => 'P',
        ]);
    }

    public function test_teacher_can_view_grades_index_for_classroom_and_subject(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('academic.grades.index', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Grades/Index')
            ->has('gradeItems', 2)
            ->where('gradeItems.0.name', 'Budi Santoso')
            ->where('gradeItems.1.name', 'Siti Rahma')
            ->has('classrooms')
            ->has('subjects')
            ->has('currentSubject')
            ->where('currentSubject.kkm', 75)
            ->has('stats')
        );
    }

    public function test_teacher_can_batch_store_and_update_student_grades(): void
    {
        // Student 1: Tugas=90, Kuis=85, UTS=80, UAS=85 => Overall = (90*0.2)+(85*0.2)+(80*0.3)+(85*0.3) = 18+17+24+25.5 = 84.5 (Predikat B, Tuntas)
        // Student 2: Tugas=60, Kuis=60, UTS=60, UAS=60 => Overall = 60 (Predikat D, Remedial)
        $response = $this->actingAs($this->teacher)->post(route('academic.grades.batch'), [
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'grades' => [
                [
                    'student_id' => $this->student1->id,
                    'assignment_score' => 90,
                    'quiz_score' => 85,
                    'midterm_score' => 80,
                    'final_score' => 85,
                    'notes' => 'Sangat aktif dalam praktikum',
                ],
                [
                    'student_id' => $this->student2->id,
                    'assignment_score' => 60,
                    'quiz_score' => 60,
                    'midterm_score' => 60,
                    'final_score' => 60,
                    'notes' => 'Perlu bimbingan remedial',
                ],
            ],
        ]);

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('student_grades', [
            'student_profile_id' => $this->student1->id,
            'subject_id' => $this->subject->id,
            'overall_score' => 84.5,
            'letter_grade' => 'B',
            'is_passed' => true,
        ]);

        $this->assertDatabaseHas('student_grades', [
            'student_profile_id' => $this->student2->id,
            'subject_id' => $this->subject->id,
            'overall_score' => 60,
            'letter_grade' => 'D',
            'is_passed' => false,
        ]);
    }

    public function test_grade_calculation_and_kkm_pass_status(): void
    {
        // Siswa mencapai nilai sempurna 95 => Predikat A, Tuntas
        $calcA = StudentGrade::calculateScore(95, 95, 95, 95, 75);
        $this->assertEquals(95.0, $calcA['overall_score']);
        $this->assertEquals('A', $calcA['letter_grade']);
        $this->assertTrue($calcA['is_passed']);

        // Siswa tepat di batas KKM (75) => Predikat C, Tuntas
        $calcC = StudentGrade::calculateScore(75, 75, 75, 75, 75);
        $this->assertEquals(75.0, $calcC['overall_score']);
        $this->assertEquals('C', $calcC['letter_grade']);
        $this->assertTrue($calcC['is_passed']);

        // Siswa di bawah KKM (70) => Predikat D, Tidak Tuntas (Remedial)
        $calcD = StudentGrade::calculateScore(70, 70, 70, 70, 75);
        $this->assertEquals(70.0, $calcD['overall_score']);
        $this->assertEquals('D', $calcD['letter_grade']);
        $this->assertFalse($calcD['is_passed']);
    }

    public function test_batch_store_validates_score_range_between_0_and_100(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('academic.grades.batch'), [
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'grades' => [
                [
                    'student_id' => $this->student1->id,
                    'assignment_score' => 150, // Nilai melebihi 100
                    'quiz_score' => 80,
                    'midterm_score' => 80,
                    'final_score' => 80,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['grades.0.assignment_score']);
    }
}
