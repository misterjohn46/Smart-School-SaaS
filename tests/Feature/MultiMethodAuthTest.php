<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\AuditTrail;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MultiMethodAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed-central');
        RateLimiter::clear('guru@sekolah.id|127.0.0.1');
        RateLimiter::clear('ahmad.fauzi|127.0.0.1');
        RateLimiter::clear('0051234567|127.0.0.1');
    }

    public function test_staff_can_login_with_email_and_password(): void
    {
        $role = Role::firstOrCreate(['name' => RoleName::Teacher->value]);

        $teacher = User::factory()->create([
            'email' => 'guru_test@sekolah.id',
            'username' => 'guru.test',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $teacher->syncRoles([$role->name]);

        $response = $this->post('/login', [
            'login_type' => 'staff',
            'identifier' => 'guru_test@sekolah.id',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($teacher);
        $response->assertRedirect(route('dashboard', absolute: false));

        // Verifikasi audit trail tercatat sukses
        $this->assertDatabaseHas('audit_trails', [
            'user_id' => $teacher->id,
            'identifier_attempted' => 'guru_test@sekolah.id',
            'status' => 'success',
        ]);

        // Verifikasi akses halaman /dashboard guru berhasil
        $dashboardResponse = $this->actingAs($teacher)->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('user')
            ->has('stats')
            ->has('schedule')
            ->has('cbtOverview')
            ->has('meetings')
        );
    }

    public function test_staff_can_login_with_username(): void
    {
        $role = Role::firstOrCreate(['name' => RoleName::Teacher->value]);

        $teacher = User::factory()->create([
            'email' => 'guru_username@sekolah.id',
            'username' => 'guru.username',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $teacher->syncRoles([$role->name]);

        $response = $this->post('/login', [
            'login_type' => 'staff',
            'identifier' => 'guru.username',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($teacher);
    }

    public function test_student_can_login_with_nisn_and_redirects_to_student_portal(): void
    {
        $role = Role::firstOrCreate(['name' => RoleName::Student->value]);

        $student = User::factory()->create([
            'name' => 'Siswa Test',
            'email' => 'siswa_test@sekolah.id',
            'nisn' => '0059998888',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $student->syncRoles([$role->name]);

        $response = $this->post('/login', [
            'login_type' => 'student',
            'identifier' => '0059998888',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect(route('portal.student', absolute: false));
    }

    public function test_parent_can_login_with_student_nisn_and_birth_date(): void
    {
        $parentRole = Role::firstOrCreate(['name' => RoleName::Parent->value]);
        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value]);

        $parent = User::factory()->create([
            'name' => 'Orang Tua Test',
            'email' => 'ortu_test@sekolah.id',
            'is_active' => true,
        ]);
        $parent->syncRoles([$parentRole->name]);

        $student = User::factory()->create([
            'name' => 'Anak Test',
            'nisn' => '0057776666',
        ]);
        $student->syncRoles([$studentRole->name]);

        StudentProfile::create([
            'user_id' => $student->id,
            'parent_user_id' => $parent->id,
            'nisn' => '0057776666',
            'full_name' => 'Anak Test',
            'gender' => 'L',
            'birth_date' => '2008-05-15',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'parent',
            'identifier' => '0057776666',
            'birth_date' => '2008-05-15',
        ]);

        $this->assertAuthenticatedAs($parent);
        $response->assertRedirect(route('portal.parent', absolute: false));

        // Verifikasi audit trail tercatat
        $this->assertDatabaseHas('audit_trails', [
            'user_id' => $parent->id,
            'identifier_attempted' => '0057776666',
            'status' => 'success',
        ]);
    }

    public function test_failed_attempts_trigger_lockout_after_five_failures(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@sekolah.id',
            'password' => Hash::make('correct_password'),
            'is_active' => true,
        ]);

        RateLimiter::clear('victim@sekolah.id|127.0.0.1');

        // Lakukan 4 kali percobaan salah
        for ($i = 1; $i <= 4; $i++) {
            $this->post('/login', [
                'login_type' => 'staff',
                'identifier' => 'victim@sekolah.id',
                'password' => 'wrong_password',
            ]);
            $this->assertGuest();
        }

        $user->refresh();
        $this->assertFalse((bool) $user->is_locked);

        // Percobaan ke-5 yang gagal memicu lockout
        $this->post('/login', [
            'login_type' => 'staff',
            'identifier' => 'victim@sekolah.id',
            'password' => 'wrong_password',
        ]);

        $user->refresh();
        $this->assertTrue((bool) $user->is_locked);
        $this->assertNotNull($user->lockout_until);

        // Verifikasi audit trail mencatat status locked
        $this->assertDatabaseHas('audit_trails', [
            'user_id' => $user->id,
            'status' => 'locked',
        ]);

        // Coba login dengan password benar saat masih terkunci -> harus tetap ditolak
        $response = $this->post('/login', [
            'login_type' => 'staff',
            'identifier' => 'victim@sekolah.id',
            'password' => 'correct_password',
        ]);
        $response->assertSessionHasErrors(['identifier']);
        $this->assertGuest();

        // Uji buka kunci akun oleh Admin
        $user->unlockAccount();
        $user->refresh();
        $this->assertFalse((bool) $user->is_locked);
        $this->assertNull($user->lockout_until);
    }
}
