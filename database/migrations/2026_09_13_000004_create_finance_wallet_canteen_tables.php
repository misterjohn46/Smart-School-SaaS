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
        // 1. Kategori Biaya Sekolah (SPP, DSP, Ujian, dsb.)
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(true); // Tagihan berulang bulanan vs insidental
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tagihan Siswa (Student Invoices)
        Schema::create('student_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('fee_category_id')->constrained('fee_categories')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('bill_number', 64)->unique();
            $table->string('title', 150);
            $table->unsignedTinyInteger('month')->nullable(); // 1 - 12 (Jan - Des)
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'waived'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_profile_id', 'status']);
        });

        // 3. Transaksi Pembayaran Masuk (Kasir / Transfer / E-Wallet)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 64)->unique();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // User kasir atau pembayar
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'wallet', 'qris'])->default('cash');
            $table->dateTime('payment_date');
            $table->string('reference_number', 100)->nullable();
            $table->string('proof_document')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('verified');
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_profile_id', 'status']);
        });

        // 4. Alokasi Pembayaran ke Tagihan (Pivot / Settlement)
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('student_bill_id')->constrained('student_bills')->cascadeOnDelete();
            $table->decimal('amount_allocated', 12, 2);
            $table->timestamps();
        });

        // 5. Rekening Tabungan & Dompet Digital Siswa (E-Wallet)
        Schema::create('student_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->unique()->constrained('student_profiles')->cascadeOnDelete();
            $table->string('account_number', 50)->unique();
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('daily_limit', 12, 2)->default(50000);
            $table->decimal('spent_today', 12, 2)->default(0);
            $table->date('last_spent_date')->nullable();
            $table->string('pin', 255); // 6-digit hashed bcrypt PIN
            $table->boolean('is_active')->default(true);
            $table->string('locked_reason')->nullable();
            $table->timestamps();
        });

        // 6. Mutasi Transaksi Dompet Digital (Ledger)
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_wallet_id')->constrained('student_wallets')->cascadeOnDelete();
            $table->string('transaction_number', 64)->unique();
            $table->enum('type', ['topup', 'withdrawal', 'purchase', 'bill_payment', 'refund']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->foreignId('cashier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description', 255);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('status', ['success', 'failed', 'reversed'])->default('success');
            $table->timestamps();

            $table->index(['student_wallet_id', 'type']);
        });

        // 7. Stand / Tenant Kantin Sekolah
        Schema::create('canteen_stands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Vendor / Penjual Stand
            $table->string('name', 100);
            $table->string('stand_number', 20);
            $table->string('phone', 30)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->timestamps();
        });

        // 8. Menu Makanan & Minuman Kantin
        Schema::create('canteen_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canteen_stand_id')->constrained('canteen_stands')->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('category', ['food', 'drink', 'snack', 'dessert'])->default('food');
            $table->decimal('price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->integer('stock')->default(50);
            $table->boolean('is_available')->default(true);
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 9. Pesanan Transaksi Kantin (Canteen Orders)
        Schema::create('canteen_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 64)->unique();
            $table->foreignId('canteen_stand_id')->constrained('canteen_stands')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Pemesan
            $table->decimal('total_amount', 12, 2);
            $table->enum('payment_method', ['wallet', 'cash'])->default('wallet');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('paid');
            $table->enum('order_status', ['pending', 'cooking', 'ready', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('order_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['canteen_stand_id', 'order_status']);
        });

        // 10. Detail Item Pesanan Kantin
        Schema::create('canteen_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canteen_order_id')->constrained('canteen_orders')->cascadeOnDelete();
            $table->foreignId('canteen_menu_id')->constrained('canteen_menus')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->string('special_request', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canteen_order_items');
        Schema::dropIfExists('canteen_orders');
        Schema::dropIfExists('canteen_menus');
        Schema::dropIfExists('canteen_stands');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('student_wallets');
        Schema::dropIfExists('bill_payments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('student_bills');
        Schema::dropIfExists('fee_categories');
    }
};
