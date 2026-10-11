<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);

        $this->admin = User::factory()->create(['email' => 'admin@sekolah.id']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_teachers_list_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academic.teachers.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Teachers/Index')
            ->has('teachers')
            ->has('stats')
        );
    }

    public function test_admin_can_create_new_teacher(): void
    {
        $payload = [
            'full_name' => 'Dewi Sartika, S.Pd',
            'email' => 'dewi@guru.sch.id',
            'nip' => '198801012015012001',
            'nuptk' => '1234567890123456',
            'gender' => 'P',
            'phone_wa' => '081298765432',
            'address' => 'Jl. Kartini No. 5',
            'password' => 'guru123',
        ];

        $response = $this->actingAs($this->admin)->post(route('academic.teachers.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'email' => 'dewi@guru.sch.id',
            'name' => 'Dewi Sartika, S.Pd',
        ]);

        $this->assertDatabaseHas('teacher_profiles', [
            'nip' => '198801012015012001',
            'full_name' => 'Dewi Sartika, S.Pd',
            'gender' => 'P',
        ]);
    }

    public function test_admin_can_update_teacher_profile(): void
    {
        $user = User::factory()->create(['email' => 'guru_lama@sekolah.id']);
        $user->assignRole('guru');

        $profile = TeacherProfile::create([
            'user_id' => $user->id,
            'nip' => '198001012005011001',
            'full_name' => 'Guru Lama, M.Pd',
            'gender' => 'L',
        ]);

        $payload = [
            'full_name' => 'Guru Baru, Dr. M.Pd',
            'email' => 'guru_baru@sekolah.id',
            'nip' => '198001012005011001',
            'gender' => 'L',
        ];

        $response = $this->actingAs($this->admin)->put(route('academic.teachers.update', $profile->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('teacher_profiles', [
            'id' => $profile->id,
            'full_name' => 'Guru Baru, Dr. M.Pd',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'guru_baru@sekolah.id',
        ]);
    }

    public function test_admin_can_delete_teacher(): void
    {
        $user = User::factory()->create(['email' => 'guru_hapus@sekolah.id']);
        $user->assignRole('guru');

        $profile = TeacherProfile::create([
            'user_id' => $user->id,
            'full_name' => 'Guru Dihapus',
            'gender' => 'L',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic.teachers.destroy', $profile->id));

        $response->assertRedirect();

        $this->assertDatabaseMissing('teacher_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_reset_teacher_password(): void
    {
        $user = User::factory()->create(['email' => 'guru_reset@sekolah.id']);
        $user->assignRole('guru');

        $profile = TeacherProfile::create([
            'user_id' => $user->id,
            'full_name' => 'Guru Reset',
            'gender' => 'L',
        ]);

        $response = $this->actingAs($this->admin)->post(route('academic.teachers.reset-password', $profile->id));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $response->assertSessionHas('reset_credential');
    }
}
