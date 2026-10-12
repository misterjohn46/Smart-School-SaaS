<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    /**
     * Tampilan Master Data Guru & Tenaga Kependidikan (PTK)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $gender = $request->input('gender');

        $query = TeacherProfile::with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nuptk', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($gender && in_array($gender, ['L', 'P'])) {
            $query->where('gender', $gender);
        }

        $teachers = $query->orderBy('full_name')->paginate(15)->withQueryString();

        $stats = [
            'total' => TeacherProfile::count(),
            'male' => TeacherProfile::where('gender', 'L')->count(),
            'female' => TeacherProfile::where('gender', 'P')->count(),
        ];

        return Inertia::render('Academic/Teachers/Index', [
            'teachers' => $teachers,
            'filters' => [
                'search' => $search,
                'gender' => $gender,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Tambah Guru / Staf Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'nip' => ['nullable', 'string', 'max:30', 'unique:teacher_profiles,nip'],
            'nuptk' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', 'in:L,P'],
            'phone_wa' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $rawPassword = $validated['password'] ?: 'guru123';

        DB::transaction(function () use ($validated, $rawPassword) {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_wa'] ?? null,
                'password' => Hash::make($rawPassword),
                'is_active' => true,
            ]);

            $user->assignRole('guru');

            TeacherProfile::create([
                'user_id' => $user->id,
                'nip' => $validated['nip'] ?? null,
                'nuptk' => $validated['nuptk'] ?? null,
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
                'phone_wa' => $validated['phone_wa'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
        });

        return back()->with('status', "Guru {$validated['full_name']} berhasil ditambahkan!")
            ->with('created_credential', [
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => $rawPassword,
                'nip' => $validated['nip'] ?? '-',
            ]);
    }

    /**
     * Perbarui Data Guru
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $profile = TeacherProfile::findOrFail($id);
        $user = $profile->user;

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'nip' => ['nullable', 'string', 'max:30', 'unique:teacher_profiles,nip,'.$profile->id],
            'nuptk' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', 'in:L,P'],
            'phone_wa' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        DB::transaction(function () use ($validated, $profile, $user) {
            $userPayload = [
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_wa'] ?? $user->phone_number,
            ];

            if (! empty($validated['password'])) {
                $userPayload['password'] = Hash::make($validated['password']);
            }

            $user->update($userPayload);

            $profile->update([
                'nip' => $validated['nip'] ?? null,
                'nuptk' => $validated['nuptk'] ?? null,
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
                'phone_wa' => $validated['phone_wa'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
        });

        return back()->with('status', "Data guru {$validated['full_name']} berhasil diperbarui!");
    }

    /**
     * Hapus Guru
     */
    public function destroy(int $id): RedirectResponse
    {
        $profile = TeacherProfile::findOrFail($id);
        $name = $profile->full_name;

        // Menghapus user akan otomatis cascade menghapus profile guru
        $profile->user->delete();

        return back()->with('status', "Data guru {$name} berhasil dihapus.");
    }

    /**
     * Reset Password Akun Guru
     */
    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $profile = TeacherProfile::with('user')->findOrFail($id);

        // Str::random() memakai random_bytes (CSPRNG). rand() sebelumnya hanya
        // menghasilkan 9.000 kemungkinan dan dapat diprediksi.
        $newPassword = 'guru-'.Str::lower(Str::random(10));

        $profile->user->update([
            'password' => Hash::make($newPassword),
        ]);

        return back()->with('status', "Password akun guru {$profile->full_name} berhasil direset!")
            ->with('reset_credential', [
                'name' => $profile->full_name,
                'email' => $profile->user->email,
                'password' => $newPassword,
            ]);
    }
}
