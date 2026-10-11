<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\CanteenMenu;
use App\Models\CanteenStand;
use App\Models\FeeCategory;
use App\Models\StudentBill;
use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FinanceAndCanteenSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
        $adminUser = User::where('email', 'admin@sekolah.id')->first() ?? User::first();

        // 1. Kategori Biaya Sekolah
        $feeSpp = FeeCategory::firstOrCreate(
            ['code' => 'SPP-X'],
            [
                'name' => 'SPP Bulanan Kelas X',
                'description' => 'Iuran pembinaan pendidikan bulanan tahun ajaran 2024/2025',
                'is_recurring' => true,
                'default_amount' => 350000,
                'academic_year_id' => $academicYear?->id,
                'is_active' => true,
            ]
        );

        $feeGedung = FeeCategory::firstOrCreate(
            ['code' => 'DSP-SARPRAS'],
            [
                'name' => 'Uang Gedung & Sarana Prasarana (DSP)',
                'description' => 'Sumbangan pengembangan sarana dan prasarana lab kejuruan',
                'is_recurring' => false,
                'default_amount' => 1500000,
                'academic_year_id' => $academicYear?->id,
                'is_active' => true,
            ]
        );

        $feeSeragam = FeeCategory::firstOrCreate(
            ['code' => 'SERAGAM-X'],
            [
                'name' => 'Paket Seragam & Atribut KTS Siswa',
                'description' => 'Seragam batik, wearpack lab kejuruan, dan kartu pelajar RFID ISO CR-80',
                'is_recurring' => false,
                'default_amount' => 650000,
                'academic_year_id' => $academicYear?->id,
                'is_active' => true,
            ]
        );

        // 2. Data Siswa Demo & Rekening Dompet Tabungan
        $student = StudentProfile::where('nisn', '0051234567')->first() ?? StudentProfile::first();

        if ($student) {
            $wallet = StudentWallet::firstOrCreate(
                ['student_profile_id' => $student->id],
                [
                    'account_number' => 'WAL-' . $student->nisn,
                    'balance' => 125000,
                    'daily_limit' => 50000,
                    'spent_today' => 0,
                    'last_spent_date' => now()->toDateString(),
                    'pin' => Hash::make('123456'),
                    'is_active' => true,
                ]
            );

            // Seed sample initial transaction jika belum ada
            if ($wallet->transactions()->count() === 0) {
                WalletTransaction::create([
                    'student_wallet_id' => $wallet->id,
                    'transaction_number' => 'TRX/TOP/' . date('Ymd') . '/' . strtoupper(Str::random(6)),
                    'type' => 'topup',
                    'amount' => 125000,
                    'balance_before' => 0,
                    'balance_after' => 125000,
                    'cashier_user_id' => $adminUser?->id,
                    'description' => 'Setor Awal Pembukaan Tabungan Siswa',
                    'status' => 'success',
                ]);
            }

            // Seed Tagihan Siswa
            StudentBill::firstOrCreate(
                [
                    'student_profile_id' => $student->id,
                    'fee_category_id' => $feeSpp->id,
                    'year' => 2026,
                    'month' => 9,
                ],
                [
                    'academic_year_id' => $academicYear?->id,
                    'bill_number' => 'INV/202609/' . strtoupper(Str::random(6)),
                    'title' => 'SPP Bulan September 2026',
                    'amount' => 350000,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'due_date' => now()->addDays(10)->toDateString(),
                ]
            );

            StudentBill::firstOrCreate(
                [
                    'student_profile_id' => $student->id,
                    'fee_category_id' => $feeSpp->id,
                    'year' => 2026,
                    'month' => 8,
                ],
                [
                    'academic_year_id' => $academicYear?->id,
                    'bill_number' => 'INV/202608/' . strtoupper(Str::random(6)),
                    'title' => 'SPP Bulan Agustus 2026',
                    'amount' => 350000,
                    'paid_amount' => 350000,
                    'status' => 'paid',
                    'due_date' => now()->subDays(20)->toDateString(),
                ]
            );

            StudentBill::firstOrCreate(
                [
                    'student_profile_id' => $student->id,
                    'fee_category_id' => $feeSeragam->id,
                    'year' => 2026,
                    'month' => null,
                ],
                [
                    'academic_year_id' => $academicYear?->id,
                    'bill_number' => 'INV/202607/' . strtoupper(Str::random(6)),
                    'title' => 'Paket Seragam & Atribut KTS Siswa',
                    'amount' => 650000,
                    'paid_amount' => 650000,
                    'status' => 'paid',
                    'due_date' => now()->subDays(45)->toDateString(),
                ]
            );
        }

        // 3. Stand Kantin Sekolah & Menu Makanan/Minuman
        $stand = CanteenStand::firstOrCreate(
            ['name' => 'Kantin Barokah Sehat (Stand 01)'],
            [
                'stand_number' => 'Stand 01',
                'user_id' => $adminUser->id,
                'phone' => '081298765432',
                'description' => 'Menyediakan aneka hidangan nasi, lauk pauk higienis, dan minuman segar berstandar BPOM',
                'is_active' => true,
                'total_revenue' => 450000,
            ]
        );

        $menus = [
            [
                'name' => 'Nasi Goreng Spesial Telur',
                'category' => 'food',
                'price' => 15000,
                'cost_price' => 10000,
                'stock' => 40,
                'description' => 'Nasi goreng bumbu rempah harum dengan telur mata sapi dan acar segar',
            ],
            [
                'name' => 'Ayam Geprek Sambal Bawang + Nasi',
                'category' => 'food',
                'price' => 18000,
                'cost_price' => 12000,
                'stock' => 35,
                'description' => 'Ayam krispi gurih digeprek dengan sambal bawang pedas nikmat',
            ],
            [
                'name' => 'Mie Ayam Pangsit Bakso',
                'category' => 'food',
                'price' => 15000,
                'cost_price' => 9000,
                'stock' => 25,
                'description' => 'Mie kenyal gurih dengan topping daging ayam cincang dan kuah kaldu sapi',
            ],
            [
                'name' => 'Es Teh Lemon Manis Dingin',
                'category' => 'drink',
                'price' => 5000,
                'cost_price' => 2000,
                'stock' => 80,
                'description' => 'Teh melati wangi berpadu dengan kesegaran perasan lemon asli',
            ],
            [
                'name' => 'Jus Alpukat Susu Kental',
                'category' => 'drink',
                'price' => 10000,
                'cost_price' => 6000,
                'stock' => 30,
                'description' => 'Jus buah alpukat mentega kental dengan susu cokelat manis',
            ],
            [
                'name' => 'Roti Bakar Coklat Keju',
                'category' => 'snack',
                'price' => 8000,
                'cost_price' => 4500,
                'stock' => 50,
                'description' => 'Roti panggang mentega dengan taburan melimpah keju cheddar dan meises',
            ],
            [
                'name' => 'Tahu Krispi Cabe Rawit',
                'category' => 'snack',
                'price' => 5000,
                'cost_price' => 2500,
                'stock' => 60,
                'description' => 'Tahu goreng renyah bumbu gurih lengkap dengan cabai rawit hijau',
            ],
        ];

        foreach ($menus as $m) {
            CanteenMenu::firstOrCreate(
                [
                    'canteen_stand_id' => $stand->id,
                    'name' => $m['name'],
                ],
                [
                    'category' => $m['category'],
                    'price' => $m['price'],
                    'cost_price' => $m['cost_price'],
                    'stock' => $m['stock'],
                    'is_available' => true,
                    'description' => $m['description'],
                ]
            );
        }
    }
}
