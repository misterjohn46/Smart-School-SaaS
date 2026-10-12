<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\StudentProfile;
use App\Models\StudentViolation;
use App\Models\User;
use App\Models\ViolationCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentViolationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected Classroom $classroom;

    protected StudentProfile $student;

    protected ViolationCategory $categoryLate;

    protected ViolationCategory $categorySmoke;

    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);
        Role::firstOrCreate(['name' => 'siswa']);

        $this->admin = User::factory()->create(['name' => 'Koordinator BK']);
        $this->admin->assignRole('admin');

        $this->teacher = User::factory()->create(['name' => 'Guru Piket, S.Pd']);
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

        $userStudent = User::factory()->create(['name' => 'Aditya Pratama']);
        $userStudent->assignRole('siswa');

        $this->student = StudentProfile::create([
            'user_id' => $userStudent->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '20241001',
            'full_name' => 'Aditya Pratama',
            'gender' => 'L',
        ]);

        $this->categoryLate = ViolationCategory::create([
            'code' => 'DIS-01',
            'name' => 'Terlambat Masuk Sekolah',
            'type' => 'pelanggaran',
            'severity' => 'ringan',
            'points' => 5,
            'action_recommendation' => 'Teguran lisan & pencatatan piket',
            'is_active' => true,
        ]);

        $this->categorySmoke = ViolationCategory::create([
            'code' => 'DIS-05',
            'name' => 'Merokok di Lingkungan Sekolah',
            'type' => 'pelanggaran',
            'severity' => 'berat',
            'points' => 45,
            'action_recommendation' => 'Panggilan orang tua & skorsing 3 hari',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_violations_index_with_props_and_radar(): void
    {
        StudentViolation::create([
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categoryLate->id,
            'reporter_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'notes' => 'Terlambat 20 menit pada jam pelajaran pertama.',
            'points' => 5,
            'status' => 'tercatat',
        ]);

        $response = $this->actingAs($this->admin)->get(route('counseling.violations.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Counseling/Violations/Index')
            ->has('violations.data', 1)
            ->where('violations.data.0.student_name', 'Aditya Pratama')
            ->has('radarStudents', 1)
            ->where('radarStudents.0.total_points', 5)
            ->where('radarStudents.0.warning_level', 'aman')
            ->has('categories')
            ->has('stats')
        );
    }

    public function test_teacher_can_record_student_violation(): void
    {
        $payload = [
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categorySmoke->id,
            'date' => now()->toDateString(),
            'time' => '10:15',
            'notes' => 'Ditemukan merokok di belakang kantin saat jam istirahat.',
            'sanction' => 'Pembersihan area taman & pemanggilan wali murid.',
            'status' => 'dalam_pembinaan',
        ];

        $response = $this->actingAs($this->teacher)->post(route('counseling.violations.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_violations', [
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categorySmoke->id,
            'reporter_user_id' => $this->teacher->id,
            'points' => 45,
            'status' => 'dalam_pembinaan',
        ]);
    }

    public function test_counselor_can_update_violation_and_sanction(): void
    {
        $violation = StudentViolation::create([
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categoryLate->id,
            'reporter_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'notes' => 'Catatan awal',
            'points' => 5,
            'status' => 'tercatat',
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'notes' => 'Kronologi direvisi setelah klarifikasi dengan siswa bersangkutan.',
            'sanction' => 'Tugas merangkum materi di perpustakaan.',
            'follow_up' => 'Siswa berjanji berangkat lebih awal.',
            'status' => 'dalam_pembinaan',
            'points' => 5,
        ];

        $response = $this->actingAs($this->admin)->put(route('counseling.violations.update', $violation->id), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_violations', [
            'id' => $violation->id,
            'sanction' => 'Tugas merangkum materi di perpustakaan.',
            'status' => 'dalam_pembinaan',
        ]);
    }

    public function test_counselor_can_resolve_violation_case(): void
    {
        $violation = StudentViolation::create([
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categoryLate->id,
            'reporter_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'notes' => 'Terlambat.',
            'points' => 5,
            'status' => 'dalam_pembinaan',
        ]);

        $response = $this->actingAs($this->admin)->post(route('counseling.violations.resolve', $violation->id));

        $response->assertRedirect();
        $violation->refresh();
        $this->assertEquals('selesai', $violation->status);
        $this->assertEquals($this->admin->id, $violation->handled_by_user_id);
    }

    public function test_counselor_can_mark_parent_notified(): void
    {
        $violation = StudentViolation::create([
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categorySmoke->id,
            'reporter_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'notes' => 'Kasus berat rokok.',
            'points' => 45,
            'status' => 'tercatat',
        ]);

        $response = $this->actingAs($this->admin)->post(route('counseling.violations.notify-parent', $violation->id));

        $response->assertRedirect();
        $violation->refresh();
        $this->assertTrue($violation->parent_notified);
        $this->assertNotNull($violation->parent_notified_at);
        $this->assertEquals('panggilan_orang_tua', $violation->status);
    }

    public function test_admin_can_delete_violation(): void
    {
        $violation = StudentViolation::create([
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categoryLate->id,
            'reporter_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'notes' => 'Salah input.',
            'points' => 5,
            'status' => 'tercatat',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('counseling.violations.destroy', $violation->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('student_violations', [
            'id' => $violation->id,
        ]);
    }

    public function test_admin_can_manage_violation_categories(): void
    {
        $payload = [
            'code' => 'TATIB-09',
            'name' => 'Membawa Senjata Tajam / Benda Berbahaya',
            'type' => 'pelanggaran',
            'severity' => 'sangat_berat',
            'points' => 100,
            'action_recommendation' => 'Konferensi kasus dan skorsing 1 bulan',
            'is_active' => true,
        ];

        // Store
        $response = $this->actingAs($this->admin)->post(route('counseling.categories.store'), $payload);
        $response->assertRedirect();
        $this->assertDatabaseHas('violation_categories', ['code' => 'TATIB-09', 'points' => 100]);

        $category = ViolationCategory::where('code', 'TATIB-09')->firstOrFail();

        // Update
        $updatePayload = array_merge($payload, ['name' => 'Membawa Benda Berbahaya (Revisi)']);
        $response = $this->actingAs($this->admin)->put(route('counseling.categories.update', $category->id), $updatePayload);
        $response->assertRedirect();
        $this->assertDatabaseHas('violation_categories', ['id' => $category->id, 'name' => 'Membawa Benda Berbahaya (Revisi)']);

        // Delete
        $response = $this->actingAs($this->admin)->delete(route('counseling.categories.destroy', $category->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('violation_categories', ['id' => $category->id]);
    }

    public function test_radar_scoring_calculates_warning_levels_correctly(): void
    {
        // Add 45 points -> Total 45 -> SP 2
        StudentViolation::create([
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categorySmoke->id,
            'reporter_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'notes' => 'Kasus 1',
            'points' => 45,
            'status' => 'tercatat',
        ]);

        $response = $this->actingAs($this->admin)->get(route('counseling.violations.index'));
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Counseling/Violations/Index')
            ->where('radarStudents.0.total_points', 45)
            ->where('radarStudents.0.warning_level', 'sp2')
        );

        // Add 35 more points -> Total 80 -> SP 3
        StudentViolation::create([
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $this->student->id,
            'violation_category_id' => $this->categorySmoke->id,
            'reporter_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'notes' => 'Kasus 2',
            'points' => 35,
            'status' => 'tercatat',
        ]);

        $response = $this->actingAs($this->admin)->get(route('counseling.violations.index'));
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Counseling/Violations/Index')
            ->where('radarStudents.0.total_points', 80)
            ->where('radarStudents.0.warning_level', 'sp3')
        );
    }
}
