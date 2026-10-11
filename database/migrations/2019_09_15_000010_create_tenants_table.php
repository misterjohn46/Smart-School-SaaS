<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('school_name')->nullable();
            $table->string('npsn', 20)->nullable();
            $table->string('admin_name')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('admin_phone', 30)->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
