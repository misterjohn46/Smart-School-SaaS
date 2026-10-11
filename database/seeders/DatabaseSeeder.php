<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Paket Langganan SaaS Platform
        $this->call(PlanSeeder::class);

        // 2. Seed Master Data Akademik & 11 Role Spatie
        $this->call(AcademicMasterSeeder::class);

        // 3. Seed Keuangan, Tabungan Siswa, dan E-Kantin Sekolah
        $this->call(FinanceAndCanteenSeeder::class);
    }
}
