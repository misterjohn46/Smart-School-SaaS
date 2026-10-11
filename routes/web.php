<?php

use App\Http\Controllers\Central\SchoolRegistrationController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Models\Plan;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

        Route::post('/register-school', [SchoolRegistrationController::class, 'store'])->name('school.register');
    });
}

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Portal Siswa & Orang Tua Terpadu
Route::middleware(['auth'])->group(function () {
    Route::get('/portal/siswa', [PortalController::class, 'student'])->name('portal.student');
    Route::get('/portal/orangtua', [PortalController::class, 'parent'])->name('portal.parent');

    // Master Data Siswa & Rombel
    Route::get('/academic/students', [\App\Http\Controllers\StudentController::class, 'index'])->name('academic.students.index');
    Route::post('/academic/students', [\App\Http\Controllers\StudentController::class, 'store'])->name('academic.students.store');
    Route::put('/academic/students/{id}', [\App\Http\Controllers\StudentController::class, 'update'])->name('academic.students.update');
    Route::delete('/academic/students/{id}', [\App\Http\Controllers\StudentController::class, 'destroy'])->name('academic.students.destroy');
    Route::post('/academic/students/{id}/reset-password', [\App\Http\Controllers\StudentController::class, 'resetPassword'])->name('academic.students.reset-password');

    // Master Data Tahun Ajaran & Semester
    Route::get('/academic/academic-years', [\App\Http\Controllers\AcademicYearController::class, 'index'])->name('academic.academic-years.index');
    Route::post('/academic/academic-years', [\App\Http\Controllers\AcademicYearController::class, 'store'])->name('academic.academic-years.store');
    Route::put('/academic/academic-years/{id}', [\App\Http\Controllers\AcademicYearController::class, 'update'])->name('academic.academic-years.update');
    Route::delete('/academic/academic-years/{id}', [\App\Http\Controllers\AcademicYearController::class, 'destroy'])->name('academic.academic-years.destroy');
    Route::post('/academic/academic-years/{id}/activate', [\App\Http\Controllers\AcademicYearController::class, 'activate'])->name('academic.academic-years.activate');

    // Master Data Program Keahlian / Jurusan
    Route::get('/academic/departments', [\App\Http\Controllers\DepartmentController::class, 'index'])->name('academic.departments.index');
    Route::post('/academic/departments', [\App\Http\Controllers\DepartmentController::class, 'store'])->name('academic.departments.store');
    Route::put('/academic/departments/{id}', [\App\Http\Controllers\DepartmentController::class, 'update'])->name('academic.departments.update');
    Route::delete('/academic/departments/{id}', [\App\Http\Controllers\DepartmentController::class, 'destroy'])->name('academic.departments.destroy');
    Route::post('/academic/departments/{id}/toggle-status', [\App\Http\Controllers\DepartmentController::class, 'toggleStatus'])->name('academic.departments.toggle-status');

    // Master Data Guru & Tenaga Kependidikan (PTK)
    Route::get('/academic/teachers', [\App\Http\Controllers\TeacherController::class, 'index'])->name('academic.teachers.index');
    Route::post('/academic/teachers', [\App\Http\Controllers\TeacherController::class, 'store'])->name('academic.teachers.store');
    Route::put('/academic/teachers/{id}', [\App\Http\Controllers\TeacherController::class, 'update'])->name('academic.teachers.update');
    Route::delete('/academic/teachers/{id}', [\App\Http\Controllers\TeacherController::class, 'destroy'])->name('academic.teachers.destroy');
    Route::post('/academic/teachers/{id}/reset-password', [\App\Http\Controllers\TeacherController::class, 'resetPassword'])->name('academic.teachers.reset-password');

    // Master Data Rombongan Belajar (Kelas)
    Route::get('/academic/classrooms', [\App\Http\Controllers\ClassroomController::class, 'index'])->name('academic.classrooms.index');
    Route::post('/academic/classrooms', [\App\Http\Controllers\ClassroomController::class, 'store'])->name('academic.classrooms.store');
    Route::put('/academic/classrooms/{id}', [\App\Http\Controllers\ClassroomController::class, 'update'])->name('academic.classrooms.update');
    Route::delete('/academic/classrooms/{id}', [\App\Http\Controllers\ClassroomController::class, 'destroy'])->name('academic.classrooms.destroy');

    // Master Data Mata Pelajaran (Mapel)
    Route::get('/academic/subjects', [\App\Http\Controllers\SubjectController::class, 'index'])->name('academic.subjects.index');
    Route::post('/academic/subjects', [\App\Http\Controllers\SubjectController::class, 'store'])->name('academic.subjects.store');
    Route::put('/academic/subjects/{id}', [\App\Http\Controllers\SubjectController::class, 'update'])->name('academic.subjects.update');
    Route::delete('/academic/subjects/{id}', [\App\Http\Controllers\SubjectController::class, 'destroy'])->name('academic.subjects.destroy');
    Route::post('/academic/subjects/{id}/toggle', [\App\Http\Controllers\SubjectController::class, 'toggleStatus'])->name('academic.subjects.toggle');

    // Modul Jadwal Pelajaran & Mengajar (Timetable)
    Route::get('/academic/schedules', [\App\Http\Controllers\ScheduleController::class, 'index'])->name('academic.schedules');
    Route::post('/academic/schedules', [\App\Http\Controllers\ScheduleController::class, 'store'])->name('academic.schedules.store');
    Route::delete('/academic/schedules/{id}', [\App\Http\Controllers\ScheduleController::class, 'destroy'])->name('academic.schedules.destroy');

    // Buku Nilai Guru & Penilaian Siswa (Gradebook)
    Route::get('/academic/grades', [\App\Http\Controllers\GradeController::class, 'index'])->name('academic.grades.index');
    Route::post('/academic/grades/batch', [\App\Http\Controllers\GradeController::class, 'batchStoreOrUpdate'])->name('academic.grades.batch');

    // Jurnal Mengajar Guru & Agenda Kelas
    Route::get('/academic/journals', [\App\Http\Controllers\TeachingJournalController::class, 'index'])->name('academic.journals.index');
    Route::post('/academic/journals', [\App\Http\Controllers\TeachingJournalController::class, 'store'])->name('academic.journals.store');
    Route::put('/academic/journals/{id}', [\App\Http\Controllers\TeachingJournalController::class, 'update'])->name('academic.journals.update');
    Route::delete('/academic/journals/{id}', [\App\Http\Controllers\TeachingJournalController::class, 'destroy'])->name('academic.journals.destroy');
    Route::post('/academic/journals/{id}/verify', [\App\Http\Controllers\TeachingJournalController::class, 'verify'])->name('academic.journals.verify');

    // CBT Ujian Online & Bank Soal
    Route::get('/academic/cbt', [\App\Http\Controllers\CbtController::class, 'index'])->name('academic.cbt.index');
    Route::post('/academic/cbt/banks', [\App\Http\Controllers\CbtController::class, 'storeBank'])->name('academic.cbt.banks.store');
    Route::put('/academic/cbt/banks/{id}', [\App\Http\Controllers\CbtController::class, 'updateBank'])->name('academic.cbt.banks.update');
    Route::delete('/academic/cbt/banks/{id}', [\App\Http\Controllers\CbtController::class, 'destroyBank'])->name('academic.cbt.banks.destroy');
    Route::post('/academic/cbt/banks/{bankId}/questions', [\App\Http\Controllers\CbtController::class, 'storeQuestion'])->name('academic.cbt.questions.store');
    Route::put('/academic/cbt/questions/{id}', [\App\Http\Controllers\CbtController::class, 'updateQuestion'])->name('academic.cbt.questions.update');
    Route::delete('/academic/cbt/questions/{id}', [\App\Http\Controllers\CbtController::class, 'destroyQuestion'])->name('academic.cbt.questions.destroy');
    Route::post('/academic/cbt/exams', [\App\Http\Controllers\CbtController::class, 'storeExam'])->name('academic.cbt.exams.store');
    Route::put('/academic/cbt/exams/{id}', [\App\Http\Controllers\CbtController::class, 'updateExam'])->name('academic.cbt.exams.update');
    Route::delete('/academic/cbt/exams/{id}', [\App\Http\Controllers\CbtController::class, 'destroyExam'])->name('academic.cbt.exams.destroy');
    Route::post('/academic/cbt/exams/{id}/regenerate-token', [\App\Http\Controllers\CbtController::class, 'regenerateToken'])->name('academic.cbt.exams.regenerate-token');
    Route::post('/academic/cbt/exams/{id}/push-grades', [\App\Http\Controllers\CbtController::class, 'pushGradesToGradebook'])->name('academic.cbt.exams.push-grades');
    Route::post('/academic/cbt/exams/{id}/submit-simulation', [\App\Http\Controllers\CbtController::class, 'submitSimulation'])->name('academic.cbt.exams.submit-simulation');

    // Leger Nilai & Buku Rapor Siswa
    Route::get('/academic/reports', [\App\Http\Controllers\AcademicReportController::class, 'index'])->name('academic.reports.index');
    Route::post('/academic/reports/remarks', [\App\Http\Controllers\AcademicReportController::class, 'storeRemark'])->name('academic.reports.remarks.store');

    // Modul KTS Digital ISO CR-80 & Batch Print
    Route::get('/kts/preview/{hashId?}', [\App\Http\Controllers\KtsController::class, 'preview'])->name('kts.preview');
    Route::get('/kts/batch-print', [\App\Http\Controllers\KtsController::class, 'batchPrint'])->name('kts.batch');

    // Presensi Kelas & Absensi Siswa per Jam Pelajaran
    Route::get('/attendance/classroom', [\App\Http\Controllers\ClassroomAttendanceController::class, 'index'])->name('attendance.classroom.index');
    Route::post('/attendance/classroom', [\App\Http\Controllers\ClassroomAttendanceController::class, 'store'])->name('attendance.classroom.store');

    // Presensi GPS Geofencing & AI Face
    Route::get('/attendance/gps-face', [\App\Http\Controllers\AttendanceController::class, 'gpsFace'])->name('attendance.gps.index');
    Route::post('/attendance/gps-face', [\App\Http\Controllers\AttendanceController::class, 'storeGps'])->name('attendance.gps.store');

    // Pengajuan Izin / Sakit (E-Leave)
    Route::get('/attendance/leave', [\App\Http\Controllers\AttendanceController::class, 'leaveIndex'])->name('attendance.leave.index');
    Route::post('/attendance/leave', [\App\Http\Controllers\AttendanceController::class, 'storeLeave'])->name('attendance.leave.store');

    // Pengaturan Geofence GPS & Jam Kerja Presensi
    Route::get('/attendance/settings', [\App\Http\Controllers\AttendanceSettingController::class, 'index'])->name('attendance.settings.index');
    Route::post('/attendance/geofences', [\App\Http\Controllers\AttendanceSettingController::class, 'store'])->name('attendance.geofences.store');
    Route::put('/attendance/geofences/{id}', [\App\Http\Controllers\AttendanceSettingController::class, 'update'])->name('attendance.geofences.update');
    Route::post('/attendance/geofences/{id}/toggle', [\App\Http\Controllers\AttendanceSettingController::class, 'toggleStatus'])->name('attendance.geofences.toggle');
    Route::delete('/attendance/geofences/{id}', [\App\Http\Controllers\AttendanceSettingController::class, 'destroy'])->name('attendance.geofences.destroy');
    Route::post('/attendance/settings/working-hours', [\App\Http\Controllers\AttendanceSettingController::class, 'updateWorkingHours'])->name('attendance.settings.hours');

    // Modul Kedisiplinan Siswa, Tata Tertib & Konseling BK
    Route::get('/counseling/violations', [\App\Http\Controllers\StudentViolationController::class, 'index'])->name('counseling.violations.index');
    Route::post('/counseling/violations', [\App\Http\Controllers\StudentViolationController::class, 'store'])->name('counseling.violations.store');
    Route::put('/counseling/violations/{id}', [\App\Http\Controllers\StudentViolationController::class, 'update'])->name('counseling.violations.update');
    Route::delete('/counseling/violations/{id}', [\App\Http\Controllers\StudentViolationController::class, 'destroy'])->name('counseling.violations.destroy');
    Route::post('/counseling/violations/{id}/resolve', [\App\Http\Controllers\StudentViolationController::class, 'resolve'])->name('counseling.violations.resolve');
    Route::post('/counseling/violations/{id}/notify-parent', [\App\Http\Controllers\StudentViolationController::class, 'notifyParent'])->name('counseling.violations.notify-parent');
    Route::post('/counseling/categories', [\App\Http\Controllers\StudentViolationController::class, 'storeCategory'])->name('counseling.categories.store');
    Route::put('/counseling/categories/{id}', [\App\Http\Controllers\StudentViolationController::class, 'updateCategory'])->name('counseling.categories.update');
    Route::delete('/counseling/categories/{id}', [\App\Http\Controllers\StudentViolationController::class, 'destroyCategory'])->name('counseling.categories.destroy');

    // Keuangan Sekolah & POS Kasir SPP
    Route::get('/finance/pos-kasir', [\App\Http\Controllers\FinanceController::class, 'posKasir'])->name('finance.pos');
    Route::get('/finance/search-students', [\App\Http\Controllers\FinanceController::class, 'searchStudents'])->name('finance.search-students');
    Route::post('/finance/pay', [\App\Http\Controllers\FinanceController::class, 'storePayment'])->name('finance.pay');
    Route::get('/finance/receipt/{hashId}', [\App\Http\Controllers\FinanceController::class, 'receipt'])->name('finance.receipt');
    Route::post('/finance/upload-proof', [\App\Http\Controllers\FinanceController::class, 'uploadProof'])->name('finance.upload-proof');

    // Manajemen Tagihan Siswa & Batch Billing SPP
    Route::get('/finance/bills', [\App\Http\Controllers\FinanceController::class, 'billsIndex'])->name('finance.bills.index');
    Route::post('/finance/bills/batch', [\App\Http\Controllers\FinanceController::class, 'generateBatchBills'])->name('finance.bills.batch');
    Route::delete('/finance/bills/{id}', [\App\Http\Controllers\FinanceController::class, 'destroyBill'])->name('finance.bills.destroy');

    // Master Pos Tarif & Kategori Biaya
    Route::get('/finance/fee-categories', [\App\Http\Controllers\FinanceController::class, 'feeCategoriesIndex'])->name('finance.fee-categories.index');
    Route::post('/finance/fee-categories', [\App\Http\Controllers\FinanceController::class, 'storeFeeCategory'])->name('finance.fee-categories.store');
    Route::put('/finance/fee-categories/{id}', [\App\Http\Controllers\FinanceController::class, 'updateFeeCategory'])->name('finance.fee-categories.update');
    Route::delete('/finance/fee-categories/{id}', [\App\Http\Controllers\FinanceController::class, 'destroyFeeCategory'])->name('finance.fee-categories.destroy');

    // Tabungan & Dompet Digital Siswa (E-Wallet)
    Route::get('/wallet', [\App\Http\Controllers\WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [\App\Http\Controllers\WalletController::class, 'topup'])->name('wallet.topup');
    Route::post('/wallet/withdraw', [\App\Http\Controllers\WalletController::class, 'withdraw'])->name('wallet.withdraw');
    Route::post('/wallet/change-pin', [\App\Http\Controllers\WalletController::class, 'changePin'])->name('wallet.change-pin');

    // E-Kantin Sekolah & POS Vendor
    Route::get('/canteen', [\App\Http\Controllers\CanteenController::class, 'index'])->name('canteen.index');
    Route::get('/canteen/stand/{standId}/pos', [\App\Http\Controllers\CanteenController::class, 'standPos'])->name('canteen.stand.pos');
    Route::post('/canteen/order', [\App\Http\Controllers\CanteenController::class, 'storeOrder'])->name('canteen.order.store');
    Route::post('/canteen/orders/{orderId}/status', [\App\Http\Controllers\CanteenController::class, 'updateStatus'])->name('canteen.order.status');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
