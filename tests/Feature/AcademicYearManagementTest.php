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

class AcademicYearManagementTest extends TestCase
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

    public function test_admin_can_view_academic_years_index(): void
    {
        AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('academic.academic-years.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/AcademicYears/Index')
            ->has('academicYears.data', 1)
            ->where('academicYears.data.0.name', '2024/2025')
            ->has('activeYear')
            ->has('stats')
        );
    }

    public function test_admin_can_create_academic_year(): void
    {
        $payload = [
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)->post(route('academic.academic-years.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('academic_years', [
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_activate_academic_year_and_deactivates_others(): void
    {
        $year1 = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $year2 = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'genap',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('academic.academic-years.activate', $year2->id));

        $response->assertRedirect();

        $year1->refresh();
        $year2->refresh();

        $this->assertFalse($year1->is_active);
        $this->assertTrue($year2->is_active);
    }

    public function test_admin_can_update_academic_year(): void
    {
        $year = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);

        $payload = [
            'name' => '2024/2025 Revisi',
            'semester' => 'genap',
            'start_date' => '2025-01-05',
            'end_date' => '2025-06-25',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)->put(route('academic.academic-years.update', $year->id), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('academic_years', [
            'id' => $year->id,
            'name' => '2024/2025 Revisi',
            'semester' => 'genap',
        ]);
    }

    public function test_admin_can_delete_inactive_academic_year_without_classrooms(): void
    {
        $year = AcademicYear::create([
            'name' => '2022/2023',
            'semester' => 'genap',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.academic-years.destroy', $year->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('academic_years', [
            'id' => $year->id,
        ]);
    }

    public function test_admin_cannot_delete_active_academic_year(): void
    {
        $year = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.academic-years.destroy', $year->id));

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('academic_years', [
            'id' => $year->id,
        ]);
    }

    public function test_admin_cannot_delete_academic_year_with_classrooms(): void
    {
        $year = AcademicYear::create([
            'name' => '2023/2024',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);

        $dept = Department::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $grade = GradeLevel::create(['name' => 'Kelas 10', 'level_numeric' => 10]);

        Classroom::create([
            'academic_year_id' => $year->id,
            'department_id' => $dept->id,
            'grade_level_id' => $grade->id,
            'name' => 'X - RPL 1',
            'capacity' => 36,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.academic-years.destroy', $year->id));

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('academic_years', [
            'id' => $year->id,
        ]);
    }
}
