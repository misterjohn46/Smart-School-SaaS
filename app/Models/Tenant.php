<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $guarded = [];

    /**
     * Domain yang diminta pendaftar. Diisi sebelum create() agar hook `created`
     * tidak membuat domain turunan NPSN sebagai domain kedua yang tidak dipakai.
     */
    public ?string $requestedDomain = null;

    /**
     * Password awal akun admin sekolah hasil provisioning.
     * Hanya tersedia sekali pada instance yang membuat tenant, tidak pernah disimpan.
     */
    public ?string $generatedAdminPassword = null;

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            // 1. Buat Akun Administrator Sekolah jika belum ada
            if (! empty($tenant->admin_email)) {
                // Password awal dibuat acak dengan CSPRNG. Sebelumnya setiap sekolah
                // baru mendapat password literal 'password' yang sama.
                $initialPassword = Str::password(16, symbols: false);

                $user = User::firstOrCreate(
                    ['email' => $tenant->admin_email],
                    [
                        'name' => $tenant->admin_name ?: 'Admin '.$tenant->school_name,
                        'phone_number' => $tenant->admin_phone,
                        'password' => Hash::make($initialPassword),
                        'is_active' => true,
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    $tenant->generatedAdminPassword = $initialPassword;
                }

                if (! $user->hasRole('admin')) {
                    $role = Role::firstOrCreate(['name' => 'admin']);
                    $user->assignRole($role);
                }
            }

            // 2. Buat Subdomain / Domain default jika belum ada
            if ($tenant->domains()->count() === 0) {
                $tenant->domains()->create([
                    'domain' => $tenant->requestedDomain ?: $tenant->defaultDomainName(),
                ]);
            }
        });
    }

    /**
     * Domain turunan NPSN / nama sekolah sebagai cadangan bila pendaftar
     * tidak menentukan subdomain.
     */
    public function defaultDomainName(): string
    {
        $slug = Str::slug($this->npsn ?: $this->school_name);
        $host = request()?->getHost() ?: (string) (config('tenancy.central_domains')[1] ?? 'localhost');

        return $slug.'.'.$host;
    }

    /**
     * Custom virtual columns.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'school_name',
            'npsn',
            'admin_name',
            'admin_email',
            'admin_phone',
            'plan_id',
            'trial_ends_at',
            'created_at',
            'updated_at',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tenant_id')
            ->latestOfMany();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id');
    }
}
