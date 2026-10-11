<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolRegistrationController extends Controller
{
    /**
     * Mendaftarkan sekolah baru ke platform SaaS (Onboarding).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'npsn' => ['required', 'string', 'max:20'],
            'subdomain' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9\-]+$/',
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_phone' => ['required', 'string', 'max:25'],
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
        ], [
            'subdomain.regex' => 'Subdomain hanya boleh berisi huruf kecil, angka, dan tanda hubung (-).',
            'subdomain.min' => 'Subdomain minimal terdiri dari 3 karakter.',
            'plan_slug.exists' => 'Paket langganan yang dipilih tidak valid.',
        ]);

        $plan = Plan::where('slug', $validated['plan_slug'])->firstOrFail();

        $subdomain = Str::lower($validated['subdomain']);
        $host = $request->getHost();
        
        // Bentuk domain penuh (misal: sman1.localhost atau sman1.sekolahku.id)
        $domainName = $subdomain . '.' . $host;

        // Cek apakah domain sudah digunakan
        if (DB::table('domains')->where('domain', $domainName)->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Subdomain '{$subdomain}' sudah digunakan oleh sekolah lain. Silakan pilih subdomain lain.",
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Buat Tenant
            $tenantId = (string) Str::uuid();
            $tenant = Tenant::create([
                'id' => $tenantId,
                'school_name' => $validated['school_name'],
                'npsn' => $validated['npsn'],
                'admin_name' => $validated['admin_name'],
                'admin_email' => $validated['admin_email'],
                'admin_phone' => $validated['admin_phone'],
                'plan_id' => $plan->id,
                'trial_ends_at' => now()->addDays(14)->toDateTimeString(),
            ]);

            // 2. Buat Domain
            $tenant->domains()->create([
                'domain' => $domainName,
            ]);

            // 3. Buat Data Langganan Uji Coba (Free Trial 14 Hari)
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'billing_cycle' => 'monthly',
                'trial_ends_at' => now()->addDays(14),
                'starts_at' => now(),
                'ends_at' => now()->addDays(14),
            ]);

            DB::commit();

            $protocol = $request->secure() ? 'https://' : 'http://';
            $port = $request->getPort();
            $portSuffix = ($port && !in_array($port, [80, 443])) ? ':' . $port : '';
            $tenantUrl = $protocol . $domainName . $portSuffix;

            return response()->json([
                'success' => true,
                'message' => "Sekolah {$validated['school_name']} berhasil didaftarkan! Subdomain Anda telah aktif.",
                'data' => [
                    'school_name' => $validated['school_name'],
                    'subdomain' => $domainName,
                    'portal_url' => $tenantUrl,
                    'plan' => $plan->name,
                    'trial_days' => 14,
                    'admin_name' => $validated['admin_name'],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat portal sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }
}
