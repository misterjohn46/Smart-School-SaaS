<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->create();
        Role::create(['name' => 'admin']);
        $admin->assignRole('admin');

        $this->assertTrue($admin->canAccessPanel(Panel::make()));
    }

    public function test_super_admin_can_access_the_admin_panel(): void
    {
        $superAdmin = User::factory()->create();
        Role::create(['name' => 'super_admin']);
        $superAdmin->assignRole('super_admin');

        $this->assertTrue($superAdmin->canAccessPanel(Panel::make()));
    }

    public function test_non_admin_role_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'user']);
        $user->assignRole('user');

        $this->assertFalse($user->canAccessPanel(Panel::make()));
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create();
        Role::create(['name' => 'admin']);
        $admin->assignRole('admin');

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create();
        $otherUser = User::factory()->create();
        Role::create(['name' => 'admin']);
        $admin->assignRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('delete', $otherUser));
    }
}
