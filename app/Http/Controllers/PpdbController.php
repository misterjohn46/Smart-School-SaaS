<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\PpdbBatch;
use App\Models\PpdbRegistration;
use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PpdbController extends Controller
{
    // =========================================================================
    // SISI PUBLIK (CALON SISWA & WALI MURID)
    // =========================================================================

    /**
     * Halaman Informasi Utama PPDB Sekolah (Brosur, Jalur, Jadwal Gelombang)
     */
    public function publicIndex(): Response
    {
        $batches = PpdbBatch::with('academicYear')
            ->where('is_active', true)
            ->orderBy('start_date')
            ->get()
            ->map(function ($b) {
                $count = PpdbRegistration::where('ppdb_batch_id', $b->id)->count();
                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'academic_year' => $b->academicYear?->name ?? 'Tahun Ajaran Baru',
                    'start_date' => $b->start_date?->translatedFormat('d M Y'),
                    'end_date' => $b->end_date?->translatedFormat('d M Y'),
                    'announcement_date' => $b->announcement_date?->translatedFormat('d M Y'),
                    'registration_fee' => (float) $b->registration_fee,
                    'quota' => $b->quota,
                    'registered_count' => $count,
                    'quota_remaining' => max(0, $b->quota - $count),
                    'is_open' => $b->isOpen(),
                    'description' => $b->description,
                ];
            });

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Ppdb/Public/Index', [
            'batches' => $batches,
            'departments' => $departments,
        ]);
    }

    /**
     * Formulir Pendaftaran Online Calon Siswa (Multi-Step Form)
     */
    public function publicRegister(Request $request): Response|RedirectResponse
    {
        $selectedBatchId = $request->query('batch_id');

        $activeBatches = PpdbBatch::where('is_active', true)
            ->whereDate('end_date', '>=', Carbon::today())
            ->orderBy('start_date')
            ->get();

        if ($activeBatches->isEmpty()) {
            return redirect()->route('ppdb.public.index')->with('warning', 'Mohon maaf, saat ini belum ada gelombang pendaftaran PPDB yang dibuka.');
        }

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Ppdb/Public/Register', [
            'batches' => $activeBatches,
            'departments' => $departments,
            'defaultBatchId' => $selectedBatchId ? (int) $selectedBatchId : $activeBatches->first()->id,
        ]);
    }

    /**
     * Simpan Pendaftaran Baru Calon Siswa & Unggah Dokumen
     */
    public function publicStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ppdb_batch_id' => ['required', 'exists:ppdb_batches,id'],
            'track' => ['required', 'in:zonasi,prestasi,afirmasi,reguler,pindahan'],

            // Data Pribadi Siswa
            'full_name' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'in:L,P'],
            'nisn' => ['nullable', 'string', 'max:20'],
            'nik' => ['nullable', 'string', 'max:20'],
            'birth_place' => ['required', 'string', 'max:60'],
            'birth_date' => ['required', 'date'],
            'religion' => ['nullable', 'string', 'max:30'],
            'previous_school' => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['required', 'string', 'max:500'],

            // Peminatan & Nilai
            'department_id' => ['nullable', 'exists:departments,id'],
            'second_department_id' => ['nullable', 'exists:departments,id'],
            'distance_meters' => ['nullable', 'integer', 'min:0'],
            'average_report_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'achievements' => ['nullable', 'string', 'max:1000'],

            // Data Orang Tua / Wali
            'parent_name' => ['required', 'string', 'max:150'],
            'parent_phone' => ['required', 'string', 'max:25'],
            'parent_job' => ['nullable', 'string', 'max:100'],
            'parent_income' => ['nullable', 'string', 'max:50'],

            // Berkas Uploaded (Optional / Formats)
            'doc_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:3072'],
            'doc_kk' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'doc_akta' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'doc_ijazah' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $batch = PpdbBatch::findOrFail($validated['ppdb_batch_id']);

        // Generate Nomor Pendaftaran Unik (PPDB-TAHUN-URUTAN/ACAK)
        $year = date('Y');
        $regCount = PpdbRegistration::where('ppdb_batch_id', $batch->id)->count() + 1;
        $registrationNumber = sprintf('PPDB-%s-%04d', $year, $regCount);

        // Pastikan nomor unik
        while (PpdbRegistration::where('registration_number', $registrationNumber)->exists()) {
            $registrationNumber = sprintf('PPDB-%s-%04d', $year, rand(1000, 9999));
        }

        // Generate PIN 6 digit untuk pengecekan mandiri
        $accessToken = (string) rand(100000, 999999);

        // Proses unggah dokumen berkas
        $documents = [];
        $uploadPaths = [
            'doc_photo' => 'photo',
            'doc_kk' => 'kk',
            'doc_akta' => 'akta',
            'doc_ijazah' => 'ijazah',
        ];

        foreach ($uploadPaths as $inputKey => $docKey) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                $path = $file->store('ppdb/documents/' . $registrationNumber, 'public');
                $documents[$docKey] = $path;
            }
        }

        $registration = PpdbRegistration::create([
            'tenant_id' => $batch->tenant_id,
            'ppdb_batch_id' => $batch->id,
            'registration_number' => $registrationNumber,
            'access_token' => $accessToken,
            'track' => $validated['track'],
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'nisn' => $validated['nisn'] ?? null,
            'nik' => $validated['nik'] ?? null,
            'birth_place' => $validated['birth_place'],
            'birth_date' => $validated['birth_date'],
            'religion' => $validated['religion'] ?? null,
            'previous_school' => $validated['previous_school'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'],
            'department_id' => $validated['department_id'] ?? null,
            'second_department_id' => $validated['second_department_id'] ?? null,
            'distance_meters' => ! empty($validated['distance_meters']) ? (int) $validated['distance_meters'] : null,
            'average_report_score' => ! empty($validated['average_report_score']) ? (float) $validated['average_report_score'] : null,
            'achievements' => $validated['achievements'] ?? null,
            'parent_name' => $validated['parent_name'],
            'parent_phone' => $validated['parent_phone'],
            'parent_job' => $validated['parent_job'] ?? null,
            'parent_income' => $validated['parent_income'] ?? null,
            'documents' => $documents,
            'payment_status' => (float) $batch->registration_fee > 0 ? 'unpaid' : 'waived',
            'status' => 'pending',
        ]);

        return redirect()->route('ppdb.public.proof', $registration->registration_number)
            ->with('success', "Pendaftaran berhasil! Nomor Pendaftaran Anda: {$registration->registration_number}. Simpan tanda bukti ini.");
    }

    /**
     * Cetak Tanda Bukti Pendaftaran Resmi Calon Siswa (Print Ready)
     */
    public function publicProof(string $registrationNumber): Response|RedirectResponse
    {
        $registration = PpdbRegistration::with(['batch.academicYear', 'department', 'secondDepartment'])
            ->where('registration_number', $registrationNumber)
            ->first();

        if (! $registration) {
            return redirect()->route('ppdb.public.index')->withErrors(['error' => 'Data pendaftaran tidak ditemukan.']);
        }

        return Inertia::render('Ppdb/Public/Proof', [
            'registration' => [
                'id' => $registration->id,
                'registration_number' => $registration->registration_number,
                'access_token' => $registration->access_token,
                'track' => $registration->track,
                'track_label' => match ($registration->track) {
                    'zonasi' => 'Jalur Zonasi Jarak',
                    'prestasi' => 'Jalur Prestasi Akademik & Non-Akademik',
                    'afirmasi' => 'Jalur Afirmasi / KIP',
                    'pindahan' => 'Jalur Perpindahan Tugas Orang Tua',
                    default => 'Jalur Reguler / Umum',
                },
                'batch_name' => $registration->batch?->name ?? 'Gelombang PPDB',
                'academic_year' => $registration->batch?->academicYear?->name ?? '2026/2027',
                'created_at' => $registration->created_at?->translatedFormat('d F Y, H:i'),
                'full_name' => $registration->full_name,
                'gender' => $registration->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                'nisn' => $registration->nisn ?: '-',
                'nik' => $registration->nik ?: '-',
                'ttl' => ($registration->birth_place ?: '-') . ', ' . ($registration->birth_date ? $registration->birth_date->translatedFormat('d F Y') : '-'),
                'religion' => $registration->religion ?: '-',
                'previous_school' => $registration->previous_school ?: '-',
                'phone_number' => $registration->phone_number ?: '-',
                'email' => $registration->email ?: '-',
                'address' => $registration->address ?: '-',
                'department' => $registration->department?->name ?? 'Pilihan Umum',
                'second_department' => $registration->secondDepartment?->name ?? '-',
                'distance_meters' => $registration->distance_meters,
                'average_report_score' => $registration->average_report_score,
                'parent_name' => $registration->parent_name ?: '-',
                'parent_phone' => $registration->parent_phone ?: '-',
                'status' => $registration->status,
                'documents' => $registration->documents ?? [],
                'announcement_date' => $registration->batch?->announcement_date?->translatedFormat('d F Y') ?? 'Sesuai Jadwal',
            ],
        ]);
    }

    /**
     * Portal Publik Pengecekan Status Seleksi PPDB Online
     */
    public function publicCheck(Request $request): Response
    {
        $regNumber = $request->input('registration_number');
        $birthDate = $request->input('birth_date');
        $result = null;
        $searched = false;

        if ($regNumber && $birthDate) {
            $searched = true;
            $reg = PpdbRegistration::with(['batch.academicYear', 'department', 'studentProfile.classroom'])
                ->where('registration_number', trim($regNumber))
                ->whereDate('birth_date', $birthDate)
                ->first();

            if ($reg) {
                $result = [
                    'id' => $reg->id,
                    'registration_number' => $reg->registration_number,
                    'full_name' => $reg->full_name,
                    'previous_school' => $reg->previous_school,
                    'track' => $reg->track,
                    'batch_name' => $reg->batch?->name,
                    'department' => $reg->department?->name ?? 'Umum',
                    'status' => $reg->status,
                    'status_label' => match ($reg->status) {
                        'pending' => 'Menunggu Verifikasi Berkas',
                        'verified' => 'Berkas Terverifikasi (Proses Seleksi)',
                        'rejected' => 'Berkas Ditolak / Tidak Lengkap',
                        'accepted' => 'SELAMAT! ANDA DINYATAKAN DITERIMA',
                        'not_accepted' => 'MOHON MAAF, BELUM LOLOS SELEKSI',
                        'enrolled' => 'RESMI TERDAFTAR SEBAGAI SISWA',
                        default => 'Menunggu Keputusan',
                    },
                    'rejection_reason' => $reg->rejection_reason,
                    'verification_notes' => $reg->verification_notes,
                    'announcement_date' => $reg->batch?->announcement_date?->translatedFormat('d F Y'),
                    'enrolled_classroom' => $reg->studentProfile?->classroom?->name,
                ];
            }
        }

        return Inertia::render('Ppdb/Public/CheckStatus', [
            'filters' => [
                'registration_number' => $regNumber ?? '',
                'birth_date' => $birthDate ?? '',
            ],
            'searched' => $searched,
            'result' => $result,
        ]);
    }

    // =========================================================================
    // SISI ADMINISTRATOR / PANITIA PPDB SEKOLAH
    // =========================================================================

    /**
     * Dashboard & Panel Manajemen PPDB Sekolah
     */
    public function adminIndex(Request $request): Response
    {
        $search = $request->input('search');
        $batchId = $request->input('batch_id');
        $status = $request->input('status');
        $track = $request->input('track');
        $departmentId = $request->input('department_id');

        $query = PpdbRegistration::with(['batch', 'department', 'secondDepartment', 'studentProfile.classroom']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('previous_school', 'like', "%{$search}%");
            });
        }

        if (! empty($batchId) && $batchId !== 'all') {
            $query->where('ppdb_batch_id', $batchId);
        }

        if (! empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($track) && $track !== 'all') {
            $query->where('track', $track);
        }

        if (! empty($departmentId) && $departmentId !== 'all') {
            $query->where('department_id', $departmentId);
        }

        $registrations = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $batches = PpdbBatch::with('academicYear')->orderBy('start_date', 'desc')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $classrooms = Classroom::with('department')->orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $stats = [
            'total' => PpdbRegistration::count(),
            'pending' => PpdbRegistration::where('status', 'pending')->count(),
            'verified' => PpdbRegistration::where('status', 'verified')->count(),
            'accepted' => PpdbRegistration::where('status', 'accepted')->count(),
            'rejected' => PpdbRegistration::where('status', 'rejected')->count(),
            'enrolled' => PpdbRegistration::where('status', 'enrolled')->count(),
        ];

        return Inertia::render('Ppdb/Admin/Index', [
            'registrations' => $registrations,
            'batches' => $batches,
            'departments' => $departments,
            'classrooms' => $classrooms,
            'academicYears' => $academicYears,
            'filters' => [
                'search' => $search ?? '',
                'batch_id' => $batchId ?? 'all',
                'status' => $status ?? 'all',
                'track' => $track ?? 'all',
                'department_id' => $departmentId ?? 'all',
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Tambah Gelombang Baru PPDB
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'announcement_date' => ['nullable', 'date'],
            'registration_fee' => ['nullable', 'numeric', 'min:0'],
            'quota' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        PpdbBatch::create([
            'academic_year_id' => $validated['academic_year_id'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'announcement_date' => $validated['announcement_date'] ?? null,
            'registration_fee' => $validated['registration_fee'] ?? 0,
            'quota' => $validated['quota'],
            'is_active' => $validated['is_active'] ?? true,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', "Gelombang pendaftaran '{$validated['name']}' berhasil dibuka.");
    }

    /**
     * Update Gelombang PPDB
     */
    public function updateBatch(Request $request, string|int $id): RedirectResponse
    {
        $batch = PpdbBatch::findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'announcement_date' => ['nullable', 'date'],
            'registration_fee' => ['nullable', 'numeric', 'min:0'],
            'quota' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $batch->update($validated);

        return back()->with('success', "Gelombang '{$batch->name}' berhasil diperbarui.");
    }

    /**
     * Hapus Gelombang PPDB
     */
    public function destroyBatch(string|int $id): RedirectResponse
    {
        $batch = PpdbBatch::findOrFail($id);
        $name = $batch->name;
        $batch->delete();

        return back()->with('success', "Gelombang '{$name}' berhasil dihapus.");
    }

    /**
     * Verifikasi Kelengkapan Berkas Pendaftaran
     */
    public function verifyRegistration(Request $request, string|int $id): RedirectResponse
    {
        $reg = PpdbRegistration::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
            'verification_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['status'] === 'rejected' && empty($validated['rejection_reason'])) {
            return back()->withErrors(['rejection_reason' => 'Alasan penolakan wajib diisi bila berkas ditolak.']);
        }

        $reg->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null,
            'verification_notes' => $validated['verification_notes'] ?? null,
            'verified_at' => Carbon::now(),
            'verified_by' => Auth::id(),
        ]);

        $msg = $validated['status'] === 'verified'
            ? "Berkas pendaftar {$reg->full_name} berhasil diverifikasi valid!"
            : "Berkas pendaftar {$reg->full_name} ditolak. Calon siswa dapat memperbaiki dokumen.";

        return back()->with('success', $msg);
    }

    /**
     * Putusan Kelulusan Seleksi (Diterima / Tidak Lolos)
     */
    public function decideSelection(Request $request, string|int $id): RedirectResponse
    {
        $reg = PpdbRegistration::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:accepted,not_accepted,pending'],
        ]);

        $reg->update(['status' => $validated['status']]);

        $statusText = match ($validated['status']) {
            'accepted' => 'DITERIMA',
            'not_accepted' => 'TIDAK LOLOS',
            default => 'MENUNGGU',
        };

        return back()->with('success', "Keputusan seleksi untuk {$reg->full_name} diubah menjadi: {$statusText}.");
    }

    /**
     * Konversi Otomatis Calon Siswa Diterima Menjadi Siswa Resmi Sekolah
     * Menghasilkan Akun User Login, Profil Siswa, dan Akun Dompet Tabungan Digital
     */
    public function enrollStudent(Request $request, string|int $id): RedirectResponse
    {
        $reg = PpdbRegistration::findOrFail($id);

        if ($reg->status === 'enrolled' && $reg->student_profile_id) {
            return back()->with('warning', "Calon siswa {$reg->full_name} sudah pernah dikonversi menjadi siswa resmi.");
        }

        $validated = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $rawPassword = $validated['password'] ?: 'siswa123';

        $credential = DB::transaction(function () use ($reg, $classroom, $rawPassword) {
            // Tentukan email login siswa baru
            $baseEmail = $reg->email;
            if (! $baseEmail) {
                $nisnPart = $reg->nisn ?: rand(10000000, 99999999);
                $baseEmail = "siswa.{$nisnPart}@sekolah.id";
            }

            // Hindari duplikasi email
            $finalEmail = $baseEmail;
            $counter = 1;
            while (User::where('email', $finalEmail)->exists()) {
                $finalEmail = Str::before($baseEmail, '@') . "{$counter}@" . Str::after($baseEmail, '@');
                $counter++;
            }

            // 1. Buat User Siswa
            $user = User::create([
                'tenant_id' => $reg->tenant_id,
                'name' => $reg->full_name,
                'email' => $finalEmail,
                'phone_number' => $reg->phone_number ?: $reg->parent_phone,
                'password' => Hash::make($rawPassword),
                'is_active' => true,
                'email_verified_at' => Carbon::now(),
            ]);

            $user->assignRole('siswa');

            // 2. Buat StudentProfile
            // Jika NISN belum ada, generate format sementara
            $nisn = $reg->nisn ?: (string) rand(1000000000, 9999999999);
            while (StudentProfile::where('nisn', $nisn)->exists()) {
                $nisn = (string) rand(1000000000, 9999999999);
            }

            $profile = StudentProfile::create([
                'tenant_id' => $reg->tenant_id,
                'user_id' => $user->id,
                'classroom_id' => $classroom->id,
                'nisn' => $nisn,
                'nik' => $reg->nik,
                'full_name' => $reg->full_name,
                'gender' => $reg->gender,
                'birth_place' => $reg->birth_place,
                'birth_date' => $reg->birth_date,
                'parent_name' => $reg->parent_name,
                'parent_phone_wa' => $reg->parent_phone,
                'address' => $reg->address,
            ]);

            // 3. Inisialisasi Dompet Tabungan Digital Siswa
            StudentWallet::firstOrCreate(
                ['student_profile_id' => $profile->id],
                [
                    'tenant_id' => $reg->tenant_id,
                    'account_number' => 'WAL-' . $profile->nisn,
                    'balance' => 0,
                    'daily_limit' => 50000,
                    'spent_today' => 0,
                    'pin' => Hash::make('123456'),
                    'is_active' => true,
                ]
            );

            // 4. Perbarui status registrasi PPDB
            $reg->update([
                'status' => 'enrolled',
                'student_profile_id' => $profile->id,
            ]);

            return [
                'name' => $reg->full_name,
                'email' => $user->email,
                'password' => $rawPassword,
                'nisn' => $profile->nisn,
                'classroom' => $classroom->name,
                'registration_number' => $reg->registration_number,
            ];
        });

        return back()->with('success', "Calon siswa {$reg->full_name} berhasil dikonversi menjadi Siswa Resmi di kelas {$classroom->name}!")
            ->with('enrolled_credential', $credential);
    }

    /**
     * Konversi Massal Calon Siswa Diterima ke Rombel Tertentu
     */
    public function batchEnrollStudents(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_ids' => ['required', 'array', 'min:1'],
            'registration_ids.*' => ['exists:ppdb_registrations,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
        ]);

        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $registrations = PpdbRegistration::whereIn('id', $validated['registration_ids'])
            ->where('status', 'accepted')
            ->get();

        if ($registrations->isEmpty()) {
            return back()->withErrors(['error' => 'Tidak ada calon siswa berstatus DITERIMA yang dapat dikonversi.']);
        }

        $enrolledCount = 0;
        foreach ($registrations as $reg) {
            DB::transaction(function () use ($reg, $classroom, &$enrolledCount) {
                $baseEmail = $reg->email ?: "siswa.{$reg->nisn}@sekolah.id";
                $finalEmail = $baseEmail;
                $counter = 1;
                while (User::where('email', $finalEmail)->exists()) {
                    $finalEmail = Str::before($baseEmail, '@') . "{$counter}@" . Str::after($baseEmail, '@');
                    $counter++;
                }

                $user = User::create([
                    'tenant_id' => $reg->tenant_id,
                    'name' => $reg->full_name,
                    'email' => $finalEmail,
                    'phone_number' => $reg->phone_number ?: $reg->parent_phone,
                    'password' => Hash::make('siswa123'),
                    'is_active' => true,
                    'email_verified_at' => Carbon::now(),
                ]);

                $user->assignRole('siswa');

                $nisn = $reg->nisn ?: (string) rand(1000000000, 9999999999);
                while (StudentProfile::where('nisn', $nisn)->exists()) {
                    $nisn = (string) rand(1000000000, 9999999999);
                }

                $profile = StudentProfile::create([
                    'tenant_id' => $reg->tenant_id,
                    'user_id' => $user->id,
                    'classroom_id' => $classroom->id,
                    'nisn' => $nisn,
                    'nik' => $reg->nik,
                    'full_name' => $reg->full_name,
                    'gender' => $reg->gender,
                    'birth_place' => $reg->birth_place,
                    'birth_date' => $reg->birth_date,
                    'parent_name' => $reg->parent_name,
                    'parent_phone_wa' => $reg->parent_phone,
                    'address' => $reg->address,
                ]);

                StudentWallet::firstOrCreate(
                    ['student_profile_id' => $profile->id],
                    [
                        'tenant_id' => $reg->tenant_id,
                        'account_number' => 'WAL-' . $profile->nisn,
                        'balance' => 0,
                        'daily_limit' => 50000,
                        'spent_today' => 0,
                        'pin' => Hash::make('123456'),
                        'is_active' => true,
                    ]
                );

                $reg->update([
                    'status' => 'enrolled',
                    'student_profile_id' => $profile->id,
                ]);

                $enrolledCount++;
            });
        }

        return back()->with('success', "Berhasil mengonversi {$enrolledCount} calon siswa ke kelas {$classroom->name}!");
    }

    /**
     * Ekspor Data Seluruh Pendaftar PPDB ke Berkas CSV
     */
    public function exportRegistrations(Request $request): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="data_pendaftar_ppdb_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () {
            $output = fopen('php://output', 'w');
            // Menulis header CSV
            fputcsv($output, [
                'No. Pendaftaran',
                'Gelombang',
                'Jalur',
                'Nama Lengkap',
                'JK',
                'NISN',
                'NIK',
                'Tempat Lahir',
                'Tanggal Lahir',
                'Asal Sekolah',
                'No. Telepon',
                'Email',
                'Alamat',
                'Jurusan Pilihan 1',
                'Jurusan Pilihan 2',
                'Rata Rapor',
                'Jarak (m)',
                'Nama Orang Tua',
                'No. Telepon Ortu',
                'Pekerjaan Ortu',
                'Status Seleksi',
                'Tanggal Daftar',
            ]);

            $registrations = PpdbRegistration::with(['batch', 'department', 'secondDepartment'])
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($registrations as $r) {
                fputcsv($output, [
                    $r->registration_number,
                    $r->batch?->name ?? '-',
                    strtoupper($r->track),
                    $r->full_name,
                    $r->gender,
                    $r->nisn ?? '-',
                    $r->nik ?? '-',
                    $r->birth_place ?? '-',
                    $r->birth_date?->format('Y-m-d') ?? '-',
                    $r->previous_school ?? '-',
                    $r->phone_number ?? '-',
                    $r->email ?? '-',
                    $r->address ?? '-',
                    $r->department?->name ?? 'Umum',
                    $r->secondDepartment?->name ?? '-',
                    $r->average_report_score ?? '-',
                    $r->distance_meters ?? '-',
                    $r->parent_name ?? '-',
                    $r->parent_phone ?? '-',
                    $r->parent_job ?? '-',
                    strtoupper($r->status),
                    $r->created_at?->format('Y-m-d H:i') ?? '-',
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }
}
