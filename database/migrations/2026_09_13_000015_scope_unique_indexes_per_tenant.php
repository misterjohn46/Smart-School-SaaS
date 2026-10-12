<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menjadikan batasan unique yang bersifat "per sekolah" menjadi komposit dengan
 * tenant_id.
 *
 * Tanpa ini, sekolah kedua tidak dapat memakai kode jurusan "RPL", NISN, atau
 * username yang sudah dipakai sekolah lain - padahal nilai-nilai tersebut hanya
 * perlu unik di dalam satu sekolah.
 *
 * CATATAN SENGAJA DIKECUALIKAN - `users.email` tetap unique global:
 * token reset password (`password_reset_tokens`) berkunci alamat email tanpa
 * tenant_id. Bila satu email boleh dipakai dua sekolah, token reset milik sekolah
 * A dapat dipakai akun sekolah B. Satu email = satu akun di seluruh platform.
 *
 * Identifier yang digenerate sistem (bill_number, payment_number,
 * transaction_number, order_number, device_token_hash, invoice_number, domain)
 * juga tetap unique global karena justru harus tidak pernah bertabrakan.
 */
return new class extends Migration
{
    /**
     * [tabel, kolom, nama index lama]
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    private const SCOPED_UNIQUES = [
        ['users', 'username', 'users_username_unique'],
        ['users', 'nisn', 'users_nisn_unique'],
        ['departments', 'code', 'departments_code_unique'],
        ['subjects', 'code', 'subjects_code_unique'],
        ['fee_categories', 'code', 'fee_categories_code_unique'],
        ['violation_categories', 'code', 'violation_categories_code_unique'],
        ['student_profiles', 'nisn', 'student_profiles_nisn_unique'],
        ['student_profiles', 'rfid_uid', 'student_profiles_rfid_uid_unique'],
        ['teacher_profiles', 'rfid_uid', 'teacher_profiles_rfid_uid_unique'],
        ['student_wallets', 'account_number', 'student_wallets_account_number_unique'],
    ];

    public function up(): void
    {
        foreach (self::SCOPED_UNIQUES as [$table, $column, $oldIndex]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($oldIndex) {
                $blueprint->dropUnique($oldIndex);
            });

            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                $blueprint->unique(['tenant_id', $column], "{$table}_tenant_{$column}_unique");
            });
        }
    }

    public function down(): void
    {
        foreach (self::SCOPED_UNIQUES as [$table, $column, $oldIndex]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                $blueprint->dropUnique("{$table}_tenant_{$column}_unique");
            });

            Schema::table($table, function (Blueprint $blueprint) use ($column, $oldIndex) {
                $blueprint->unique($column, $oldIndex);
            });
        }
    }
};
