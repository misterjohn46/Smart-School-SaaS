<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('school_geofences', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('radius_meters');
            $table->time('check_in_start')->default('06:00:00')->after('address');
            $table->time('check_in_end')->default('07:15:00')->after('check_in_start');
            $table->unsignedInteger('late_tolerance_minutes')->default(15)->after('check_in_end');
            $table->time('check_out_start')->default('15:00:00')->after('late_tolerance_minutes');
            $table->time('check_out_end')->default('18:00:00')->after('check_out_start');
            $table->json('work_days')->nullable()->after('check_out_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_geofences', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'check_in_start',
                'check_in_end',
                'late_tolerance_minutes',
                'check_out_start',
                'check_out_end',
                'work_days',
            ]);
        });
    }
};
