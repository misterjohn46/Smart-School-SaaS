<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\FinanceController;
use App\Models\Payment;
use App\Models\StudentBill;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bukti transfer bank memuat identitas dan data rekening, sehingga tidak boleh
 * tersimpan di disk publik maupun dapat dibuka pengguna lain.
 */
class PaymentProofSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    private function ownStudent(): StudentProfile
    {
        return StudentProfile::where('nisn', '0051234567')->firstOrFail();
    }

    private function billFor(StudentProfile $student): StudentBill
    {
        $bill = StudentBill::where('student_profile_id', $student->id)->first();

        if ($bill) {
            return $bill;
        }

        // Seeder hanya membuat tagihan untuk sebagian siswa.
        $reference = StudentBill::firstOrFail();

        return StudentBill::create([
            'student_profile_id' => $student->id,
            'fee_category_id' => $reference->fee_category_id,
            'academic_year_id' => $reference->academic_year_id,
            'bill_number' => 'BILL/TEST/'.$student->id,
            'title' => 'Tagihan Uji Bukti Transfer',
            'month' => (int) now()->format('n'),
            'year' => (int) now()->format('Y'),
            'amount' => 500000,
            'paid_amount' => 0,
            'due_date' => now()->addMonth(),
            'status' => 'unpaid',
        ]);
    }

    public function test_uploaded_proof_is_stored_on_the_private_disk_only(): void
    {
        Storage::fake(FinanceController::PROOF_DISK);
        Storage::fake('public');

        $student = $this->ownStudent();
        $bill = $this->billFor($student);

        $response = $this->actingAs($student->user)->post('/finance/upload-proof', [
            'student_profile_id' => $student->id,
            'bill_id' => $bill->id,
            'amount' => 250000,
            'reference_number' => 'TRF-BCA-112233',
            'proof_file' => UploadedFile::fake()->image('slip.jpg'),
        ]);

        $response->assertRedirect();

        $payment = Payment::where('reference_number', 'TRF-BCA-112233')->firstOrFail();

        $this->assertNotEmpty($payment->proof_document);
        Storage::disk(FinanceController::PROOF_DISK)->assertExists($payment->proof_document);
        Storage::disk('public')->assertMissing($payment->proof_document);

        // Unggahan bukti tidak langsung melunasi tagihan.
        $this->assertSame('pending', $payment->status);
    }

    public function test_student_cannot_upload_proof_for_another_students_bill(): void
    {
        Storage::fake(FinanceController::PROOF_DISK);

        $otherStudent = StudentProfile::where('nisn', '!=', '0051234567')->firstOrFail();
        $otherBill = $this->billFor($otherStudent);
        $ownStudent = $this->ownStudent();

        $this->actingAs($ownStudent->user)->post('/finance/upload-proof', [
            'student_profile_id' => $otherStudent->id,
            'bill_id' => $otherBill->id,
            'amount' => 250000,
            'reference_number' => 'TRF-CURANG-0001',
            'proof_file' => UploadedFile::fake()->image('slip.jpg'),
        ])->assertForbidden();

        $this->assertDatabaseMissing('payments', ['reference_number' => 'TRF-CURANG-0001']);
    }

    public function test_treasurer_may_upload_proof_on_behalf_of_any_student(): void
    {
        Storage::fake(FinanceController::PROOF_DISK);

        $treasurer = User::where('email', 'bendahara@sekolah.id')->firstOrFail();
        $student = $this->ownStudent();
        $bill = $this->billFor($student);

        $this->actingAs($treasurer)->post('/finance/upload-proof', [
            'student_profile_id' => $student->id,
            'bill_id' => $bill->id,
            'amount' => 250000,
            'reference_number' => 'TRF-KASIR-0001',
            'proof_file' => UploadedFile::fake()->image('slip.jpg'),
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'reference_number' => 'TRF-KASIR-0001',
            'status' => 'pending',
        ]);
    }

    public function test_proof_document_is_only_served_to_finance_staff_or_the_owner(): void
    {
        Storage::fake(FinanceController::PROOF_DISK);

        $student = $this->ownStudent();
        $bill = $this->billFor($student);

        $this->actingAs($student->user)->post('/finance/upload-proof', [
            'student_profile_id' => $student->id,
            'bill_id' => $bill->id,
            'amount' => 250000,
            'reference_number' => 'TRF-BCA-445566',
            'proof_file' => UploadedFile::fake()->image('slip.jpg'),
        ])->assertRedirect();

        $payment = Payment::where('reference_number', 'TRF-BCA-445566')->firstOrFail();
        $url = "/finance/proof/{$payment->hash_id}";

        // Pemilik tagihan: boleh
        $this->actingAs($student->user)->get($url)->assertOk();

        // Bendahara: boleh
        $this->actingAs(User::where('email', 'bendahara@sekolah.id')->firstOrFail())
            ->get($url)->assertOk();

        // Guru dan siswa lain: ditolak
        $this->actingAs(User::where('email', 'guru@sekolah.id')->firstOrFail())
            ->get($url)->assertForbidden();

        $otherStudent = StudentProfile::where('nisn', '!=', '0051234567')->firstOrFail();
        $this->actingAs($otherStudent->user)->get($url)->assertForbidden();
    }

    public function test_proof_document_is_not_reachable_without_authentication(): void
    {
        Storage::fake(FinanceController::PROOF_DISK);

        $student = $this->ownStudent();
        $bill = $this->billFor($student);

        $this->actingAs($student->user)->post('/finance/upload-proof', [
            'student_profile_id' => $student->id,
            'bill_id' => $bill->id,
            'amount' => 250000,
            'reference_number' => 'TRF-BCA-990011',
            'proof_file' => UploadedFile::fake()->image('slip.jpg'),
        ])->assertRedirect();

        $payment = Payment::where('reference_number', 'TRF-BCA-990011')->firstOrFail();
        $path = $payment->proof_document;

        // Berkas tidak berada di disk publik, jadi tidak ada URL /storage/... yang bisa dibuka.
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->get('/storage/'.$path)->assertNotFound();

        // Rute bergerbang menolak pengguna anonim.
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->get("/finance/proof/{$payment->hash_id}")->assertRedirect(route('login'));
    }

    public function test_verified_transfer_credits_the_bill_only_after_approval(): void
    {
        Storage::fake(FinanceController::PROOF_DISK);

        $student = $this->ownStudent();
        $bill = $this->billFor($student);
        $paidBefore = (float) $bill->paid_amount;

        $this->actingAs($student->user)->post('/finance/upload-proof', [
            'student_profile_id' => $student->id,
            'bill_id' => $bill->id,
            'amount' => 100000,
            'reference_number' => 'TRF-BCA-778899',
            'proof_file' => UploadedFile::fake()->image('slip.jpg'),
        ])->assertRedirect();

        $this->assertSame($paidBefore, (float) $bill->fresh()->paid_amount);

        $payment = Payment::where('reference_number', 'TRF-BCA-778899')->firstOrFail();
        $treasurer = User::where('email', 'bendahara@sekolah.id')->firstOrFail();

        app(FinanceService::class)->verifyBankTransfer($payment->id, true, $treasurer->id);

        $this->assertSame('verified', $payment->fresh()->status);
        $this->assertSame($paidBefore + 100000, (float) $bill->fresh()->paid_amount);
    }
}
