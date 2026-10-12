<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\AuditTrail;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $loginType = $this->input('login_type', 'staff');

        if ($loginType === 'parent') {
            return [
                'login_type' => ['required', 'string', 'in:staff,student,parent'],
                'identifier' => ['required', 'string'], // NISN Siswa
                'birth_date' => ['required', 'string'], // Tanggal Lahir Siswa
            ];
        }

        return [
            'login_type' => ['nullable', 'string', 'in:staff,student,parent'],
            'identifier' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $loginType = $this->input('login_type', 'staff');
        $identifier = (string) ($this->input('identifier') ?: $this->input('email'));

        $this->ensureIsNotRateLimited($identifier);

        if ($loginType === 'parent') {
            $this->authenticateParent($identifier);

            return;
        }

        if ($loginType === 'student') {
            $this->authenticateStudent($identifier);

            return;
        }

        // Default: Staff / Admin / Guru / Umum
        $this->authenticateStaff($identifier);
    }

    protected function authenticateParent(string $nisn): void
    {
        $birthDateInput = (string) $this->input('birth_date');

        // Cari profil siswa berdasarkan NISN
        $studentProfile = StudentProfile::with('parentUser', 'user')->where('nisn', $nisn)->first();

        if (! $studentProfile) {
            $this->logAudit(null, 'login_parent', $nisn, 'failed', 'NISN siswa tidak terdaftar');
            RateLimiter::hit($this->throttleKey($nisn));

            throw ValidationException::withMessages([
                'identifier' => 'Data siswa dengan NISN tersebut tidak ditemukan.',
            ]);
        }

        // Validasi kecocokan tanggal lahir siswa
        try {
            $expectedBirth = Carbon::parse($studentProfile->birth_date)->format('Y-m-d');
            $providedBirth = Carbon::parse($birthDateInput)->format('Y-m-d');

            if ($expectedBirth !== $providedBirth) {
                $this->logAudit($studentProfile->parent_user_id, 'login_parent', $nisn, 'failed', 'Tanggal lahir siswa tidak sesuai');
                RateLimiter::hit($this->throttleKey($nisn));

                throw ValidationException::withMessages([
                    'birth_date' => 'Tanggal lahir siswa tidak sesuai dengan data terdaftar.',
                ]);
            }
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            throw ValidationException::withMessages([
                'birth_date' => 'Format tanggal lahir tidak valid. Gunakan format YYYY-MM-DD.',
            ]);
        }

        // Temukan akun orang tua
        $parentUser = $studentProfile->parentUser;

        if (! $parentUser) {
            $this->logAudit(null, 'login_parent', $nisn, 'failed', 'Akun orang tua belum terhubung');
            throw ValidationException::withMessages([
                'identifier' => 'Akun orang tua belum dihubungkan dengan siswa ini. Hubungi TU sekolah.',
            ]);
        }

        $this->validateUserStatusAndLogin($parentUser, $nisn, 'login_parent');
    }

    protected function authenticateStudent(string $identifier): void
    {
        // Siswa bisa login menggunakan NISN atau Username
        $user = User::where('nisn', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        $this->validateCredentialsAndLogin($user, $identifier, 'login_student');
    }

    protected function authenticateStaff(string $identifier): void
    {
        // Staf / Guru / Admin login menggunakan Email atau Username
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        $this->validateCredentialsAndLogin($user, $identifier, 'login_staff');
    }

    protected function validateCredentialsAndLogin(?User $user, string $identifier, string $action): void
    {
        $password = (string) $this->input('password');

        if (! $user) {
            $this->logAudit(null, $action, $identifier, 'failed', 'Pengguna tidak ditemukan');
            RateLimiter::hit($this->throttleKey($identifier));

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
            ]);
        }

        // Periksa apakah akun dikunci atau nonaktif
        $this->checkUserLockoutAndActive($user, $identifier, $action);

        if (! Hash::check($password, $user->password)) {
            $throttleKey = $this->throttleKey($identifier);
            RateLimiter::hit($throttleKey);
            $attempts = RateLimiter::attempts($throttleKey);

            if ($attempts >= 5) {
                $user->lockAccount('Gagal memasukkan password sebanyak 5 kali berturut-turut.', 30);
                $this->logAudit($user->id, $action, $identifier, 'locked', 'Akun dikunci karena 5 kali percobaan gagal');

                throw ValidationException::withMessages([
                    'identifier' => 'Akun Anda telah dikunci selama 30 menit karena 5 kali percobaan gagal. Hubungi Admin atau coba lagi nanti.',
                ]);
            }

            $remaining = 5 - $attempts;
            $this->logAudit($user->id, $action, $identifier, 'failed', 'Password salah');

            throw ValidationException::withMessages([
                'password' => "Password salah. Sisa kesempatan: {$remaining} kali sebelum akun dikunci.",
            ]);
        }

        $this->validateUserStatusAndLogin($user, $identifier, $action);
    }

    protected function checkUserLockoutAndActive(User $user, string $identifier, string $action): void
    {
        if (! $user->is_active) {
            $this->logAudit($user->id, $action, $identifier, 'disabled', 'Akun dinonaktifkan oleh administrator');

            throw ValidationException::withMessages([
                'identifier' => 'Akun Anda sedang dinonaktifkan oleh administrator sekolah.',
            ]);
        }

        if ($user->isLockedOut()) {
            $minutes = $user->lockout_until ? max(1, (int) ceil(now()->diffInMinutes($user->lockout_until, false))) : 30;
            $this->logAudit($user->id, $action, $identifier, 'locked', 'Percobaan login pada akun terkunci');

            throw ValidationException::withMessages([
                'identifier' => "Akun Anda sedang terkunci ({$user->lockout_reason}). Silakan tunggu {$minutes} menit atau hubungi Admin.",
            ]);
        }
    }

    protected function validateUserStatusAndLogin(User $user, string $identifier, string $action): void
    {
        $this->checkUserLockoutAndActive($user, $identifier, $action);

        RateLimiter::clear($this->throttleKey($identifier));

        // Jika sebelumnya terkunci tapi lockout_until sudah lewat, pastikan status bersih
        if ($user->is_locked) {
            $user->unlockAccount();
        }

        Auth::login($user, $this->boolean('remember'));

        $this->logAudit($user->id, $action, $identifier, 'success', null);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(string $identifier): void
    {
        $key = $this->throttleKey($identifier);

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'identifier' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(string $identifier): string
    {
        return Str::transliterate(Str::lower($identifier).'|'.$this->ip());
    }

    protected function logAudit(?int $userId, string $action, string $identifier, string $status, ?string $failureReason): void
    {
        AuditTrail::create([
            'user_id' => $userId,
            'action' => $action,
            'identifier_attempted' => $identifier,
            'ip_address' => (string) $this->ip(),
            'user_agent' => (string) $this->userAgent(),
            'status' => $status,
            'failure_reason' => $failureReason,
            'payload' => [
                'login_type' => $this->input('login_type', 'staff'),
                'time' => now()->toIso8601String(),
            ],
        ]);
    }
}
