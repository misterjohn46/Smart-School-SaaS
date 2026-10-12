<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengalihkan data lama (tenant_id NULL) menjadi milik satu sekolah.
 *
 * Dibutuhkan saat instalasi yang sebelumnya melayani satu sekolah dipindahkan ke
 * arsitektur multi-tenant: seluruh baris lama masih bertenant_id NULL sehingga
 * hanya terlihat dari central domain, bukan dari subdomain sekolahnya.
 */
class AdoptDataIntoTenant extends Command
{
    protected $signature = 'tenants:adopt-data
                            {tenant : ID tenant (UUID) tujuan}
                            {--dry-run : Hanya tampilkan jumlah baris yang akan dialihkan}';

    protected $description = 'Alihkan data lama bertenant_id NULL menjadi milik satu sekolah';

    /**
     * Diambil dari migrasi 2026_09_13_000014_add_tenant_id_to_tenant_scoped_tables.
     *
     * @var array<int, string>
     */
    private const TENANT_TABLES = [
        'users', 'audit_trails', 'academic_years', 'grade_levels', 'departments',
        'classrooms', 'subjects', 'teacher_profiles', 'student_profiles',
        'attendance_devices', 'school_geofences', 'attendances', 'attendance_leaves',
        'kts_templates', 'fee_categories', 'student_bills', 'payments', 'bill_payments',
        'student_wallets', 'wallet_transactions', 'canteen_stands', 'canteen_menus',
        'canteen_orders', 'canteen_order_items', 'lesson_schedules', 'lesson_attendances',
        'student_grades', 'teaching_journals', 'violation_categories', 'student_violations',
        'student_report_remarks', 'cbt_banks', 'cbt_questions', 'cbt_exams', 'cbt_submissions',
    ];

    public function handle(): int
    {
        $tenantId = (string) $this->argument('tenant');
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->components->error("Tenant {$tenantId} tidak ditemukan.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $rows = [];
        $total = 0;

        foreach (self::TENANT_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $count = DB::table($table)->whereNull('tenant_id')->count();

            if ($count === 0) {
                continue;
            }

            $rows[] = [$table, $count];
            $total += $count;
        }

        if ($rows === []) {
            $this->components->info('Tidak ada data bertenant_id NULL. Tidak ada yang perlu dialihkan.');

            return self::SUCCESS;
        }

        $this->table(['Tabel', 'Baris'], $rows);
        $this->components->warn("Total {$total} baris akan dialihkan ke: {$tenant->school_name} ({$tenantId}).");

        if ($dryRun) {
            $this->components->info('Mode --dry-run: tidak ada perubahan yang ditulis.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Lanjutkan? Operasi ini mengubah data secara permanen.', false)) {
            $this->components->info('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $tenantId): void {
            foreach ($rows as [$table, $count]) {
                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
                $this->components->twoColumnDetail($table, "{$count} baris");
            }
        });

        $this->components->info("Selesai. {$total} baris kini milik {$tenant->school_name}.");
        $this->components->warn('Akun Super Admin platform ikut teralihkan bila sebelumnya bertenant_id NULL. Periksa kembali dan kosongkan tenant_id-nya bila perlu.');

        return self::SUCCESS;
    }
}
