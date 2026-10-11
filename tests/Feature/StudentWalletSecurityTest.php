<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Models\User;
use App\Services\WalletService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentWalletSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_wallet_topup_and_withdraw_records_ledger_accurately(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->first();
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $wallet = StudentWallet::where('student_profile_id', $student->id)->first();

        $initialBalance = (float) $wallet->balance;
        $walletService = app(WalletService::class);

        // Top up Rp 50.000
        $topupTrx = $walletService->topup($wallet->id, 50000, $admin->id, 'Setor Kasir');
        $this->assertEquals('topup', $topupTrx->type);
        $this->assertEquals($initialBalance + 50000, (float) $wallet->fresh()->balance);

        // Tarik Tunai Rp 20.000 dengan PIN yang benar
        $withdrawTrx = $walletService->withdraw($wallet->id, 20000, $admin->id, '123456', 'Tarik Tunai Kasir');
        $this->assertEquals('withdrawal', $withdrawTrx->type);
        $this->assertEquals($initialBalance + 30000, (float) $wallet->fresh()->balance);
    }

    public function test_withdrawal_with_invalid_pin_is_blocked(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->first();
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $wallet = StudentWallet::where('student_profile_id', $student->id)->first();

        $walletService = app(WalletService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('PIN Dompet 6-digit salah');

        $walletService->withdraw($wallet->id, 10000, $admin->id, '999999');
    }

    public function test_withdrawal_exceeding_balance_is_blocked(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->first();
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $wallet = StudentWallet::where('student_profile_id', $student->id)->first();

        $walletService = app(WalletService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Saldo tabungan tidak mencukupi');

        // Saldo awal 125.000, coba tarik 500.000
        $walletService->withdraw($wallet->id, 500000, $admin->id, '123456');
    }

    public function test_canteen_purchase_exceeding_daily_limit_is_rejected(): void
    {
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $wallet = StudentWallet::where('student_profile_id', $student->id)->first();

        // Saldo awal 125.000, daily limit 50.000
        $walletService = app(WalletService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Batas belanja harian terlampaui');

        // Coba jajan 60.000 (melebihi limit harian 50.000)
        $walletService->deductForCanteen($wallet->id, 60000, '123456', 'Jajan Kantin Mewah');
    }

    public function test_student_can_change_pin_via_api(): void
    {
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $wallet = StudentWallet::where('student_profile_id', $student->id)->first();

        $response = $this->actingAs($student->user)->postJson('/wallet/change-pin', [
            'wallet_id' => $wallet->id,
            'current_pin' => '123456',
            'new_pin' => '654321',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertTrue($wallet->fresh()->verifyPin('654321'));
        $this->assertFalse($wallet->fresh()->verifyPin('123456'));
    }
}
