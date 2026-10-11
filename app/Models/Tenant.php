<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            // 1. Buat Akun Administrator Sekolah jika belum ada
            if (! empty($tenant->admin_email)) {
                $user = \App\Models\User::firstOrCreate(
                    ['email' => $tenant->admin_email],
                    [
                        'name' => $tenant->admin_name ?: 'Admin ' . $tenant->school_name,
                        'phone_number' => $tenant->admin_phone,
                        'password' => \Illuminate\Support\Facades\Hash::make('password'),
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]
                );

                if (! $user->hasRole('admin')) {
                    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
                    $user->assignRole($role);
                }
            }

            // 2. Buat Subdomain / Domain default jika belum ada
            if ($tenant->domains()->count() === 0) {
                $slug = \Illuminate\Support\Str::slug($tenant->npsn ?: $tenant->school_name);
                $host = request()?->getHost() ?: 'localhost';
                $domainName = $slug . '.' . $host;

                $tenant->domains()->create([
                    'domain' => $domainName,
                ]);
            }
        });
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
