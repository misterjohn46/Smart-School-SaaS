<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-database multi-tenancy: menambahkan kolom `tenant_id` pada seluruh tabel
 * milik sekolah, sebagai dasar global scope Stancl\Tenancy\Database\TenantScope.
 *
 * Kolom dibuat NULLABLE dengan sengaja:
 *  - Data instalasi satu sekolah yang sudah ada tetap valid (tenant_id NULL) dan
 *    terus terlihat pada konteks central. Gunakan `php artisan tenants:adopt-data`
 *    untuk mengalihkannya ke satu tenant.
 *  - Akun Super Admin platform memang tidak dimiliki tenant mana pun.
 */
return new class extends Migration
{
    /**
     * Tabel yang datanya dimiliki satu sekolah.
     *
     * Tidak termasuk: tenants, domains, plans, subscriptions, invoices, cbt_nodes
     * (tabel platform/central) serta tabel bawaan framework (sessions, cache, jobs,
     * password_reset_tokens, personal_access_tokens).
     *
     * @var array<int, string>
     */
    private const TENANT_TABLES = [
        'users',
        'audit_trails',
        'academic_years',
        'grade_levels',
        'departments',
        'classrooms',
        'subjects',
        'teacher_profiles',
        'student_profiles',
        'attendance_devices',
        'school_geofences',
        'attendances',
        'attendance_leaves',
        'kts_templates',
        'fee_categories',
        'student_bills',
        'payments',
        'bill_payments',
        'student_wallets',
        'wallet_transactions',
        'canteen_stands',
        'canteen_menus',
        'canteen_orders',
        'canteen_order_items',
        'lesson_schedules',
        'lesson_attendances',
        'student_grades',
        'teaching_journals',
        'violation_categories',
        'student_violations',
        'student_report_remarks',
        'cbt_banks',
        'cbt_questions',
        'cbt_exams',
        'cbt_submissions',
    ];

    public function up(): void
    {
        foreach (self::TENANT_TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->string('tenant_id')->nullable()->index("{$table}_tenant_id_index");
            });
        }
    }

    public function down(): void
    {
        foreach (self::TENANT_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex("{$table}_tenant_id_index");
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};
