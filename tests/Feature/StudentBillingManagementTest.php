<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\FeeCategory;
use App\Models\GradeLevel;
use App\Models\StudentBill;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentBillingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Classroom $classroom;

    protected AcademicYear $academicYear;

    protected FeeCategory $feeCategory;

    protected StudentProfile $student1;

    protected StudentProfile $student2;

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

        $this->feeCategory = FeeCategory::create([
            'code' => 'SPP-REGULER',
            'name' => 'SPP Reguler Bulanan',
            'default_amount' => 250000,
            'is_recurring' => true,
            'is_active' => true,
        ]);

        $user1 = User::factory()->create(['name' => 'Budi Santoso']);
        $user1->assignRole('siswa');
        $this->student1 = StudentProfile::create([
            'user_id' => $user1->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '1001001',
            'full_name' => 'Budi Santoso',
            'gender' => 'L',
        ]);

        $user2 = User::factory()->create(['name' => 'Siti Rahma']);
        $user2->assignRole('siswa');
        $this->student2 = StudentProfile::create([
            'user_id' => $user2->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '1001002',
            'full_name' => 'Siti Rahma',
            'gender' => 'P',
        ]);
    }

    public function test_admin_can_view_bills_index(): void
    {
        StudentBill::create([
            'student_profile_id' => $this->student1->id,
            'fee_category_id' => $this->feeCategory->id,
            'academic_year_id' => $this->academicYear->id,
            'bill_number' => 'INV-202609-001',
            'title' => 'SPP Bulan September 2026',
            'month' => 9,
            'year' => 2026,
            'amount' => 250000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->get(route('finance.bills.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Finance/Bills/Index')
            ->has('bills.data', 1)
            ->where('bills.data.0.bill_number', 'INV-202609-001')
            ->has('stats')
            ->has('classrooms')
            ->has('feeCategories')
        );
    }

    public function test_admin_can_filter_bills_by_status_and_classroom(): void
    {
        StudentBill::create([
            'student_profile_id' => $this->student1->id,
            'fee_category_id' => $this->feeCategory->id,
            'academic_year_id' => $this->academicYear->id,
            'bill_number' => 'INV-202609-001',
            'title' => 'SPP September',
            'year' => 2026,
            'amount' => 250000,
            'paid_amount' => 250000,
            'status' => 'paid',
        ]);

        StudentBill::create([
            'student_profile_id' => $this->student2->id,
            'fee_category_id' => $this->feeCategory->id,
            'academic_year_id' => $this->academicYear->id,
            'bill_number' => 'INV-202609-002',
            'title' => 'SPP September',
            'year' => 2026,
            'amount' => 250000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->get(route('finance.bills.index', [
            'status' => 'unpaid',
            'classroom_id' => $this->classroom->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Finance/Bills/Index')
            ->has('bills.data', 1)
            ->where('bills.data.0.bill_number', 'INV-202609-002')
        );
    }

    public function test_admin_can_generate_batch_bills_for_classroom(): void
    {
        $response = $this->actingAs($this->admin)->post(route('finance.bills.batch'), [
            'fee_category_id' => $this->feeCategory->id,
            'academic_year_id' => $this->academicYear->id,
            'target_type' => 'classroom',
            'classroom_id' => $this->classroom->id,
            'title' => 'SPP Bulan Oktober 2026',
            'month' => 10,
            'year' => 2026,
            'amount' => 250000,
            'due_date' => '2026-10-10',
        ]);

        $response->assertSessionHas('status');

        // Harus membuat 2 tagihan untuk student1 dan student2
        $this->assertEquals(2, StudentBill::where('title', 'SPP Bulan Oktober 2026')->count());
        $this->assertDatabaseHas('student_bills', [
            'student_profile_id' => $this->student1->id,
            'month' => 10,
            'year' => 2026,
            'amount' => 250000,
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('student_bills', [
            'student_profile_id' => $this->student2->id,
            'month' => 10,
            'year' => 2026,
            'amount' => 250000,
            'status' => 'unpaid',
        ]);
    }

    public function test_batch_billing_skips_duplicate_bills(): void
    {
        // Tagihan sudah ada untuk student1
        StudentBill::create([
            'student_profile_id' => $this->student1->id,
            'fee_category_id' => $this->feeCategory->id,
            'academic_year_id' => $this->academicYear->id,
            'bill_number' => 'INV-EXISTING',
            'title' => 'SPP Bulan Oktober 2026',
            'month' => 10,
            'year' => 2026,
            'amount' => 250000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->post(route('finance.bills.batch'), [
            'fee_category_id' => $this->feeCategory->id,
            'academic_year_id' => $this->academicYear->id,
            'target_type' => 'classroom',
            'classroom_id' => $this->classroom->id,
            'title' => 'SPP Bulan Oktober 2026',
            'month' => 10,
            'year' => 2026,
            'amount' => 250000,
        ]);

        $response->assertSessionHas('status');

        // Total tetap 2 tagihan (1 yang lama + 1 untuk student2 baru dibuat), student1 tidak diduplikasi
        $this->assertEquals(2, StudentBill::where('month', 10)->where('year', 2026)->count());
    }

    public function test_admin_cannot_delete_bill_with_payment(): void
    {
        $bill = StudentBill::create([
            'student_profile_id' => $this->student1->id,
            'fee_category_id' => $this->feeCategory->id,
            'academic_year_id' => $this->academicYear->id,
            'bill_number' => 'INV-PAID-01',
            'title' => 'SPP Lunas',
            'year' => 2026,
            'amount' => 250000,
            'paid_amount' => 100000,
            'status' => 'partial',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('finance.bills.destroy', $bill->id));

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('student_bills', [
            'id' => $bill->id,
        ]);
    }

    public function test_admin_can_delete_unpaid_bill(): void
    {
        $bill = StudentBill::create([
            'student_profile_id' => $this->student1->id,
            'fee_category_id' => $this->feeCategory->id,
            'academic_year_id' => $this->academicYear->id,
            'bill_number' => 'INV-UNPAID-01',
            'title' => 'Tagihan Salah',
            'year' => 2026,
            'amount' => 250000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('finance.bills.destroy', $bill->id));

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('student_bills', [
            'id' => $bill->id,
        ]);
    }

    public function test_admin_can_manage_fee_categories(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)->get(route('finance.fee-categories.index'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Finance/FeeCategories/Index')
            ->has('categories')
        );

        // 2. Store
        $responseStore = $this->actingAs($this->admin)->post(route('finance.fee-categories.store'), [
            'code' => 'DSP-GEDUNG',
            'name' => 'Dana Sumbangan Pendidikan (DSP)',
            'default_amount' => 1500000,
            'is_recurring' => false,
            'is_active' => true,
        ]);
        $responseStore->assertSessionHas('status');
        $this->assertDatabaseHas('fee_categories', ['code' => 'DSP-GEDUNG']);

        $category = FeeCategory::where('code', 'DSP-GEDUNG')->first();

        // 3. Update
        $responseUpdate = $this->actingAs($this->admin)->put(route('finance.fee-categories.update', $category->id), [
            'code' => 'DSP-GEDUNG',
            'name' => 'DSP Uang Gedung 2026',
            'default_amount' => 1750000,
            'is_recurring' => false,
            'is_active' => true,
        ]);
        $responseUpdate->assertSessionHas('status');
        $this->assertDatabaseHas('fee_categories', ['name' => 'DSP Uang Gedung 2026', 'default_amount' => 1750000]);

        // 4. Delete
        $responseDelete = $this->actingAs($this->admin)->delete(route('finance.fee-categories.destroy', $category->id));
        $responseDelete->assertSessionHas('status');
        $this->assertDatabaseMissing('fee_categories', ['id' => $category->id]);
    }
}
