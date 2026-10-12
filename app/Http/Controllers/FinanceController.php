<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\FeeCategory;
use App\Models\GradeLevel;
use App\Models\Payment;
use App\Models\StudentBill;
use App\Models\StudentProfile;
use App\Rules\TenantUnique;
use App\Services\FinanceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    /**
     * Disk penyimpanan bukti transfer. Lihat config/filesystems.php - disk ini
     * tidak disajikan lewat rute bawaan framework.
     */
    public const PROOF_DISK = 'secure';

    public function __construct(
        protected FinanceService $financeService
    ) {}

    /**
     * Tampilan POS Kasir Pembayaran SPP & Tagihan Sekolah.
     */
    public function posKasir(Request $request): Response
    {
        $academicYears = AcademicYear::orderByDesc('is_active')->get();
        $classrooms = Classroom::with('department')->orderBy('name')->get();
        $feeCategories = FeeCategory::where('is_active', true)->get();

        return Inertia::render('Finance/PosKasir', [
            'academicYears' => $academicYears,
            'classrooms' => $classrooms,
            'feeCategories' => $feeCategories,
        ]);
    }

    /**
     * Endpoint Pencarian Siswa & Rincian Tunggakan Tagihan untuk POS Kasir.
     */
    public function searchStudents(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $classroomId = $request->input('classroom_id');

        $studentsQuery = StudentProfile::with([
            'user',
            'classroom.department',
            'wallet',
            'bills' => function ($q) {
                $q->whereIn('status', ['unpaid', 'partial'])->with('feeCategory');
            },
        ]);

        if (! empty($classroomId)) {
            $studentsQuery->where('classroom_id', $classroomId);
        }

        if (! empty($query)) {
            $studentsQuery->where(function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                    ->orWhere('nisn', 'like', "%{$query}%")
                    ->orWhere('rfid_uid', 'like', "%{$query}%");
            });
        }

        $students = $studentsQuery->take(20)->get()->map(function ($student) {
            $unpaidBills = $student->bills->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'title' => $bill->title,
                    'category' => $bill->feeCategory?->name ?? 'Biaya Sekolah',
                    'amount' => (float) $bill->amount,
                    'paid_amount' => (float) $bill->paid_amount,
                    'remaining_amount' => (float) $bill->remaining_amount,
                    'month' => $bill->month,
                    'year' => $bill->year,
                    'status' => $bill->status,
                    'due_date' => $bill->due_date?->format('d M Y'),
                ];
            });

            return [
                'id' => $student->id,
                'hash_id' => $student->hash_id,
                'name' => $student->full_name,
                'nisn' => $student->nisn,
                'classroom' => $student->classroom?->name ?? '-',
                'rfid_uid' => $student->rfid_uid,
                'wallet_balance' => (float) ($student->wallet?->balance ?? 0),
                'bills' => $unpaidBills,
                'total_arrears' => $unpaidBills->sum('remaining_amount'),
            ];
        });

        return response()->json([
            'success' => true,
            'students' => $students,
        ]);
    }

    /**
     * Eksekusi Pembayaran Tagihan dari POS Kasir.
     */
    public function storePayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_profile_id' => 'required|integer|exists:student_profiles,id',
            'payment_method' => 'required|in:cash,bank_transfer,wallet,qris',
            'allocations' => 'required|array|min:1',
            'allocations.*.bill_id' => 'required|integer|exists:student_bills,id',
            'allocations.*.amount' => 'required|numeric|min:1000',
            'total_amount' => 'required|numeric|min:1000',
            'pin' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $payment = $this->financeService->processPayment(
                (int) $validated['student_profile_id'],
                $validated['allocations'],
                $validated['payment_method'],
                (float) $validated['total_amount'],
                (int) $request->user()->id,
                $validated['reference_number'] ?? null,
                null,
                $validated['pin'] ?? null,
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diproses.',
                'payment' => [
                    'id' => $payment->id,
                    'hash_id' => $payment->hash_id,
                    'payment_number' => $payment->payment_number,
                    'amount' => (float) $payment->amount,
                    'payment_date' => $payment->payment_date->format('d M Y H:i'),
                    'payment_method' => $payment->payment_method,
                    'receipt_url' => route('finance.receipt', $payment->hash_id),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cetak Kuitansi Pembayaran Resmi Format Sekolah (A5 / Thermal 80mm).
     */
    public function receipt(string $hashId): Response
    {
        $payment = Payment::with([
            'studentProfile.classroom.department',
            'studentProfile.user',
            'user',
            'bills.feeCategory',
            'billPayments.studentBill',
        ])->whereHashId($hashId)->firstOrFail();

        $terbilangText = FinanceService::terbilang((float) $payment->amount);

        return Inertia::render('Finance/ReceiptPrint', [
            'payment' => [
                'id' => $payment->id,
                'hash_id' => $payment->hash_id,
                'payment_number' => $payment->payment_number,
                'amount' => (float) $payment->amount,
                'amount_formatted' => 'Rp '.number_format((float) $payment->amount, 0, ',', '.'),
                'terbilang' => $terbilangText,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date->format('d F Y, H:i:s').' WIB',
                'cashier_name' => $payment->user?->name ?? 'Kasir Sekolah',
                'status' => $payment->status,
                'notes' => $payment->notes,
                'student' => [
                    'name' => $payment->studentProfile->full_name,
                    'nisn' => $payment->studentProfile->nisn,
                    'classroom' => $payment->studentProfile->classroom?->name ?? '-',
                    'department' => $payment->studentProfile->classroom?->department?->name ?? '-',
                ],
                'items' => $payment->billPayments->map(function ($bp) {
                    return [
                        'title' => $bp->studentBill->title,
                        'amount' => (float) $bp->amount_allocated,
                        'amount_formatted' => 'Rp '.number_format((float) $bp->amount_allocated, 0, ',', '.'),
                    ];
                }),
            ],
            'school' => [
                'name' => 'SMK INFORMATIKA TERPADU',
                'npsn' => '20104567',
                'address' => 'Jl. Pendidikan Terpadu No. 128, Gedung A-B, Jakarta',
                'phone' => '(021) 7890-1234',
                'email' => 'finance@smkinformatika.sch.id',
                'treasurer' => 'Hj. Ratna Sari, S.E., M.M.',
            ],
        ]);
    }

    /**
     * Upload Bukti Transfer Bank Manual oleh Siswa / Orang Tua.
     */
    public function uploadProof(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_profile_id' => 'required|integer|exists:student_profiles,id',
            'bill_id' => 'required|integer|exists:student_bills,id',
            'amount' => 'required|numeric|min:1000',
            'reference_number' => 'required|string|max:100',
            'proof_file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:3072',
            'notes' => 'nullable|string|max:255',
        ]);

        $studentProfile = StudentProfile::findOrFail((int) $validated['student_profile_id']);

        // Siswa / orang tua hanya boleh mengunggah bukti untuk dirinya sendiri.
        // Petugas keuangan boleh mengunggah atas nama siswa mana pun.
        if (! $this->isFinanceStaff($request) && ! $this->ownsStudentProfile($request, $studentProfile)) {
            abort(403, 'Anda hanya dapat mengunggah bukti transfer untuk tagihan sendiri.');
        }

        // Disimpan di disk `secure`, bukan `public` maupun `local`. Bukti transfer
        // memuat data rekening dan identitas sehingga tidak boleh dapat diakses lewat
        // URL publik (/storage/...) tanpa pemeriksaan peran / kepemilikan.
        $filePath = $request->file('proof_file')->store('payment_proofs', self::PROOF_DISK);

        $this->financeService->processPayment(
            (int) $validated['student_profile_id'],
            [['bill_id' => (int) $validated['bill_id'], 'amount' => (float) $validated['amount']]],
            'bank_transfer',
            (float) $validated['amount'],
            (int) $request->user()->id,
            $validated['reference_number'],
            $filePath,
            null,
            $validated['notes'] ?? 'Unggahan bukti transfer orang tua'
        );

        return back()->with('success', 'Bukti transfer berhasil diunggah dan sedang menunggu verifikasi kasir.');
    }

    /**
     * Menyajikan berkas bukti transfer dari disk privat.
     *
     * Hanya petugas keuangan dan siswa/orang tua pemilik tagihan yang berhak.
     */
    public function proofDocument(Request $request, string $hashId): StreamedResponse
    {
        $payment = Payment::with('studentProfile')->whereHashId($hashId)->firstOrFail();

        if (empty($payment->proof_document)) {
            abort(404, 'Pembayaran ini tidak memiliki berkas bukti transfer.');
        }

        if (! $this->isFinanceStaff($request) && ! $this->ownsStudentProfile($request, $payment->studentProfile)) {
            abort(403, 'Anda tidak berhak membuka bukti transfer ini.');
        }

        $disk = Storage::disk(self::PROOF_DISK);

        if (! $disk->exists($payment->proof_document)) {
            abort(404, 'Berkas bukti transfer tidak ditemukan di penyimpanan.');
        }

        $extension = pathinfo($payment->proof_document, PATHINFO_EXTENSION);
        $downloadName = 'bukti-transfer-'.$payment->payment_number.($extension ? '.'.$extension : '');

        return $disk->response($payment->proof_document, str_replace('/', '-', $downloadName));
    }

    protected function isFinanceStaff(Request $request): bool
    {
        return (bool) $request->user()?->hasAnyRole(['admin', 'kepala_sekolah', 'bendahara']);
    }

    protected function ownsStudentProfile(Request $request, ?StudentProfile $studentProfile): bool
    {
        $user = $request->user();

        if (! $user || ! $studentProfile) {
            return false;
        }

        return $studentProfile->user_id === $user->id
            || $studentProfile->parent_user_id === $user->id;
    }

    /**
     * Tampilan Daftar & Monitoring Tagihan Siswa (Vue 3 SPA)
     */
    public function billsIndex(Request $request): Response
    {
        $search = $request->input('search');
        $classroomId = $request->input('classroom_id');
        $feeCategoryId = $request->input('fee_category_id');
        $status = $request->input('status');
        $year = $request->input('year');
        $month = $request->input('month');

        $query = StudentBill::with([
            'studentProfile.classroom.department',
            'feeCategory',
            'academicYear',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('studentProfile', function ($sq) use ($search) {
                        $sq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('nisn', 'like', "%{$search}%");
                    });
            });
        }

        if ($classroomId) {
            $query->whereHas('studentProfile', function ($sq) use ($classroomId) {
                $sq->where('classroom_id', $classroomId);
            });
        }

        if ($feeCategoryId) {
            $query->where('fee_category_id', $feeCategoryId);
        }

        if ($status && in_array($status, ['unpaid', 'partial', 'paid', 'waived'])) {
            $query->where('status', $status);
        }

        if ($year) {
            $query->where('year', (int) $year);
        }

        if ($month) {
            $query->where('month', (int) $month);
        }

        $bills = $query->latest('id')->paginate(15)->withQueryString();

        $bills->getCollection()->transform(function ($b) {
            return [
                'id' => $b->id,
                'bill_number' => $b->bill_number,
                'title' => $b->title,
                'month' => $b->month,
                'year' => $b->year,
                'amount' => (float) $b->amount,
                'paid_amount' => (float) $b->paid_amount,
                'remaining_amount' => (float) $b->remaining_amount,
                'status' => $b->status,
                'due_date' => $b->due_date?->format('Y-m-d'),
                'due_date_formatted' => $b->due_date?->format('d M Y') ?? '-',
                'student_name' => $b->studentProfile?->full_name ?? '-',
                'student_nisn' => $b->studentProfile?->nisn ?? '-',
                'classroom_name' => $b->studentProfile?->classroom?->name ?? 'Tanpa Rombel',
                'category_name' => $b->feeCategory?->name ?? 'Biaya Sekolah',
            ];
        });

        // Hitung total piutang yang belum terbayar
        $totalArrears = (float) StudentBill::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('SUM(amount - paid_amount) as remaining')
            ->value('remaining') ?? 0.0;

        $stats = [
            'total_billed' => (float) StudentBill::sum('amount'),
            'total_collected' => (float) StudentBill::sum('paid_amount'),
            'total_arrears' => $totalArrears,
            'unpaid_count' => StudentBill::where('status', 'unpaid')->count(),
            'paid_count' => StudentBill::where('status', 'paid')->count(),
            'total_bills' => StudentBill::count(),
        ];

        $classrooms = Classroom::with('department')->orderBy('name')->get(['id', 'name', 'department_id']);
        $gradeLevels = GradeLevel::orderBy('level_numeric')->get(['id', 'name', 'level_numeric']);
        $feeCategories = FeeCategory::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'default_amount', 'is_recurring']);
        $academicYears = AcademicYear::orderByDesc('is_active')->get(['id', 'name', 'is_active']);

        return Inertia::render('Finance/Bills/Index', [
            'bills' => $bills,
            'classrooms' => $classrooms,
            'gradeLevels' => $gradeLevels,
            'feeCategories' => $feeCategories,
            'academicYears' => $academicYears,
            'filters' => [
                'search' => $search,
                'classroom_id' => $classroomId ? (int) $classroomId : null,
                'fee_category_id' => $feeCategoryId ? (int) $feeCategoryId : null,
                'status' => $status,
                'year' => $year ? (int) $year : null,
                'month' => $month ? (int) $month : null,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Penerbitan Tagihan Massal (Batch Billing Generator)
     */
    public function generateBatchBills(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fee_category_id' => ['required', 'exists:fee_categories,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'target_type' => ['required', 'in:all,classroom,grade_level'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'grade_level_id' => ['nullable', 'exists:grade_levels,id'],
            'title' => ['required', 'string', 'max:150'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $studentsQuery = StudentProfile::query();

        if ($validated['target_type'] === 'classroom' && ! empty($validated['classroom_id'])) {
            $studentsQuery->where('classroom_id', $validated['classroom_id']);
        } elseif ($validated['target_type'] === 'grade_level' && ! empty($validated['grade_level_id'])) {
            $studentsQuery->whereHas('classroom', function ($q) use ($validated) {
                $q->where('grade_level_id', $validated['grade_level_id']);
            });
        } else {
            $studentsQuery->whereNotNull('classroom_id');
        }

        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return back()->withErrors(['error' => 'Tidak ada siswa yang ditemukan pada rombel/tingkat yang dipilih.']);
        }

        $createdCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($students, $validated, &$createdCount, &$skippedCount) {
            foreach ($students as $student) {
                // Cek duplikasi tagihan yang sama
                $existsQuery = StudentBill::where('student_profile_id', $student->id)
                    ->where('fee_category_id', $validated['fee_category_id'])
                    ->where('year', $validated['year']);

                if (! empty($validated['month'])) {
                    $existsQuery->where('month', $validated['month']);
                }

                if ($existsQuery->exists()) {
                    $skippedCount++;

                    continue;
                }

                $billNumber = 'INV-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));

                StudentBill::create([
                    'student_profile_id' => $student->id,
                    'fee_category_id' => $validated['fee_category_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                    'bill_number' => $billNumber,
                    'title' => $validated['title'],
                    'month' => $validated['month'] ?? null,
                    'year' => $validated['year'],
                    'amount' => $validated['amount'],
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'due_date' => $validated['due_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $createdCount++;
            }
        });

        $message = "Berhasil menerbitkan {$createdCount} tagihan siswa!";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} siswa dilewati karena sudah memiliki tagihan ini).";
        }

        return back()->with('status', $message);
    }

    /**
     * Hapus Tagihan Siswa (dengan Proteksi Pembayaran)
     */
    public function destroyBill(int|string $id): RedirectResponse
    {
        $bill = StudentBill::findOrFail($id);

        if ((float) $bill->paid_amount > 0) {
            return back()->withErrors([
                'error' => "Tagihan {$bill->bill_number} tidak dapat dihapus karena sudah memiliki riwayat pembayaran sebesar Rp ".number_format((float) $bill->paid_amount, 0, ',', '.'),
            ]);
        }

        $number = $bill->bill_number;
        $bill->delete();

        return back()->with('status', "Tagihan {$number} berhasil dihapus!");
    }

    /**
     * Master Pos Tarif & Kategori Biaya (Vue 3 SPA)
     */
    public function feeCategoriesIndex(): Response
    {
        $categories = FeeCategory::withCount('bills')
            ->withSum('bills as total_billed', 'amount')
            ->orderBy('name')
            ->get();

        return Inertia::render('Finance/FeeCategories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Simpan Kategori Biaya Baru
     */
    public function storeFeeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', TenantUnique::make('fee_categories', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'is_recurring' => ['boolean'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        FeeCategory::create($validated);

        return back()->with('status', "Pos biaya {$validated['name']} berhasil ditambahkan!");
    }

    /**
     * Perbarui Kategori Biaya
     */
    public function updateFeeCategory(Request $request, int|string $id): RedirectResponse
    {
        $category = FeeCategory::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', TenantUnique::make('fee_categories', 'code', $category->id)],
            'name' => ['required', 'string', 'max:100'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'is_recurring' => ['boolean'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $category->update($validated);

        return back()->with('status', "Pos biaya {$category->name} berhasil diperbarui!");
    }

    /**
     * Hapus Kategori Biaya
     */
    public function destroyFeeCategory(int|string $id): RedirectResponse
    {
        $category = FeeCategory::withCount('bills')->findOrFail($id);

        if ($category->bills_count > 0) {
            return back()->withErrors([
                'error' => "Pos biaya {$category->name} tidak dapat dihapus karena sudah digunakan oleh {$category->bills_count} tagihan siswa.",
            ]);
        }

        $name = $category->name;
        $category->delete();

        return back()->with('status', "Pos biaya {$name} berhasil dihapus!");
    }
}
