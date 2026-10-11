<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan token perangkat IoT sebagai hash SHA-256, bukan plaintext.
 *
 * Token plaintext hanya ditampilkan sekali saat perangkat dibuat / di-regenerate,
 * sehingga dump database atau backup tidak lagi membocorkan kredensial perangkat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_devices', function (Blueprint $table) {
            $table->string('device_token_hash', 64)->nullable()->after('id');
            $table->string('device_token_preview', 32)->nullable()->after('device_token_hash');
        });

        foreach (DB::table('attendance_devices')->select('id', 'device_token')->get() as $device) {
            $plain = (string) $device->device_token;

            DB::table('attendance_devices')->where('id', $device->id)->update([
                'device_token_hash' => hash('sha256', $plain),
                'device_token_preview' => mb_substr($plain, 0, 6).'••••',
            ]);
        }

        Schema::table('attendance_devices', function (Blueprint $table) {
            $table->dropUnique('attendance_devices_device_token_unique');
        });

        Schema::table('attendance_devices', function (Blueprint $table) {
            $table->dropColumn('device_token');
        });

        Schema::table('attendance_devices', function (Blueprint $table) {
            $table->unique('device_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_devices', function (Blueprint $table) {
            $table->string('device_token', 64)->nullable();
        });

        Schema::table('attendance_devices', function (Blueprint $table) {
            $table->dropUnique('attendance_devices_device_token_hash_unique');
            $table->dropColumn(['device_token_hash', 'device_token_preview']);
        });
    }
};
