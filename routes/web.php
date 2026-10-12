<?php

use App\Http\Controllers\AcademicReportController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\CanteenController;
use App\Http\Controllers\CbtController;
use App\Http\Controllers\Central\SchoolRegistrationController;
use App\Http\Controllers\ClassroomAttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\KtsController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentViolationController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeachingJournalController;
use App\Http\Controllers\WalletController;
use App\Models\Plan;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Matriks Peran per Modul
|--------------------------------------------------------------------------
|
| Setiap grup rute di bawah dijaga middleware `role:` (spatie/laravel-permission).
| Tanpa ini, setiap pengguna terautentikasi - termasuk siswa dan orang tua -
| dapat memanggil seluruh aksi administratif lewat HTTP request langsung.
|
*/
$roleAcademicAdmin = 'role:admin|kepala_sekolah|operator';
$roleAcademicStaff = 'role:admin|kepala_sekolah|operator|guru';
$roleTeaching = 'role:admin|kepala_sekolah|guru';
$roleFinance = 'role:admin|kepala_sekolah|bendahara';
$roleCashier = 'role:admin|bendahara';
$roleCounseling = 'role:admin|kepala_sekolah|guru_bk';
$roleCounselingInput = 'role:admin|kepala_sekolah|guru_bk|guru';
$roleCanteenVendor = 'role:admin|kantin|vendor_kantin|kasir_kantin';

foreach (config('tenancy.central_domains', ['localhost', '127.0.0.1']) as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return Inertia::render('LandingPage', [
                'canLogin' => Route::has('login'),
                'canRegister' => Route::has('register'),
                'plans' => Plan::where('is_active', true)->get(),
                'laravelVersion' => Application::VERSION,
                'phpVersion' => PHP_VERSION,
            ]);
        });

        Route::post('/register-school', [SchoolRegistrationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('school.register');
    });
}

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
| Portal & layanan mandiri (self-service).
| Controller sudah membatasi data ke pengguna yang login, jadi tidak perlu gate peran.
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/portal/siswa', [PortalController::class, 'student'])->name('portal.student');
    Route::get('/portal/orangtua', [PortalController::class, 'parent'])->name('portal.parent');

    // Presensi mandiri GPS Geofencing & AI Face (mencatat untuk diri sendiri)
    Route::get('/attendance/gps-face', [AttendanceController::class, 'gpsFace'])->name('attendance.gps.index');
    Route::post('/attendance/gps-face', [AttendanceController::class, 'storeGps'])->name('attendance.gps.store');

    // Pengajuan Izin / Sakit (E-Leave) milik sendiri
    Route::get('/attendance/leave', [AttendanceController::class, 'leaveIndex'])->name('attendance.leave.index');
    Route::post('/attendance/leave', [AttendanceController::class, 'storeLeave'])->name('attendance.leave.store');

    // Rekening tabungan sendiri & PIN sendiri
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/change-pin', [WalletController::class, 'changePin'])
        ->middleware('throttle:wallet-pin')
        ->name('wallet.change-pin');

    // E-Kantin sisi siswa (jelajah menu & pesan dengan saldo sendiri)
    Route::get('/canteen', [CanteenController::class, 'index'])->name('canteen.index');
    Route::post('/canteen/order', [CanteenController::class, 'storeOrder'])
        ->middleware('throttle:wallet-pin')
        ->name('canteen.order.store');

    // Preview KTS milik sendiri
    Route::get('/kts/preview/{hashId?}', [KtsController::class, 'preview'])->name('kts.preview');

    // Unggah bukti transfer bank. Siswa/orang tua hanya untuk tagihan sendiri
    // (diverifikasi di controller); petugas keuangan boleh atas nama siswa lain.
    // Pembayaran yang dihasilkan berstatus `pending` sampai kasir memverifikasi.
    Route::post('/finance/upload-proof', [FinanceController::class, 'uploadProof'])
        ->middleware('throttle:12,1')
        ->name('finance.upload-proof');

    // Berkas bukti transfer disimpan di disk privat dan hanya disajikan lewat
    // rute ini setelah pemeriksaan peran / kepemilikan.
    Route::get('/finance/proof/{hashId}', [FinanceController::class, 'proofDocument'])
        ->name('finance.proof');
});

/*
| Master data akademik - Admin TU, Kepala Sekolah, Operator.
*/
Route::middleware(['auth', $roleAcademicAdmin])->group(function () {
    // Master Data Siswa & Rombel
    Route::get('/academic/students', [StudentController::class, 'index'])->name('academic.students.index');
    Route::post('/academic/students', [StudentController::class, 'store'])->name('academic.students.store');
    Route::put('/academic/students/{id}', [StudentController::class, 'update'])->name('academic.students.update');
    Route::delete('/academic/students/{id}', [StudentController::class, 'destroy'])->name('academic.students.destroy');
    Route::post('/academic/students/{id}/reset-password', [StudentController::class, 'resetPassword'])->name('academic.students.reset-password');

    // Master Data Tahun Ajaran & Semester
    Route::get('/academic/academic-years', [AcademicYearController::class, 'index'])->name('academic.academic-years.index');
    Route::post('/academic/academic-years', [AcademicYearController::class, 'store'])->name('academic.academic-years.store');
    Route::put('/academic/academic-years/{id}', [AcademicYearController::class, 'update'])->name('academic.academic-years.update');
    Route::delete('/academic/academic-years/{id}', [AcademicYearController::class, 'destroy'])->name('academic.academic-years.destroy');
    Route::post('/academic/academic-years/{id}/activate', [AcademicYearController::class, 'activate'])->name('academic.academic-years.activate');

    // Master Data Program Keahlian / Jurusan
    Route::get('/academic/departments', [DepartmentController::class, 'index'])->name('academic.departments.index');
    Route::post('/academic/departments', [DepartmentController::class, 'store'])->name('academic.departments.store');
    Route::put('/academic/departments/{id}', [DepartmentController::class, 'update'])->name('academic.departments.update');
    Route::delete('/academic/departments/{id}', [DepartmentController::class, 'destroy'])->name('academic.departments.destroy');
    Route::post('/academic/departments/{id}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('academic.departments.toggle-status');

    // Master Data Guru & Tenaga Kependidikan (PTK)
    Route::get('/academic/teachers', [TeacherController::class, 'index'])->name('academic.teachers.index');
    Route::post('/academic/teachers', [TeacherController::class, 'store'])->name('academic.teachers.store');
    Route::put('/academic/teachers/{id}', [TeacherController::class, 'update'])->name('academic.teachers.update');
    Route::delete('/academic/teachers/{id}', [TeacherController::class, 'destroy'])->name('academic.teachers.destroy');
    Route::post('/academic/teachers/{id}/reset-password', [TeacherController::class, 'resetPassword'])->name('academic.teachers.reset-password');

    // Master Data Rombongan Belajar (Kelas)
    Route::get('/academic/classrooms', [ClassroomController::class, 'index'])->name('academic.classrooms.index');
    Route::post('/academic/classrooms', [ClassroomController::class, 'store'])->name('academic.classrooms.store');
    Route::put('/academic/classrooms/{id}', [ClassroomController::class, 'update'])->name('academic.classrooms.update');
    Route::delete('/academic/classrooms/{id}', [ClassroomController::class, 'destroy'])->name('academic.classrooms.destroy');

    // Master Data Mata Pelajaran (Mapel)
    Route::get('/academic/subjects', [SubjectController::class, 'index'])->name('academic.subjects.index');
    Route::post('/academic/subjects', [SubjectController::class, 'store'])->name('academic.subjects.store');
    Route::put('/academic/subjects/{id}', [SubjectController::class, 'update'])->name('academic.subjects.update');
    Route::delete('/academic/subjects/{id}', [SubjectController::class, 'destroy'])->name('academic.subjects.destroy');
    Route::post('/academic/subjects/{id}/toggle', [SubjectController::class, 'toggleStatus'])->name('academic.subjects.toggle');

    // Modul Jadwal Pelajaran & Mengajar (Timetable)
    Route::get('/academic/schedules', [ScheduleController::class, 'index'])->name('academic.schedules');
    Route::post('/academic/schedules', [ScheduleController::class, 'store'])->name('academic.schedules.store');
    Route::delete('/academic/schedules/{id}', [ScheduleController::class, 'destroy'])->name('academic.schedules.destroy');

    // Modul KTS Digital ISO CR-80 & Batch Print
    Route::get('/kts/batch-print', [KtsController::class, 'batchPrint'])->name('kts.batch');
    Route::get('/kts/batch-print/pdf', [KtsController::class, 'batchPrintPdf'])->name('kts.batch.pdf');

    // Pengaturan Geofence GPS & Jam Kerja Presensi
    Route::get('/attendance/settings', [AttendanceSettingController::class, 'index'])->name('attendance.settings.index');
    Route::post('/attendance/geofences', [AttendanceSettingController::class, 'store'])->name('attendance.geofences.store');
    Route::put('/attendance/geofences/{id}', [AttendanceSettingController::class, 'update'])->name('attendance.geofences.update');
    Route::post('/attendance/geofences/{id}/toggle', [AttendanceSettingController::class, 'toggleStatus'])->name('attendance.geofences.toggle');
    Route::delete('/attendance/geofences/{id}', [AttendanceSettingController::class, 'destroy'])->name('attendance.geofences.destroy');
    Route::post('/attendance/settings/working-hours', [AttendanceSettingController::class, 'updateWorkingHours'])->name('attendance.settings.hours');
});

/*
| Kegiatan belajar mengajar - Guru beserta Admin TU, Kepala Sekolah, Operator.
*/
Route::middleware(['auth', $roleAcademicStaff])->group(function () {
    // Buku Nilai Guru & Penilaian Siswa (Gradebook)
    Route::get('/academic/grades', [GradeController::class, 'index'])->name('academic.grades.index');
    Route::post('/academic/grades/batch', [GradeController::class, 'batchStoreOrUpdate'])->name('academic.grades.batch');

    // Leger Nilai & Buku Rapor Siswa
    Route::get('/academic/reports', [AcademicReportController::class, 'index'])->name('academic.reports.index');
    Route::post('/academic/reports/remarks', [AcademicReportController::class, 'storeRemark'])->name('academic.reports.remarks.store');
    Route::get('/academic/reports/{hashId}/pdf', [AcademicReportController::class, 'printReportCard'])->name('academic.reports.pdf');

    // Presensi Kelas & Absensi Siswa per Jam Pelajaran
    Route::get('/attendance/classroom', [ClassroomAttendanceController::class, 'index'])->name('attendance.classroom.index');
    Route::post('/attendance/classroom', [ClassroomAttendanceController::class, 'store'])->name('attendance.classroom.store');

    // CBT Ujian Online & Bank Soal
    Route::get('/academic/cbt', [CbtController::class, 'index'])->name('academic.cbt.index');
    Route::post('/academic/cbt/banks', [CbtController::class, 'storeBank'])->name('academic.cbt.banks.store');
    Route::put('/academic/cbt/banks/{id}', [CbtController::class, 'updateBank'])->name('academic.cbt.banks.update');
    Route::delete('/academic/cbt/banks/{id}', [CbtController::class, 'destroyBank'])->name('academic.cbt.banks.destroy');
    Route::post('/academic/cbt/banks/{bankId}/questions', [CbtController::class, 'storeQuestion'])->name('academic.cbt.questions.store');
    Route::put('/academic/cbt/questions/{id}', [CbtController::class, 'updateQuestion'])->name('academic.cbt.questions.update');
    Route::delete('/academic/cbt/questions/{id}', [CbtController::class, 'destroyQuestion'])->name('academic.cbt.questions.destroy');
    Route::post('/academic/cbt/exams', [CbtController::class, 'storeExam'])->name('academic.cbt.exams.store');
    Route::put('/academic/cbt/exams/{id}', [CbtController::class, 'updateExam'])->name('academic.cbt.exams.update');
    Route::delete('/academic/cbt/exams/{id}', [CbtController::class, 'destroyExam'])->name('academic.cbt.exams.destroy');
    Route::post('/academic/cbt/exams/{id}/regenerate-token', [CbtController::class, 'regenerateToken'])->name('academic.cbt.exams.regenerate-token');
    Route::post('/academic/cbt/exams/{id}/push-grades', [CbtController::class, 'pushGradesToGradebook'])->name('academic.cbt.exams.push-grades');
    Route::post('/academic/cbt/exams/{id}/submit-simulation', [CbtController::class, 'submitSimulation'])->name('academic.cbt.exams.submit-simulation');
});

/*
| Jurnal mengajar - Guru mengisi, Admin / Kepala Sekolah memverifikasi.
*/
Route::middleware(['auth', $roleTeaching])->group(function () {
    Route::get('/academic/journals', [TeachingJournalController::class, 'index'])->name('academic.journals.index');
    Route::post('/academic/journals', [TeachingJournalController::class, 'store'])->name('academic.journals.store');
    Route::put('/academic/journals/{id}', [TeachingJournalController::class, 'update'])->name('academic.journals.update');
    Route::delete('/academic/journals/{id}', [TeachingJournalController::class, 'destroy'])->name('academic.journals.destroy');
});

Route::middleware(['auth', $roleCounseling])->group(function () {
    Route::post('/academic/journals/{id}/verify', [TeachingJournalController::class, 'verify'])->name('academic.journals.verify');
});

/*
| Bimbingan Konseling & Kedisiplinan Siswa.
*/
Route::middleware(['auth', $roleCounselingInput])->group(function () {
    Route::get('/counseling/violations', [StudentViolationController::class, 'index'])->name('counseling.violations.index');
    Route::post('/counseling/violations', [StudentViolationController::class, 'store'])->name('counseling.violations.store');
});

Route::middleware(['auth', $roleCounseling])->group(function () {
    Route::put('/counseling/violations/{id}', [StudentViolationController::class, 'update'])->name('counseling.violations.update');
    Route::delete('/counseling/violations/{id}', [StudentViolationController::class, 'destroy'])->name('counseling.violations.destroy');
    Route::post('/counseling/violations/{id}/resolve', [StudentViolationController::class, 'resolve'])->name('counseling.violations.resolve');
    Route::post('/counseling/violations/{id}/notify-parent', [StudentViolationController::class, 'notifyParent'])->name('counseling.violations.notify-parent');
    Route::post('/counseling/categories', [StudentViolationController::class, 'storeCategory'])->name('counseling.categories.store');
    Route::put('/counseling/categories/{id}', [StudentViolationController::class, 'updateCategory'])->name('counseling.categories.update');
    Route::delete('/counseling/categories/{id}', [StudentViolationController::class, 'destroyCategory'])->name('counseling.categories.destroy');
});

/*
| Keuangan sekolah - Bendahara, Admin TU, Kepala Sekolah.
*/
Route::middleware(['auth', $roleFinance])->group(function () {
    // POS Kasir SPP
    Route::get('/finance/pos-kasir', [FinanceController::class, 'posKasir'])->name('finance.pos');
    Route::get('/finance/search-students', [FinanceController::class, 'searchStudents'])->name('finance.search-students');
    Route::post('/finance/pay', [FinanceController::class, 'storePayment'])->name('finance.pay');
    Route::get('/finance/receipt/{hashId}', [FinanceController::class, 'receipt'])->name('finance.receipt');

    // Manajemen Tagihan Siswa & Batch Billing SPP
    Route::get('/finance/bills', [FinanceController::class, 'billsIndex'])->name('finance.bills.index');
    Route::post('/finance/bills/batch', [FinanceController::class, 'generateBatchBills'])->name('finance.bills.batch');
    Route::delete('/finance/bills/{id}', [FinanceController::class, 'destroyBill'])->name('finance.bills.destroy');

    // Master Pos Tarif & Kategori Biaya
    Route::get('/finance/fee-categories', [FinanceController::class, 'feeCategoriesIndex'])->name('finance.fee-categories.index');
    Route::post('/finance/fee-categories', [FinanceController::class, 'storeFeeCategory'])->name('finance.fee-categories.store');
    Route::put('/finance/fee-categories/{id}', [FinanceController::class, 'updateFeeCategory'])->name('finance.fee-categories.update');
    Route::delete('/finance/fee-categories/{id}', [FinanceController::class, 'destroyFeeCategory'])->name('finance.fee-categories.destroy');
});

/*
| Mutasi saldo tabungan siswa hanya boleh dilakukan loket kasir.
*/
Route::middleware(['auth', $roleCashier])->group(function () {
    Route::post('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])
        ->middleware('throttle:wallet-pin')
        ->name('wallet.withdraw');
});

/*
| E-Kantin sisi vendor / kasir stand.
*/
Route::middleware(['auth', $roleCanteenVendor])->group(function () {
    Route::get('/canteen/stand/{standId}/pos', [CanteenController::class, 'standPos'])->name('canteen.stand.pos');
    Route::post('/canteen/orders/{orderId}/status', [CanteenController::class, 'updateStatus'])->name('canteen.order.status');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
