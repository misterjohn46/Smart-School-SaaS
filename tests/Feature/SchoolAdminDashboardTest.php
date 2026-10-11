<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $schoolAdmin;
    protected User $teacher;
    protected User $student;
    protected User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);
        Role::firstOrCreate(['name' => 'siswa']);
        Role::firstOrCreate(['name' => 'orang_tua']);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@platform.id',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->schoolAdmin = User::factory()->create([
            'email' => 'admin@sekolah.id',
            'password' => bcrypt('password'),
        ]);
        $this->schoolAdmin->assignRole('admin');

        $this->teacher = User::factory()->create([
            'email' => 'guru@sekolah.id',
            'password' => bcrypt('password'),
        ]);
        $this->teacher->assignRole('guru');

        $this->student = User::factory()->create([
            'email' => 'siswa@sekolah.id',
            'password' => bcrypt('password'),
        ]);
        $this->student->assignRole('siswa');

        $this->parent = User::factory()->create([
            'email' => 'ortu@sekolah.id',
            'password' => bcrypt('password'),
        ]);
        $this->parent->assignRole('orang_tua');
    }

    public function test_super_admin_is_redirected_to_filament_admin_panel_after_login(): void
    {
        // 1. Permintaan web standar diarahkan ke /admin
        $response = $this->post('/login', [
            'email' => 'superadmin@platform.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->superAdmin);
        $response->assertRedirect('/admin');

        // Logout terlebih dahulu agar request kedua tidak dicegat middleware guest
        $this->post('/logout');

        // 2. Permintaan via Inertia (X-Inertia) mengembalikan status 409 dengan header X-Inertia-Location
        $inertiaResponse = $this->withHeaders(['X-Inertia' => 'true'])->post('/login', [
            'email' => 'superadmin@platform.id',
            'password' => 'password',
        ]);
        $inertiaResponse->assertStatus(409);
        $inertiaResponse->assertHeader('X-Inertia-Location', '/admin');
    }

    public function test_school_admin_is_redirected_to_vue_dashboard_after_login(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@sekolah.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->schoolAdmin);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_school_admin_accesses_dashboard_and_renders_vue_school_dashboard_component(): void
    {
        $response = $this->actingAs($this->schoolAdmin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SchoolDashboard')
            ->has('school')
            ->has('kpi')
            ->has('recentAttendances')
            ->has('recentPayments')
            ->has('pendingLeaves')
            ->has('classroomsOverview')
            ->where('user.primary_role', 'admin')
        );
    }

    public function test_teacher_accesses_dashboard_and_renders_teacher_dashboard_component(): void
    {
        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('schedule')
            ->has('stats')
            ->has('meetings')
            ->has('cbtOverview')
            ->where('user.primary_role', 'guru')
        );
    }

    public function test_student_and_parent_are_redirected_to_their_respective_portals(): void
    {
        $studentResponse = $this->actingAs($this->student)->get('/dashboard');
        $studentResponse->assertRedirect(route('portal.student'));

        $parentResponse = $this->actingAs($this->parent)->get('/dashboard');
        $parentResponse->assertRedirect(route('portal.parent'));
    }
}
