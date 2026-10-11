<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\AcademicYearResource;
use App\Filament\Resources\AttendanceDeviceResource;
use App\Filament\Resources\AttendanceLeaveResource;
use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\AuditTrailResource;
use App\Filament\Resources\CanteenMenuResource;
use App\Filament\Resources\CanteenStandResource;
use App\Filament\Resources\CbtNodeResource;
use App\Filament\Resources\ClassroomResource;
use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\FeeCategoryResource;
use App\Filament\Resources\KtsTemplateResource;
use App\Filament\Resources\LessonScheduleResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PlanResource;
use App\Filament\Resources\SchoolGeofenceResource;
use App\Filament\Resources\StudentBillResource;
use App\Filament\Resources\StudentWalletResource;
use App\Filament\Resources\SubjectResource;
use App\Filament\Resources\TenantResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\SaasPlatformOverviewWidget;
use App\Filament\Widgets\SchoolAdminOverviewWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentRoleSegregationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@platform.id',
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->schoolAdmin = User::factory()->create([
            'email' => 'admin@sekolah.id',
        ]);
        $this->schoolAdmin->assignRole('admin');
    }

    public function test_super_admin_can_only_access_saas_platform_and_system_resources(): void
    {
        $this->actingAs($this->superAdmin);

        // SaaS resources: Accessible
        $this->assertTrue(TenantResource::canAccess());
        $this->assertTrue(PlanResource::canAccess());
        $this->assertTrue(CbtNodeResource::canAccess());

        // Platform overview widget: Accessible
        $this->assertTrue(SaasPlatformOverviewWidget::canView());
        $this->assertFalse(SchoolAdminOverviewWidget::canView());

        // System resources: Accessible
        $this->assertTrue(UserResource::canAccess());
        $this->assertTrue(AuditTrailResource::canAccess());

        // School operational resources: MUST NOT BE ACCESSIBLE
        $this->assertFalse(AcademicYearResource::canAccess());
        $this->assertFalse(DepartmentResource::canAccess());
        $this->assertFalse(ClassroomResource::canAccess());
        $this->assertFalse(SubjectResource::canAccess());
        $this->assertFalse(LessonScheduleResource::canAccess());
        $this->assertFalse(AttendanceDeviceResource::canAccess());
        $this->assertFalse(AttendanceResource::canAccess());
        $this->assertFalse(AttendanceLeaveResource::canAccess());
        $this->assertFalse(SchoolGeofenceResource::canAccess());
        $this->assertFalse(KtsTemplateResource::canAccess());
        $this->assertFalse(FeeCategoryResource::canAccess());
        $this->assertFalse(StudentBillResource::canAccess());
        $this->assertFalse(PaymentResource::canAccess());
        $this->assertFalse(StudentWalletResource::canAccess());
        $this->assertFalse(CanteenStandResource::canAccess());
        $this->assertFalse(CanteenMenuResource::canAccess());
    }

    public function test_school_admin_can_only_access_school_operational_and_system_resources(): void
    {
        $this->actingAs($this->schoolAdmin);

        // SaaS resources: MUST NOT BE ACCESSIBLE
        $this->assertFalse(TenantResource::canAccess());
        $this->assertFalse(PlanResource::canAccess());
        $this->assertFalse(CbtNodeResource::canAccess());

        // Platform overview widget: Hidden, School overview: Visible
        $this->assertFalse(SaasPlatformOverviewWidget::canView());
        $this->assertTrue(SchoolAdminOverviewWidget::canView());

        // School operational resources: Accessible
        $this->assertTrue(AcademicYearResource::canAccess());
        $this->assertTrue(DepartmentResource::canAccess());
        $this->assertTrue(ClassroomResource::canAccess());
        $this->assertTrue(SubjectResource::canAccess());
        $this->assertTrue(LessonScheduleResource::canAccess());
        $this->assertTrue(AttendanceDeviceResource::canAccess());
        $this->assertTrue(AttendanceResource::canAccess());
        $this->assertTrue(AttendanceLeaveResource::canAccess());
        $this->assertTrue(SchoolGeofenceResource::canAccess());
        $this->assertTrue(KtsTemplateResource::canAccess());
        $this->assertTrue(FeeCategoryResource::canAccess());
        $this->assertTrue(StudentBillResource::canAccess());
        $this->assertTrue(PaymentResource::canAccess());
        $this->assertTrue(StudentWalletResource::canAccess());
        $this->assertTrue(CanteenStandResource::canAccess());
        $this->assertTrue(CanteenMenuResource::canAccess());

        // System resources: Accessible
        $this->assertTrue(UserResource::canAccess());
        $this->assertTrue(AuditTrailResource::canAccess());
    }

    public function test_tenant_creation_automatically_provisions_admin_user_and_domain(): void
    {
        $tenant = \App\Models\Tenant::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'school_name' => 'SMP Bintang Gemilang',
            'npsn' => '12345678',
            'admin_name' => 'Budi Santoso',
            'admin_email' => 'budi@gemilang.sch.id',
            'admin_phone' => '08123456789',
        ]);

        $adminUser = \App\Models\User::where('email', 'budi@gemilang.sch.id')->first();
        $this->assertNotNull($adminUser);
        $this->assertEquals('Budi Santoso', $adminUser->name);
        $this->assertTrue($adminUser->hasRole('admin'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $adminUser->password));

        $this->assertGreaterThan(0, $tenant->domains()->count());
    }

    public function test_school_admin_visiting_forbidden_saas_url_gets_forbidden_status(): void
    {
        $response = $this->actingAs($this->schoolAdmin)->get('/admin/tenants');

        $response->assertStatus(403);
    }
}
