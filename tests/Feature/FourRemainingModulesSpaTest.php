<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\AttendanceLeave;
use App\Models\CanteenMenu;
use App\Models\CanteenStand;
use App\Models\Classroom;
use App\Models\LearningMaterial;
use App\Models\Payment;
use App\Models\SchoolAnnouncement;
use App\Models\StudentBill;
use App\Models\StudentGrade;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FourRemainingModulesSpaTest extends TestCase
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

    public function test_eleave_approval_syncs_attendance_records_and_enforces_roles(): void
    {
        $student = $this->user('siswa@sekolah.id');
        $teacher = $this->user('guru@sekolah.id');

        // 1. Siswa mengajukan izin sakit selama 2 hari
        $startDate = now()->toDateString();
        $endDate = now()->addDay()->toDateString();

        $this->actingAs($student)
            ->post('/attendance/leave', [
                'type' => 'sakit',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => 'Demam tinggi dan istirahat medis',
            ])
            ->assertRedirect();

        $leave = AttendanceLeave::where('user_id', $student->id)->latest('id')->firstOrFail();
        $this->assertSame('pending', $leave->status);

        // 2. Siswa tidak boleh menyetujui izin sendiri
        $this->actingAs($student)
            ->post("/attendance/leave/{$leave->id}/review", ['status' => 'approved'])
            ->assertForbidden();

        // 3. Guru membuka halaman verifikasi E-Leave dan menyetujui permohonan
        $this->actingAs($teacher)
            ->get('/attendance/leave')
            ->assertOk();

        $this->actingAs($teacher)
            ->post("/attendance/leave/{$leave->id}/review", ['status' => 'approved'])
            ->assertRedirect();

        $leave->refresh();
        $this->assertSame('approved', $leave->status);
        $this->assertSame($teacher->id, $leave->approved_by);

        // 4. Pastikan rekaman presensi otomatis terbentuk untuk kedua tanggal tersebut
        $this->assertSame(
            2,
            Attendance::where('user_id', $student->id)
                ->where('status', 'sakit')
                ->count()
        );
    }

    public function test_canteen_stand_and_menu_crud_in_spa(): void
    {
        $admin = $this->user('admin@sekolah.id');
        $student = $this->user('siswa@sekolah.id');

        // Siswa ditolak mengakses manajemen stand/menu
        $this->actingAs($student)
            ->post('/canteen/stands', [
                'name' => 'Stand Ilegal',
                'stand_number' => 'ST-99',
            ])
            ->assertForbidden();

        // Admin menambahkan stand baru
        $this->actingAs($admin)
            ->post('/canteen/stands', [
                'name' => 'Kantin Nusantara Sehat',
                'stand_number' => 'ST-08',
                'phone' => '081299998888',
                'description' => 'Aneka makanan sehat bergizi',
                'is_active' => true,
            ])
            ->assertRedirect();

        $stand = CanteenStand::where('stand_number', 'ST-08')->firstOrFail();
        $this->assertSame('Kantin Nusantara Sehat', $stand->name);

        // Admin menambahkan menu ke stand tersebut
        $this->actingAs($admin)
            ->post('/canteen/menus', [
                'canteen_stand_id' => $stand->id,
                'name' => 'Soto Ayam Kampung',
                'category' => 'food',
                'price' => 15000,
                'cost_price' => 10000,
                'stock' => 40,
                'is_available' => true,
                'description' => 'Soto ayam segar tanpa MSG',
            ])
            ->assertRedirect();

        $menu = CanteenMenu::where('canteen_stand_id', $stand->id)->where('name', 'Soto Ayam Kampung')->firstOrFail();
        $this->assertSame(40, $menu->stock);

        // Admin memperbarui stok & harga menu
        $this->actingAs($admin)
            ->put("/canteen/menus/{$menu->id}", [
                'canteen_stand_id' => $stand->id,
                'name' => 'Soto Ayam Kampung Spesial',
                'category' => 'food',
                'price' => 16000,
                'cost_price' => 10000,
                'stock' => 25,
                'is_available' => true,
                'description' => 'Diperbarui',
            ])
            ->assertRedirect();

        $this->assertSame(25, $menu->fresh()->stock);
        $this->assertSame('Soto Ayam Kampung Spesial', $menu->fresh()->name);

        // Hapus menu dan stand yang belum memiliki transaksi
        $this->actingAs($admin)->delete("/canteen/menus/{$menu->id}")->assertRedirect();
        $this->actingAs($admin)->delete("/canteen/stands/{$stand->id}")->assertRedirect();

        $this->assertDatabaseMissing('canteen_menus', ['id' => $menu->id]);
        $this->assertDatabaseMissing('canteen_stands', ['id' => $stand->id]);
    }

    public function test_pending_bank_transfer_verification_in_spa_updates_bill_status(): void
    {
        Storage::fake('secure');

        $student = $this->user('siswa@sekolah.id');
        $treasurer = $this->user('bendahara@sekolah.id');
        $studentProfile = StudentProfile::where('user_id', $student->id)->firstOrFail();
        $bill = StudentBill::where('student_profile_id', $studentProfile->id)
            ->where('status', 'unpaid')
            ->firstOrFail();

        $amountToPay = (float) $bill->amount;

        // 1. Siswa mengunggah bukti transfer bank
        $this->actingAs($student)
            ->post('/finance/upload-proof', [
                'student_profile_id' => $studentProfile->id,
                'bill_id' => $bill->id,
                'amount' => $amountToPay,
                'reference_number' => 'TRF-BNI-998877',
                'proof_file' => UploadedFile::fake()->image('bukti-transfer.jpg'),
                'notes' => 'Transfer SPP via BNI Mobile',
            ])
            ->assertRedirect();

        $payment = Payment::where('reference_number', 'TRF-BNI-998877')->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('unpaid', $bill->fresh()->status);

        // 2. Siswa tidak boleh memverifikasi pembayaran sendiri
        $this->actingAs($student)
            ->post("/finance/payments/{$payment->id}/verify", ['approved' => true])
            ->assertForbidden();

        // 3. Bendahara memverifikasi bukti transfer di SPA
        $this->actingAs($treasurer)
            ->post("/finance/payments/{$payment->id}/verify", ['approved' => true])
            ->assertRedirect();

        $this->assertSame('verified', $payment->fresh()->status);
        $this->assertSame('paid', $bill->fresh()->status);
        $this->assertSame($amountToPay, (float) $bill->fresh()->paid_amount);
    }

    public function test_elearning_materials_assignments_submission_grading_and_announcements(): void
    {
        $teacher = $this->user('guru@sekolah.id');
        $student = $this->user('siswa@sekolah.id');
        $studentProfile = StudentProfile::where('user_id', $student->id)->firstOrFail();
        $classroom = Classroom::findOrFail($studentProfile->classroom_id);
        $subject = Subject::firstOrFail();
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        // 1. Guru menerbitkan Tugas Harian Kelas
        $this->actingAs($teacher)
            ->post('/academic/learning/materials', [
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'type' => 'assignment',
                'title' => 'Praktikum Membuat REST API Laravel',
                'description' => 'Kumpulkan link repository dan ringkasan endpoint.',
                'reference_url' => 'https://laravel.com/docs',
                'due_date' => now()->addDays(3)->toDateTimeString(),
                'max_score' => 100,
                'is_published' => true,
            ])
            ->assertRedirect();

        $assignment = LearningMaterial::where('title', 'Praktikum Membuat REST API Laravel')->firstOrFail();

        // 2. Guru menerbitkan Pengumuman Sekolah
        $this->actingAs($teacher)
            ->post('/academic/learning/announcements', [
                'title' => 'Pengumpulan Proyek Akhir Semester',
                'content' => 'Harap seluruh siswa kelas X-XII mengumpulkan proyek sebelum tanggal 25.',
                'category' => 'akademik',
                'target_audience' => 'semua',
                'is_pinned' => true,
                'is_published' => true,
            ])
            ->assertRedirect();

        $this->assertSame(1, SchoolAnnouncement::where('title', 'Pengumpulan Proyek Akhir Semester')->count());

        // 3. Siswa membuka halaman E-Learning dan mengumpulkan jawaban tugas
        $this->actingAs($student)
            ->get('/academic/learning')
            ->assertOk();

        $this->actingAs($student)
            ->post("/academic/learning/materials/{$assignment->id}/submit", [
                'answer_text' => 'Seluruh endpoint CRUD telah selesai diuji dengan PHPUnit.',
                'attachment_url' => 'https://github.com/siswa/rest-api-tugas',
            ])
            ->assertRedirect();

        $submission = AssignmentSubmission::where('learning_material_id', $assignment->id)
            ->where('student_profile_id', $studentProfile->id)
            ->firstOrFail();

        // 4. Guru menilai jawaban siswa dan menyinkronkan ke Buku Nilai (Gradebook)
        $this->actingAs($teacher)
            ->post("/academic/learning/submissions/{$submission->id}/grade", [
                'score' => 94,
                'teacher_feedback' => 'Struktur kode sangat rapi dan teruji.',
                'sync_to_gradebook' => true,
            ])
            ->assertRedirect();

        $this->assertSame(94.0, (float) $submission->fresh()->score);

        // 5. Pastikan nilai tugas (assignment_score) di StudentGrade tersinkronisasi
        $grade = StudentGrade::where('academic_year_id', $activeYear->id)
            ->where('classroom_id', $classroom->id)
            ->where('subject_id', $subject->id)
            ->where('student_profile_id', $studentProfile->id)
            ->firstOrFail();

        $this->assertSame(94.0, (float) $grade->assignment_score);
    }
}
