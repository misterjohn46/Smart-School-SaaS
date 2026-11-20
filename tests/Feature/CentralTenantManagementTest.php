<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CentralTenantManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->schoolAdmin = User::factory()->create();
        $this->schoolAdmin->assignRole('admin');
    }

    public function test_super_admin_can_view_tenants_and_plans_in_spa(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get(route('central.tenants.index'))->assertOk();
        $this->get(route('central.plans.index'))->assertOk();
    }

    public function test_super_admin_can_create_new_tenant_with_domain_and_admin_user(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('central.tenants.store'), [
            'school_name' => 'SMK Bina Mandiri',
            'npsn' => '20109988',
            'subdomain' => 'smkbinamandiri',
            'admin_name' => 'Drs. Hendro Wibowo',
            'admin_email' => 'hendro@smkbinamandiri.sch.id',
            'admin_phone' => '081299988877',
            'trial_days' => 30,
        ]);

        $response->assertRedirect(route('central.tenants.index'));
        $response->assertSessionHas('credentials');

        $tenant = Tenant::where('npsn', '20109988')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('SMK Bina Mandiri', $tenant->school_name);
        $this->assertTrue($tenant->domains()->exists());

        // Pastikan akun admin sekolah otomatis terbuat dengan role admin
        $adminUser = User::withoutTenancy()
            ->where('tenant_id', $tenant->getTenantKey())
            ->where('email', 'hendro@smkbinamandiri.sch.id')
            ->first();

        $this->assertNotNull($adminUser);
        $this->assertTrue($adminUser->hasRole('admin'));
    }

    public function test_super_admin_can_update_and_delete_tenant(): void
    {
        $this->actingAs($this->superAdmin);

        $tenant = Tenant::create([
            'school_name' => 'SMA Test',
            'npsn' => '11223344',
            'admin_name' => 'Admin Test',
            'admin_email' => 'adm@test.id',
        ]);

        $this->put(route('central.tenants.update', $tenant->id), [
            'school_name' => 'SMA Test Updated',
            'admin_name' => 'Admin Updated',
            'admin_phone' => '081234567890',
            'trial_ends_at' => now()->addDays(60)->toDateString(),
        ])->assertRedirect(route('central.tenants.index'));

        $this->assertEquals('SMA Test Updated', $tenant->fresh()->school_name);

        $this->delete(route('central.tenants.destroy', $tenant->id))
            ->assertRedirect(route('central.tenants.index'));

        $this->assertNull(Tenant::find($tenant->id));
    }

    public function test_super_admin_can_sync_and_reset_tenant_admin_password(): void
    {
        $this->actingAs($this->superAdmin);

        $tenant = Tenant::create([
            'school_name' => 'SMA Nusantara',
            'npsn' => '99887766',
            'admin_name' => 'Siti Aminah',
            'admin_email' => 'siti@nusantara.sch.id',
        ]);

        $response = $this->post(route('central.tenants.sync-admin', $tenant->id));
        $response->assertRedirect(route('central.tenants.index'));
        $response->assertSessionHas('credentials');

        $credentials = session('credentials');
        $this->assertEquals('siti@nusantara.sch.id', $credentials['admin_email']);
        $this->assertNotEmpty($credentials['admin_password']);
    }

    public function test_super_admin_can_manage_saas_plans(): void
    {
        $this->actingAs($this->superAdmin);

        // Create
        $response = $this->post(route('central.plans.store'), [
            'name' => 'Enterprise Campus',
            'slug' => 'enterprise-campus',
            'price_monthly' => 1500000,
            'price_yearly' => 15000000,
            'max_students' => 2500,
            'max_teachers' => 150,
            'max_storage_mb' => 20480,
            'is_active' => true,
            'is_popular' => true,
            'description' => 'Paket skala besar.',
        ]);

        $response->assertRedirect(route('central.plans.index'));

        $plan = Plan::where('slug', 'enterprise-campus')->first();
        $this->assertNotNull($plan);
        $this->assertEquals(1500000, $plan->price_monthly);

        // Update
        $this->put(route('central.plans.update', $plan->id), [
            'name' => 'Enterprise Campus Plus',
            'slug' => 'enterprise-campus',
            'price_monthly' => 1800000,
            'price_yearly' => 18000000,
            'max_students' => 3000,
            'max_teachers' => 200,
            'max_storage_mb' => 40960,
            'is_active' => true,
            'is_popular' => false,
            'description' => 'Paket ultra skala besar.',
        ])->assertRedirect(route('central.plans.index'));

        $this->assertEquals(1800000, $plan->fresh()->price_monthly);

        // Delete
        $this->delete(route('central.plans.destroy', $plan->id))
            ->assertRedirect(route('central.plans.index'));

        $this->assertNull(Plan::find($plan->id));
    }

    public function test_non_super_admin_cannot_manage_tenants_or_plans(): void
    {
        $this->actingAs($this->schoolAdmin);

        $this->get(route('central.tenants.index'))->assertForbidden();
        $this->get(route('central.plans.index'))->assertForbidden();
        $this->post(route('central.tenants.store'), [])->assertForbidden();
        $this->post(route('central.plans.store'), [])->assertForbidden();
    }
}
