<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

class SeedCentral extends Command
{
    protected $signature = 'db:seed-central';
    protected $description = 'Seed central database with SaaS plans, academic master data, and demo accounts';

    public function handle(): int
    {
        $this->info('Starting Central Database Seeding...');

        $seeder = new DatabaseSeeder();
        $seeder->run();

        $this->info('Central Database seeded successfully!');
        return self::SUCCESS;
    }
}
