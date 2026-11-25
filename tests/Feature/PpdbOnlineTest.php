<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\PpdbBatch;
use App\Models\PpdbRegistration;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PpdbOnlineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected AcademicYear $academicYear;

    protected Department $department;

    protected Classroom $classroom;

    protected PpdbBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');

        $this->admin = User::where('email', 'admin@sekolah.id')->firstOrFail();
        $this->teacher = User::where('email', 'guru@sekolah.id')->firstOrFail();

        AcademicYear::query()->update(['is_active' => false]);
        $this->academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'semester' => 'ganjil',
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
                'is_active' => true,
            ]
        );
        $this->academicYear->update(['is_active' => true]);

        $this->department = Department::firstOrFail();
        $this->classroom = Classroom::firstOrFail();

        // Gelombang Pendaftaran Aktif
        $this->batch = PpdbBatch::create([
            'tenant_id' => $this->admin->tenant_id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Gelombang 1 - Jalur Prestasi & Zonasi',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'announcement_date' => now()->addDays(25)->toDateString(),
            'registration_fee' => 0,
            'quota' => 150,
            'is_active' => true,
            'description' => 'Pendaftaran murid baru tahun ajaran 2026/2027.',
        ]);
    }

    public function test_public_can_view_ppdb_landing_page(): void
    {
        $response = $this->get('/ppdb');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Ppdb/Public/Index')
            ->has('batches')
            ->has('departments')
        );
    }

    public function test_public_can_view_registration_form(): void
    {
        $response = $this->get('/ppdb/daftar');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Ppdb/Public/Register')
            ->has('batches')
            ->has('departments')
        );
    }

    public function test_prospective_student_can_register_online_multi_step(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('pas_foto.jpg', 300, 400);

        $payload = [
            'ppdb_batch_id' => $this->batch->id,
            'track' => 'prestasi',
            'full_name' => 'Ahmad Fadhil Prasetyo',
            'gender' => 'L',
            'nisn' => '0091234567',
            'nik' => '3201012345670002',
            'birth_place' => 'Bandung',
            'birth_date' => '2010-05-14',
            'religion' => 'Islam',
            'previous_school' => 'SMP Negeri 3 Bandung',
            'phone_number' => '081234567890',
            'email' => 'ahmad.fadhil@example.com',
            'address' => 'Jl. Merdeka No. 45, Bandung',
            'department_id' => $this->department->id,
            'average_report_score' => 89.5,
            'achievements' => 'Juara 1 OSN Matematika Kota Bandung 2025',
            'parent_name' => 'Bambang Prasetyo',
            'parent_phone' => '081398765432',
            'parent_job' => 'Wiraswasta',
            'parent_income' => 'Rp 5.000.000 - Rp 10.000.000',
            'doc_photo' => $photo,
        ];

        $response = $this->post('/ppdb/daftar', $payload);

        $this->assertDatabaseHas('ppdb_registrations', [
            'full_name' => 'Ahmad Fadhil Prasetyo',
            'nisn' => '0091234567',
            'track' => 'prestasi',
            'status' => 'pending',
        ]);

        $registration = PpdbRegistration::where('nisn', '0091234567')->firstOrFail();

        $response->assertRedirect("/ppdb/bukti/{$registration->registration_number}");
        $this->assertNotEmpty($registration->access_token);
        $this->assertNotNull($registration->documents['photo']);
    }

    public function test_public_can_view_proof_card(): void
    {
        $registration = PpdbRegistration::create([
            'tenant_id' => $this->admin->tenant_id,
            'ppdb_batch_id' => $this->batch->id,
            'department_id' => $this->department->id,
            'registration_number' => 'PPDB-2026-0099',
            'access_token' => '123456',
            'track' => 'reguler',
            'full_name' => 'Siti Aisyah',
            'gender' => 'P',
            'birth_place' => 'Jakarta',
            'birth_date' => '2010-08-20',
            'previous_school' => 'SMP IT Al-Azhar',
            'phone_number' => '081298765432',
            'address' => 'Jl. Sudirman No. 12',
            'parent_name' => 'Hendro',
            'parent_phone' => '081298765432',
            'status' => 'pending',
        ]);

        $response = $this->get("/ppdb/bukti/{$registration->registration_number}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Ppdb/Public/Proof')
            ->has('registration')
        );
    }

    public function test_public_can_check_selection_status(): void
    {
        $registration = PpdbRegistration::create([
            'tenant_id' => $this->admin->tenant_id,
            'ppdb_batch_id' => $this->batch->id,
            'department_id' => $this->department->id,
            'registration_number' => 'PPDB-2026-0077',
            'access_token' => '654321',
            'track' => 'zonasi',
            'full_name' => 'Dewi Lestari',
            'gender' => 'P',
            'birth_place' => 'Surabaya',
            'birth_date' => '2010-03-12',
            'previous_school' => 'SMPN 1 Surabaya',
            'phone_number' => '081122334455',
            'address' => 'Jl. Pemuda No. 10',
            'parent_name' => 'Suharto',
            'parent_phone' => '081122334455',
            'status' => 'accepted',
        ]);

        $response = $this->get("/ppdb/cek-status?registration_number={$registration->registration_number}&birth_date=2010-03-12");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Ppdb/Public/CheckStatus')
            ->where('searched', true)
            ->where('result.full_name', 'Dewi Lestari')
            ->where('result.status', 'accepted')
        );
    }

    public function test_admin_can_view_ppdb_dashboard_and_manage_batches(): void
    {
        $response = $this->actingAs($this->admin)->get('/academic/ppdb');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Ppdb/Admin/Index')
            ->has('registrations')
            ->has('batches')
            ->has('stats')
        );

        // Tambah Gelombang Baru
        $batchResponse = $this->actingAs($this->admin)->post('/academic/ppdb/batches', [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Gelombang 2 - Reguler',
            'start_date' => now()->addDays(30)->toDateString(),
            'end_date' => now()->addDays(50)->toDateString(),
            'quota' => 100,
            'registration_fee' => 50000,
            'is_active' => true,
        ]);

        $batchResponse->assertRedirect();
        $this->assertDatabaseHas('ppdb_batches', [
            'name' => 'Gelombang 2 - Reguler',
            'quota' => 100,
        ]);
    }

    public function test_admin_can_verify_and_decide_selection(): void
    {
        $registration = PpdbRegistration::create([
            'tenant_id' => $this->admin->tenant_id,
            'ppdb_batch_id' => $this->batch->id,
            'department_id' => $this->department->id,
            'registration_number' => 'PPDB-2026-0012',
            'access_token' => '112233',
            'track' => 'prestasi',
            'full_name' => 'Farhan Hakim',
            'gender' => 'L',
            'birth_place' => 'Semarang',
            'birth_date' => '2010-09-09',
            'previous_school' => 'SMPN 2 Semarang',
            'phone_number' => '08155667788',
            'address' => 'Jl. Pahlawan',
            'parent_name' => 'Hakim',
            'parent_phone' => '08155667788',
            'status' => 'pending',
        ]);

        // 1. Verifikasi Berkas -> Valid
        $verifyResponse = $this->actingAs($this->admin)
            ->post("/academic/ppdb/registrations/{$registration->id}/verify", [
                'status' => 'verified',
                'verification_notes' => 'Dokumen lengkap dan absah.',
            ]);

        $verifyResponse->assertRedirect();
        $this->assertEquals('verified', $registration->fresh()->status);
        $this->assertEquals($this->admin->id, $registration->fresh()->verified_by);

        // 2. Putusan Seleksi -> Diterima
        $selectionResponse = $this->actingAs($this->admin)
            ->post("/academic/ppdb/registrations/{$registration->id}/selection", [
                'status' => 'accepted',
            ]);

        $selectionResponse->assertRedirect();
        $this->assertEquals('accepted', $registration->fresh()->status);
    }

    public function test_admin_can_auto_enroll_accepted_student(): void
    {
        $registration = PpdbRegistration::create([
            'tenant_id' => $this->admin->tenant_id,
            'ppdb_batch_id' => $this->batch->id,
            'department_id' => $this->department->id,
            'registration_number' => 'PPDB-2026-0050',
            'access_token' => '998877',
            'track' => 'reguler',
            'full_name' => 'Rian Hidayat',
            'gender' => 'L',
            'nisn' => '0095556667',
            'nik' => '3201009988770001',
            'birth_place' => 'Bogor',
            'birth_date' => '2010-04-10',
            'previous_school' => 'SMPN 1 Bogor',
            'phone_number' => '081999888777',
            'email' => 'rian.hidayat@example.com',
            'address' => 'Jl. Pajajaran No. 8',
            'parent_name' => 'Hidayat',
            'parent_phone' => '081999888777',
            'status' => 'accepted',
        ]);

        $enrollResponse = $this->actingAs($this->admin)
            ->post("/academic/ppdb/registrations/{$registration->id}/enroll", [
                'classroom_id' => $this->classroom->id,
                'password' => 'password123',
            ]);

        $enrollResponse->assertRedirect();
        $enrollResponse->assertSessionHas('enrolled_credential');

        $registration->refresh();
        $this->assertEquals('enrolled', $registration->status);
        $this->assertNotNull($registration->student_profile_id);

        // Pastikan User login siswa baru terbuat
        $this->assertDatabaseHas('users', [
            'name' => 'Rian Hidayat',
            'email' => 'rian.hidayat@example.com',
        ]);

        $user = User::where('email', 'rian.hidayat@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('siswa'));

        // Pastikan StudentProfile terbuat
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $user->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '0095556667',
            'full_name' => 'Rian Hidayat',
        ]);

        // Pastikan Dompet Tabungan Digital terbuat
        $profile = StudentProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertDatabaseHas('student_wallets', [
            'student_profile_id' => $profile->id,
            'account_number' => 'WAL-0095556667',
        ]);
    }

    public function test_admin_can_export_registrations_csv(): void
    {
        $exportResponse = $this->actingAs($this->admin)
            ->get('/academic/ppdb/export');

        $exportResponse->assertOk();
        $this->assertInstanceOf(StreamedResponse::class, $exportResponse->baseResponse);
    }
}
