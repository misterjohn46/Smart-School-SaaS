<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassroomManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected AcademicYear $academicYear;

    protected GradeLevel $gradeLevel;

    protected Department $department;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);
        Role::firstOrCreate(['name' => 'siswa']);

        $this->admin = User::factory()->create(['email' => 'admin@sekolah.id']);
        $this->admin->assignRole('admin');

        $this->teacher = User::factory()->create(['name' => 'Budi Santoso, S.Kom', 'email' => 'budi@sekolah.id']);
        $this->teacher->assignRole('guru');

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $this->gradeLevel = GradeLevel::create(['name' => 'Kelas 10', 'level_numeric' => 10]);
        $this->department = Department::create(['name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);

        $this->classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'department_id' => $this->department->id,
            'homeroom_teacher_id' => $this->teacher->id,
            'name' => 'X - RPL 1',
            'capacity' => 36,
        ]);
    }

    public function test_admin_can_view_classrooms_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academic.classrooms.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Classrooms/Index')
            ->has('classrooms.data', 1)
            ->where('classrooms.data.0.name', 'X - RPL 1')
            ->has('academicYears')
            ->has('gradeLevels')
            ->has('departments')
            ->has('teachers')
            ->has('stats')
        );
    }

    public function test_admin_can_filter_classrooms(): void
    {
        Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'department_id' => $this->department->id,
            'name' => 'X - RPL 2',
            'capacity' => 36,
        ]);

        $response = $this->actingAs($this->admin)->get(route('academic.classrooms.index', [
            'search' => 'RPL 2',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Classrooms/Index')
            ->has('classrooms.data', 1)
            ->where('classrooms.data.0.name', 'X - RPL 2')
        );
    }

    public function test_admin_can_create_new_classroom(): void
    {
        $response = $this->actingAs($this->admin)->post(route('academic.classrooms.store'), [
            'name' => 'XI - RPL 1',
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'department_id' => $this->department->id,
            'homeroom_teacher_id' => $this->teacher->id,
            'capacity' => 32,
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('classrooms', [
            'name' => 'XI - RPL 1',
            'capacity' => 32,
            'homeroom_teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_admin_can_update_classroom(): void
    {
        $response = $this->actingAs($this->admin)->put(route('academic.classrooms.update', $this->classroom->id), [
            'name' => 'X - RPL 1 (Unggulan)',
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'department_id' => $this->department->id,
            'homeroom_teacher_id' => $this->teacher->id,
            'capacity' => 40,
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('classrooms', [
            'id' => $this->classroom->id,
            'name' => 'X - RPL 1 (Unggulan)',
            'capacity' => 40,
        ]);
    }

    public function test_admin_cannot_delete_classroom_with_students(): void
    {
        $studentUser = User::factory()->create();
        $studentUser->assignRole('siswa');

        StudentProfile::create([
            'user_id' => $studentUser->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '9988776655',
            'full_name' => 'Ahmad Dahlan',
            'gender' => 'L',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.classrooms.destroy', $this->classroom->id));

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('classrooms', [
            'id' => $this->classroom->id,
        ]);
    }

    public function test_admin_can_delete_empty_classroom(): void
    {
        $emptyClass = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'name' => 'Kelas Kosong',
            'capacity' => 30,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.classrooms.destroy', $emptyClass->id));

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('classrooms', [
            'id' => $emptyClass->id,
        ]);
    }
}
