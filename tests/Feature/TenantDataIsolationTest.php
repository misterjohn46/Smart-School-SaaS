<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Isolasi data antar sekolah pada arsitektur single-database.
 *
 * Mekanismenya: kolom `tenant_id` + global scope Stancl\Tenancy\Database\TenantScope
 * yang diaktifkan middleware InitializeTenancyUnlessCentralDomain berdasarkan domain.
 */
class TenantDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $schoolA;

    private Tenant $schoolB;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'siswa', 'super_admin'] as $role) {
            Role::findOrCreate($role);
        }

        $this->schoolA = $this->createSchool('SMK Alpha', 'alpha.localhost', 'admin.alpha@sekolah.id');
        $this->schoolB = $this->createSchool('SMK Beta', 'beta.localhost', 'admin.beta@sekolah.id');

        $this->seedSchool($this->schoolA, 'RPL', 'X - RPL 1', 'Siswa Alpha', '7000000001');
        $this->seedSchool($this->schoolB, 'RPL', 'X - RPL 1', 'Siswa Beta', '7000000002');
    }

    protected function tearDown(): void
    {
        tenancy()->end();

        parent::tearDown();
    }

    private function createSchool(string $name, string $domain, string $adminEmail): Tenant
    {
        $tenant = new Tenant([
            'id' => (string) Str::uuid(),
            'school_name' => $name,
            'npsn' => (string) random_int(10000000, 99999999),
            'admin_name' => 'Admin '.$name,
            'admin_email' => $adminEmail,
            'admin_phone' => '08120000000',
        ]);
        $tenant->requestedDomain = $domain;
        $tenant->save();

        // Tenant::booted memprovisikan akun admin sekolah beserta tenant_id-nya.
        $admin = User::withoutTenancy()->where('email', $adminEmail)->firstOrFail();
        $admin->assignRole('admin');

        return $tenant->fresh();
    }

    /**
     * Membuat master data minimal di dalam konteks tenant, sehingga tenant_id
     * terisi otomatis oleh trait BelongsToSchool.
     */
    private function seedSchool(Tenant $tenant, string $deptCode, string $classroomName, string $studentName, string $nisn): void
    {
        tenancy()->initialize($tenant);

        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $dept = Department::create(['name' => 'Rekayasa Perangkat Lunak', 'code' => $deptCode]);
        $grade = GradeLevel::create(['name' => 'Kelas 10', 'level_numeric' => 10]);

        $classroom = Classroom::create([
            'academic_year_id' => $year->id,
            'department_id' => $dept->id,
            'grade_level_id' => $grade->id,
            'name' => $classroomName,
            'capacity' => 36,
        ]);

        $studentUser = User::create([
            'name' => $studentName,
            'email' => Str::slug($studentName).'@'.$tenant->getTenantKey().'.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $studentUser->assignRole('siswa');

        StudentProfile::create([
            'user_id' => $studentUser->id,
            'classroom_id' => $classroom->id,
            'nisn' => $nisn,
            'full_name' => $studentName,
            'gender' => 'L',
        ]);

        tenancy()->end();
    }

    private function adminOf(Tenant $tenant): User
    {
        return User::withoutTenancy()
            ->where('tenant_id', $tenant->getTenantKey())
            ->where('email', $tenant->admin_email)
            ->firstOrFail();
    }

    public function test_seeded_records_carry_the_tenant_id_of_their_school(): void
    {
        $alphaStudent = StudentProfile::withoutTenancy()->where('nisn', '7000000001')->firstOrFail();
        $betaStudent = StudentProfile::withoutTenancy()->where('nisn', '7000000002')->firstOrFail();

        $this->assertSame($this->schoolA->getTenantKey(), $alphaStudent->tenant_id);
        $this->assertSame($this->schoolB->getTenantKey(), $betaStudent->tenant_id);
    }

    public function test_two_schools_may_reuse_the_same_department_code_and_classroom_name(): void
    {
        $this->assertSame(2, Department::withoutTenancy()->where('code', 'RPL')->count());
        $this->assertSame(2, Classroom::withoutTenancy()->where('name', 'X - RPL 1')->count());
    }

    public function test_global_scope_limits_queries_to_the_initialized_tenant(): void
    {
        tenancy()->initialize($this->schoolA);

        $this->assertSame(1, StudentProfile::count());
        $this->assertSame('Siswa Alpha', StudentProfile::firstOrFail()->full_name);
        $this->assertNull(StudentProfile::where('nisn', '7000000002')->first());

        tenancy()->end();

        tenancy()->initialize($this->schoolB);

        $this->assertSame(1, StudentProfile::count());
        $this->assertSame('Siswa Beta', StudentProfile::firstOrFail()->full_name);

        tenancy()->end();
    }

    public function test_central_context_still_sees_every_school(): void
    {
        $this->assertFalse(tenancy()->initialized);
        $this->assertSame(2, StudentProfile::count());
    }

    public function test_school_admin_only_sees_own_students_on_its_own_domain(): void
    {
        $response = $this->actingAs($this->adminOf($this->schoolA))
            ->get('http://alpha.localhost/academic/students');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('Siswa Alpha', $content);
        $this->assertStringNotContainsString('Siswa Beta', $content);
    }

    public function test_student_record_of_another_school_is_not_reachable_by_id(): void
    {
        $betaStudent = StudentProfile::withoutTenancy()->where('nisn', '7000000002')->firstOrFail();

        $this->actingAs($this->adminOf($this->schoolA))
            ->delete("http://alpha.localhost/academic/students/{$betaStudent->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('student_profiles', ['id' => $betaStudent->id]);
    }

    public function test_login_with_credentials_of_another_school_is_rejected(): void
    {
        // Akun admin sekolah A tidak boleh bisa login lewat domain sekolah B.
        $this->post('http://beta.localhost/login', [
            'identifier' => $this->schoolA->admin_email,
            'password' => 'password',
            'login_type' => 'staff',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_request_to_an_unknown_domain_returns_not_found(): void
    {
        $this->get('http://sekolah-tidak-terdaftar.localhost/academic/students')
            ->assertNotFound();
    }

    public function test_unknown_domain_is_rejected_on_api_routes_too(): void
    {
        $this->getJson('http://sekolah-tidak-terdaftar.localhost/api/v1/attendance/ping')
            ->assertNotFound();
    }
}
