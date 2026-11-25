<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TenantManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = Tenant::with(['domains']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('school_name', 'like', "%{$search}%")
                    ->orWhere('npsn', 'like', "%{$search}%")
                    ->orWhere('admin_email', 'like', "%{$search}%")
                    ->orWhere('admin_name', 'like', "%{$search}%");
            });
        }

        $tenants = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Agregasi Ringkasan Platform SaaS
        $now = now();
        $totalTenants = Tenant::count();
        $activeTrials = Tenant::whereNotNull('trial_ends_at')->where('trial_ends_at', '>', $now)->count();
        $expiredTrials = Tenant::whereNotNull('trial_ends_at')->where('trial_ends_at', '<=', $now)->count();

        return Inertia::render('Central/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'total_tenants' => $totalTenants,
                'active_trials' => $activeTrials,
                'expired_trials' => $expiredTrials,
            ],
            'flash' => [
                'credentials' => session('credentials'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'npsn' => ['required', 'string', 'max:20', 'unique:tenants,npsn'],
            'subdomain' => ['required', 'string', 'max:50', 'alpha_dash'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:100'],
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'trial_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $trialDays = (int) ($validated['trial_days'] ?? 14);

        $tenant = new Tenant([
            'school_name' => $validated['school_name'],
            'npsn' => $validated['npsn'],
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_phone' => $validated['admin_phone'] ?? null,
            'trial_ends_at' => now()->addDays($trialDays),
        ]);

        $centralDomain = config('tenancy.central_domains.0', 'localhost');
        $tenant->requestedDomain = strtolower($validated['subdomain']).'.'.$centralDomain;
        $tenant->save();

        $generatedPassword = $tenant->generatedAdminPassword ?? 'password';

        return redirect()->route('central.tenants.index')->with([
            'success' => "Sekolah {$tenant->school_name} berhasil didaftarkan!",
            'credentials' => [
                'school_name' => $tenant->school_name,
                'npsn' => $tenant->npsn,
                'admin_name' => $tenant->admin_name,
                'admin_email' => $tenant->admin_email,
                'admin_password' => $generatedPassword,
                'subdomain' => $tenant->domains()->first()?->domain ?? $tenant->requestedDomain,
                'login_url' => url('/login'),
            ],
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $tenant->update($validated);

        return redirect()->route('central.tenants.index')->with('success', "Data sekolah {$tenant->school_name} berhasil diperbarui.");
    }

    public function destroy(string $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);
        $schoolName = $tenant->school_name;

        // Hapus domain dan hapus record tenant
        $tenant->domains()->delete();
        $tenant->delete();

        return redirect()->route('central.tenants.index')->with('success', "Sekolah {$schoolName} berhasil dihapus dari sistem platform.");
    }

    public function syncAdmin(string $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);
        $role = Role::firstOrCreate(['name' => 'admin']);

        $user = User::withoutTenancy()
            ->where('tenant_id', $tenant->getTenantKey())
            ->where('email', $tenant->admin_email)
            ->first();

        $newPassword = Str::password(16, symbols: false);

        if (! $user) {
            $user = new User([
                'name' => $tenant->admin_name ?: 'Admin '.$tenant->school_name,
                'email' => $tenant->admin_email,
                'phone_number' => $tenant->admin_phone,
                'password' => Hash::make($newPassword),
                'is_active' => true,
            ]);
        } else {
            $user->fill([
                'name' => $tenant->admin_name ?: 'Admin '.$tenant->school_name,
                'phone_number' => $tenant->admin_phone,
                'password' => Hash::make($newPassword),
                'is_active' => true,
            ]);
        }

        $user->forceFill([
            'tenant_id' => $tenant->getTenantKey(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncRoles([$role]);

        return redirect()->route('central.tenants.index')->with([
            'success' => "Akun admin untuk {$tenant->school_name} berhasil disinkronkan!",
            'credentials' => [
                'school_name' => $tenant->school_name,
                'npsn' => $tenant->npsn,
                'admin_name' => $tenant->admin_name,
                'admin_email' => $tenant->admin_email,
                'admin_password' => $newPassword,
                'subdomain' => $tenant->domains()->first()?->domain ?? '-',
                'login_url' => url('/login'),
            ],
        ]);
    }
}
