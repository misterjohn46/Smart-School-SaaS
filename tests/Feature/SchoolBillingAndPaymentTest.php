<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\StudentBill;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolBillingAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_batch_generate_bills_creates_invoices_without_duplicates(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $feeCategory = FeeCategory::where('code', 'SPP-X')->first();
        $classroom = Classroom::first();

        $this->assertNotNull($academicYear);
        $this->assertNotNull($feeCategory);
        $this->assertNotNull($classroom);

        $financeService = app(FinanceService::class);

        // Batch generate untuk bulan Oktober (bulan 10)
        $count = $financeService->generateBatchBills(
            $academicYear->id,
            $feeCategory->id,
            [$classroom->id],
            10,
            2026,
            350000
        );

        $this->assertGreaterThan(0, $count);

        // Jalankan lagi untuk bulan yang sama, pastikan tidak ada duplikasi
        $secondRun = $financeService->generateBatchBills(
            $academicYear->id,
            $feeCategory->id,
            [$classroom->id],
            10,
            2026,
            350000
        );

        $this->assertEquals(0, $secondRun);
    }

    public function test_process_cash_payment_settles_bill_and_creates_payment_record(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->first();
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $unpaidBill = StudentBill::where('student_profile_id', $student->id)
            ->where('status', 'unpaid')
            ->first();

        $this->assertNotNull($unpaidBill);

        $response = $this->actingAs($admin)->postJson('/finance/pay', [
            'student_profile_id' => $student->id,
            'payment_method' => 'cash',
            'allocations' => [
                [
                    'bill_id' => $unpaidBill->id,
                    'amount' => (float) $unpaidBill->amount,
                ],
            ],
            'total_amount' => (float) $unpaidBill->amount,
            'notes' => 'Pembayaran lunas via Kasir',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $unpaidBill->refresh();
        $this->assertEquals('paid', $unpaidBill->status);
        $this->assertEquals((float) $unpaidBill->amount, (float) $unpaidBill->paid_amount);

        // Cek bahwa kuitansi pembayaran resmi dapat diakses
        $payment = Payment::where('student_profile_id', $student->id)->latest()->first();
        $this->assertNotNull($payment);

        $receiptResponse = $this->actingAs($admin)->get('/finance/receipt/'.$payment->hash_id);
        $receiptResponse->assertStatus(200);
    }

    public function test_terbilang_rupiah_utility_accuracy(): void
    {
        $this->assertEquals('Tiga Ratus Lima Puluh Ribu Rupiah', FinanceService::terbilang(350000));
        $this->assertEquals('Satu Juta Lima Ratus Ribu Rupiah', FinanceService::terbilang(1500000));
        $this->assertEquals('Dua Puluh Lima Ribu Rupiah', FinanceService::terbilang(25000));
        $this->assertEquals('Seratus Ribu Rupiah', FinanceService::terbilang(100000));
    }

    public function test_manual_bank_transfer_verification_flow(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->first();
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $bill = StudentBill::where('student_profile_id', $student->id)->first();

        $financeService = app(FinanceService::class);

        // Buat pembayaran pending (simulasi upload bukti transfer oleh orang tua)
        $payment = $financeService->processPayment(
            $student->id,
            [['bill_id' => $bill->id, 'amount' => 100000]],
            'bank_transfer',
            100000,
            $student->user_id,
            'TRF-BCA-987654',
            'payment_proofs/slip.jpg',
            null,
            'Transfer via BCA Mobile'
        );

        $this->assertEquals('pending', $payment->status);

        // Verifikasi oleh Admin
        $verifiedPayment = $financeService->verifyBankTransfer($payment->id, true, $admin->id);
        $this->assertEquals('verified', $verifiedPayment->status);
        $this->assertNotNull($verifiedPayment->verified_at);
        $this->assertEquals($admin->id, $verifiedPayment->verified_by_user_id);
    }
}
