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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_monthly')->default(0);
            $table->unsignedBigInteger('price_yearly')->default(0);
            $table->unsignedInteger('max_students')->default(200);
            $table->unsignedInteger('max_teachers')->default(30);
            $table->unsignedInteger('max_storage_mb')->default(10240); // 10 GB
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('status')->default('trial'); // trial, active, past_due, cancelled, expired
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('tenant_id');
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('status')->default('unpaid'); // unpaid, paid, expired, failed
            $table->string('payment_gateway')->nullable(); // midtrans, xendit, manual
            $table->string('payment_method')->nullable(); // qris, va_bca, etc.
            $table->string('payment_reference')->nullable();
            $table->text('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('cbt_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address')->nullable();
            $table->string('api_domain');
            $table->string('api_token');
            $table->unsignedInteger('max_concurrent_students')->default(1000);
            $table->unsignedInteger('current_active_students')->default(0);
            $table->string('status')->default('idle'); // idle, active, maintenance, offline
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbt_nodes');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
