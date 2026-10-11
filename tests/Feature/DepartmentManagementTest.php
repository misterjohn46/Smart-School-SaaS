<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);

        $this->admin = User::factory()->create(['name' => 'Administrator Sekolah']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_departments_index(): void
    {
        Department::create([
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'description' => 'Kejuruan pemrograman & software engineering.',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('academic.departments.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.code', 'RPL')
            ->has('stats')
        );
    }

    public function test_admin_can_create_department(): void
    {
        $payload = [
            'code' => 'tkj',
            'name' => 'Teknik Komputer dan Jaringan',
            'description' => 'Fokus infrastruktur jaringan, router, dan server.',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('academic.departments.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('departments', [
            'code' => 'TKJ',
            'name' => 'Teknik Komputer dan Jaringan',
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_duplicate_department_code(): void
    {
        Department::create([
            'code' => 'DKV',
            'name' => 'Desain Komunikasi Visual',
            'is_active' => true,
        ]);

        $payload = [
            'code' => 'DKV',
            'name' => 'Desain Grafis dan Komunikasi',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('academic.departments.store'), $payload);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_admin_can_update_department(): void
    {
        $dept = Department::create([
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'is_active' => true,
        ]);

        $payload = [
            'code' => 'RPL',
            'name' => 'Pengembangan Perangkat Lunak & Gim (PPLG)',
            'description' => 'Kurikulum Merdeka Kejuruan Informatika.',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->put(route('academic.departments.update', $dept->id), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'name' => 'Pengembangan Perangkat Lunak & Gim (PPLG)',
        ]);
    }

    public function test_admin_can_toggle_department_status(): void
    {
        $dept = Department::create([
            'code' => 'AKL',
            'name' => 'Akuntansi dan Keuangan Lembaga',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('academic.departments.toggle-status', $dept->id));

        $response->assertRedirect();
        $dept->refresh();
        $this->assertFalse($dept->is_active);

        // Toggle back
        $response = $this->actingAs($this->admin)->post(route('academic.departments.toggle-status', $dept->id));
        $response->assertRedirect();
        $dept->refresh();
        $this->assertTrue($dept->is_active);
    }

    public function test_admin_can_delete_department_without_classrooms(): void
    {
        $dept = Department::create([
            'code' => 'MM',
            'name' => 'Multimedia Legacy',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.departments.destroy', $dept->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('departments', [
            'id' => $dept->id,
        ]);
    }

    public function test_admin_cannot_delete_department_with_classrooms(): void
    {
        $year = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $dept = Department::create([
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'is_active' => true,
        ]);

        $grade = GradeLevel::create(['name' => 'Kelas 10', 'level_numeric' => 10]);

        Classroom::create([
            'academic_year_id' => $year->id,
            'department_id' => $dept->id,
            'grade_level_id' => $grade->id,
            'name' => 'X - RPL 1',
            'capacity' => 36,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.departments.destroy', $dept->id));

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
        ]);
    }
}
