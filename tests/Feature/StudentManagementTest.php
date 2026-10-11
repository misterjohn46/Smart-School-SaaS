<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Classroom $classroom;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'siswa']);

        $this->admin = User::factory()->create(['email' => 'admin@sekolah.id']);
        $this->admin->assignRole('admin');

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
    }

    public function test_admin_can_view_students_list_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academic.students.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Students/Index')
            ->has('students')
            ->has('classrooms')
            ->has('stats')
        );
    }

    public function test_admin_can_create_new_student_with_wallet_initialized(): void
    {
        $payload = [
            'full_name' => 'Budi Santoso',
            'email' => 'budi@siswa.sch.id',
            'nisn' => '0098765432',
            'nik' => '3513012345670001',
            'classroom_id' => $this->classroom->id,
            'gender' => 'L',
            'parent_name' => 'Santoso',
            'parent_phone_wa' => '081234567890',
            'address' => 'Jl. Merdeka No. 10',
            'password' => 'siswa123',
        ];

        $response = $this->actingAs($this->admin)->post(route('academic.students.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'email' => 'budi@siswa.sch.id',
            'name' => 'Budi Santoso',
        ]);

        $studentProfile = StudentProfile::where('nisn', '0098765432')->first();
        $this->assertNotNull($studentProfile);
        $this->assertEquals('Budi Santoso', $studentProfile->full_name);
        $this->assertEquals($this->classroom->id, $studentProfile->classroom_id);

        $wallet = StudentWallet::where('student_profile_id', $studentProfile->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_admin_can_update_student_profile(): void
    {
        $user = User::factory()->create(['email' => 'lama@siswa.sch.id']);
        $user->assignRole('siswa');

        $profile = StudentProfile::create([
            'user_id' => $user->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '0011223344',
            'full_name' => 'Nama Lama',
            'gender' => 'L',
        ]);

        $payload = [
            'full_name' => 'Nama Baru Diedit',
            'email' => 'baru@siswa.sch.id',
            'nisn' => '0011223344',
            'classroom_id' => $this->classroom->id,
            'gender' => 'L',
        ];

        $response = $this->actingAs($this->admin)->put(route('academic.students.update', $profile->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('student_profiles', [
            'id' => $profile->id,
            'full_name' => 'Nama Baru Diedit',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'baru@siswa.sch.id',
            'name' => 'Nama Baru Diedit',
        ]);
    }

    public function test_admin_can_delete_student(): void
    {
        $user = User::factory()->create(['email' => 'hapus@siswa.sch.id']);
        $user->assignRole('siswa');

        $profile = StudentProfile::create([
            'user_id' => $user->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '0055667788',
            'full_name' => 'Siswa Dihapus',
            'gender' => 'L',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.students.destroy', $profile->id));

        $response->assertRedirect();

        $this->assertDatabaseMissing('student_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_reset_student_password(): void
    {
        $user = User::factory()->create(['email' => 'reset@siswa.sch.id']);
        $user->assignRole('siswa');

        $profile = StudentProfile::create([
            'user_id' => $user->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '0099887766',
            'full_name' => 'Siswa Reset',
            'gender' => 'L',
        ]);

        $response = $this->actingAs($this->admin)->post(route('academic.students.reset-password', $profile->id));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $response->assertSessionHas('reset_credential');
    }
}
