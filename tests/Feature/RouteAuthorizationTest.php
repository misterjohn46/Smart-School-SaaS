<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Menjaga matriks peran per modul di routes/web.php.
 *
 * Sebelum perbaikan ini seluruh rute hanya memakai middleware `auth`, sehingga
 * siswa maupun orang tua dapat memanggil setiap aksi administratif lewat HTTP.
 */
class RouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function adminOnlyEndpoints(): array
    {
        return [
            'daftar siswa' => ['get', '/academic/students'],
            'tambah siswa' => ['post', '/academic/students'],
            'daftar guru' => ['get', '/academic/teachers'],
            'pengaturan presensi' => ['get', '/attendance/settings'],
            'tambah geofence' => ['post', '/attendance/geofences'],
            'jam kerja presensi' => ['post', '/attendance/settings/working-hours'],
            'cetak kts massal' => ['get', '/kts/batch-print'],
            'tahun ajaran' => ['get', '/academic/academic-years'],
            'jurusan' => ['get', '/academic/departments'],
            'mata pelajaran' => ['get', '/academic/subjects'],
            'jadwal pelajaran' => ['get', '/academic/schedules'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function financeEndpoints(): array
    {
        return [
            'pos kasir' => ['get', '/finance/pos-kasir'],
            'daftar tagihan' => ['get', '/finance/bills'],
            'generate tagihan massal' => ['post', '/finance/bills/batch'],
            'kategori biaya' => ['get', '/finance/fee-categories'],
            'bayar spp' => ['post', '/finance/pay'],
        ];
    }

    #[DataProvider('adminOnlyEndpoints')]
    public function test_student_cannot_reach_academic_admin_endpoints(string $method, string $uri): void
    {
        $this->actingAs($this->user('siswa@sekolah.id'))
            ->{$method}($uri)
            ->assertForbidden();
    }

    #[DataProvider('adminOnlyEndpoints')]
    public function test_parent_cannot_reach_academic_admin_endpoints(string $method, string $uri): void
    {
        $this->actingAs($this->user('ortu@sekolah.id'))
            ->{$method}($uri)
            ->assertForbidden();
    }

    #[DataProvider('financeEndpoints')]
    public function test_student_cannot_reach_finance_endpoints(string $method, string $uri): void
    {
        $this->actingAs($this->user('siswa@sekolah.id'))
            ->{$method}($uri)
            ->assertForbidden();
    }

    #[DataProvider('financeEndpoints')]
    public function test_teacher_cannot_reach_finance_endpoints(string $method, string $uri): void
    {
        $this->actingAs($this->user('guru@sekolah.id'))
            ->{$method}($uri)
            ->assertForbidden();
    }

    public function test_student_cannot_reset_another_account_password(): void
    {
        $profile = StudentProfile::firstOrFail();
        $originalHash = $profile->user->password;

        $this->actingAs($this->user('siswa@sekolah.id'))
            ->post("/academic/students/{$profile->id}/reset-password")
            ->assertForbidden();

        $this->assertSame($originalHash, $profile->user->fresh()->password);
    }

    public function test_student_cannot_topup_or_withdraw_wallet_balance(): void
    {
        $profile = StudentProfile::firstOrFail();
        $wallet = StudentWallet::where('student_profile_id', $profile->id)->firstOrFail();
        $balanceBefore = (float) $wallet->balance;

        $student = $this->user('siswa@sekolah.id');

        $this->actingAs($student)
            ->postJson('/wallet/topup', ['wallet_id' => $wallet->id, 'amount' => 1000000])
            ->assertForbidden();

        $this->actingAs($student)
            ->postJson('/wallet/withdraw', ['wallet_id' => $wallet->id, 'amount' => 50000, 'pin' => '123456'])
            ->assertForbidden();

        $this->assertSame($balanceBefore, (float) $wallet->fresh()->balance);
    }

    public function test_student_cannot_change_pin_of_another_student_wallet(): void
    {
        $otherProfile = StudentProfile::where('nisn', '!=', '0051234567')->firstOrFail();
        $otherWallet = app(WalletService::class)->getOrCreateWallet($otherProfile->id);

        $ownProfile = StudentProfile::where('nisn', '0051234567')->firstOrFail();
        $ownWallet = StudentWallet::where('student_profile_id', $ownProfile->id)->firstOrFail();

        // wallet_id milik siswa lain tetap dikirim, namun controller mengabaikannya
        // dan hanya mengubah PIN dompet milik pengguna yang login.
        $this->actingAs($ownProfile->user)
            ->postJson('/wallet/change-pin', [
                'wallet_id' => $otherWallet->id,
                'current_pin' => '123456',
                'new_pin' => '654321',
            ])
            ->assertOk();

        $this->assertTrue($ownWallet->fresh()->verifyPin('654321'));
        $this->assertTrue($otherWallet->fresh()->verifyPin('123456'));
    }

    public function test_student_cannot_preview_another_students_kts_card(): void
    {
        $ownProfile = StudentProfile::where('nisn', '0051234567')->firstOrFail();
        $otherProfile = StudentProfile::where('nisn', '!=', '0051234567')->firstOrFail();

        $this->actingAs($ownProfile->user)
            ->get("/kts/preview/{$otherProfile->hash_id}")
            ->assertForbidden();

        // Kartu sendiri tetap bisa dibuka
        $this->actingAs($ownProfile->user)
            ->get("/kts/preview/{$ownProfile->hash_id}")
            ->assertOk();
    }

    public function test_student_cannot_submit_grades_or_journals(): void
    {
        $student = $this->user('siswa@sekolah.id');

        $this->actingAs($student)->post('/academic/grades/batch')->assertForbidden();
        $this->actingAs($student)->get('/academic/journals')->assertForbidden();
        $this->actingAs($student)->get('/academic/cbt')->assertForbidden();
    }

    public function test_student_cannot_record_violations_or_open_canteen_pos(): void
    {
        $student = $this->user('siswa@sekolah.id');

        $this->actingAs($student)->get('/counseling/violations')->assertForbidden();
        $this->actingAs($student)->post('/counseling/violations')->assertForbidden();
        $this->actingAs($student)->get('/canteen/stand/1/pos')->assertForbidden();
    }

    public function test_student_keeps_access_to_self_service_modules(): void
    {
        $student = $this->user('siswa@sekolah.id');

        $this->actingAs($student)->get('/portal/siswa')->assertOk();
        $this->actingAs($student)->get('/wallet')->assertOk();
        $this->actingAs($student)->get('/canteen')->assertOk();
        $this->actingAs($student)->get('/attendance/leave')->assertOk();
    }

    public function test_treasurer_can_reach_finance_but_not_academic_master_data(): void
    {
        $treasurer = $this->user('bendahara@sekolah.id');

        $this->actingAs($treasurer)->get('/finance/bills')->assertOk();
        $this->actingAs($treasurer)->get('/finance/fee-categories')->assertOk();
        $this->actingAs($treasurer)->get('/academic/students')->assertForbidden();
        $this->actingAs($treasurer)->get('/attendance/settings')->assertForbidden();
    }

    public function test_teacher_can_reach_teaching_modules(): void
    {
        $teacher = $this->user('guru@sekolah.id');

        $this->actingAs($teacher)->get('/academic/grades')->assertOk();
        $this->actingAs($teacher)->get('/academic/journals')->assertOk();
        $this->actingAs($teacher)->get('/attendance/classroom')->assertOk();
        $this->actingAs($teacher)->get('/academic/students')->assertForbidden();
    }
}
