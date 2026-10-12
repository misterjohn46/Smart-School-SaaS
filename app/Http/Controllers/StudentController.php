<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Models\User;
use App\Rules\TenantUnique;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * Tampilan Master Data Siswa & Rombel (Modern Vue 3 SPA)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $classroomId = $request->input('classroom_id');
        $gender = $request->input('gender');

        $query = StudentProfile::with(['user', 'classroom', 'wallet']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        if ($gender && in_array($gender, ['L', 'P'])) {
            $query->where('gender', $gender);
        }

        $students = $query->orderBy('full_name')->paginate(15)->withQueryString();

        $classrooms = Classroom::with('department')->orderBy('name')->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'department' => $c->department?->name ?? 'Umum',
        ]);

        $stats = [
            'total' => StudentProfile::count(),
            'male' => StudentProfile::where('gender', 'L')->count(),
            'female' => StudentProfile::where('gender', 'P')->count(),
            'classrooms_count' => Classroom::count(),
        ];

        return Inertia::render('Academic/Students/Index', [
            'students' => $students,
            'classrooms' => $classrooms,
            'filters' => [
                'search' => $search,
                'classroom_id' => $classroomId ? (int) $classroomId : null,
                'gender' => $gender,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Tambah Siswa Baru beserta Akun Login & Dompet Digital
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'nisn' => ['required', 'string', 'max:20', TenantUnique::make('student_profiles', 'nisn')],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'gender' => ['required', 'in:L,P'],
            'nik' => ['nullable', 'string', 'max:20'],
            'parent_name' => ['nullable', 'string', 'max:100'],
            'parent_phone_wa' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $rawPassword = $validated['password'] ?: 'siswa123';

        DB::transaction(function () use ($validated, $rawPassword) {
            // Akun diprovisikan petugas sekolah (bukan pendaftaran mandiri),
            // sehingga langsung dianggap terverifikasi. Banyak siswa juga tidak
            // memiliki akses ke kotak surat email yang didaftarkan sekolah.
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['parent_phone_wa'] ?? null,
                'password' => Hash::make($rawPassword),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('siswa');

            $profile = StudentProfile::create([
                'user_id' => $user->id,
                'classroom_id' => $validated['classroom_id'],
                'nisn' => $validated['nisn'],
                'nik' => $validated['nik'] ?? null,
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
                'parent_name' => $validated['parent_name'] ?? null,
                'parent_phone_wa' => $validated['parent_phone_wa'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            StudentWallet::firstOrCreate(
                ['student_profile_id' => $profile->id],
                [
                    'account_number' => 'WAL-'.($profile->nisn ?: (string) rand(10000000, 99999999)),
                    'balance' => 0,
                    'daily_limit' => 50000,
                    'spent_today' => 0,
                    'pin' => Hash::make('123456'),
                    'is_active' => true,
                ]
            );
        });

        return back()->with('status', "Siswa {$validated['full_name']} berhasil ditambahkan!")
            ->with('created_credential', [
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => $rawPassword,
                'nisn' => $validated['nisn'],
            ]);
    }

    /**
     * Perbarui Data Siswa
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $profile = StudentProfile::findOrFail($id);
        $user = $profile->user;

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'nisn' => ['required', 'string', 'max:20', TenantUnique::make('student_profiles', 'nisn', $profile->id)],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'gender' => ['required', 'in:L,P'],
            'nik' => ['nullable', 'string', 'max:20'],
            'parent_name' => ['nullable', 'string', 'max:100'],
            'parent_phone_wa' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        DB::transaction(function () use ($validated, $profile, $user) {
            $userPayload = [
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['parent_phone_wa'] ?? $user->phone_number,
            ];

            if (! empty($validated['password'])) {
                $userPayload['password'] = Hash::make($validated['password']);
            }

            $user->update($userPayload);

            $profile->update([
                'classroom_id' => $validated['classroom_id'],
                'nisn' => $validated['nisn'],
                'nik' => $validated['nik'] ?? null,
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
                'parent_name' => $validated['parent_name'] ?? null,
                'parent_phone_wa' => $validated['parent_phone_wa'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
        });

        return back()->with('status', "Data siswa {$validated['full_name']} berhasil diperbarui!");
    }

    /**
     * Hapus Siswa
     */
    public function destroy(int $id): RedirectResponse
    {
        $profile = StudentProfile::findOrFail($id);
        $name = $profile->full_name;

        // Menghapus user akan otomatis cascade menghapus profile & wallet
        $profile->user->delete();

        return back()->with('status', "Data siswa {$name} berhasil dihapus.");
    }

    /**
     * Reset Password Akun Siswa
     */
    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $profile = StudentProfile::with('user')->findOrFail($id);

        // Str::random() memakai random_bytes (CSPRNG). rand() sebelumnya hanya
        // menghasilkan 9.000 kemungkinan dan dapat diprediksi.
        $newPassword = 'siswa-'.Str::lower(Str::random(10));

        $profile->user->update([
            'password' => Hash::make($newPassword),
        ]);

        return back()->with('status', "Password akun siswa {$profile->full_name} berhasil direset!")
            ->with('reset_credential', [
                'name' => $profile->full_name,
                'email' => $profile->user->email,
                'password' => $newPassword,
            ]);
    }
}
