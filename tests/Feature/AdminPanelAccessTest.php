<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_central_tenants_and_plans(): void
    {
        $superAdmin = User::factory()->create();
        Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin);

        $responseTenants = $this->get(route('central.tenants.index'));
        $responseTenants->assertOk();

        $responsePlans = $this->get(route('central.plans.index'));
        $responsePlans->assertOk();
    }

    public function test_school_admin_cannot_access_central_tenants_module(): void
    {
        $admin = User::factory()->create();
        Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->get(route('central.tenants.index'));
        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_central_tenants_module(): void
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'guru']);
        $user->assignRole('guru');

        $this->actingAs($user);

        $response = $this->get(route('central.tenants.index'));
        $response->assertForbidden();
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create();
        Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole('admin');

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create();
        $otherUser = User::factory()->create();
        Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('delete', $otherUser));
    }
}
