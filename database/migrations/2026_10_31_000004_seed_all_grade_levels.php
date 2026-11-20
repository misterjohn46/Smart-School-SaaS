<?php

declare(strict_types=1);

use App\Models\GradeLevel;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $levels = [
            ['name' => 'Tingkat I', 'level_numeric' => 1],
            ['name' => 'Tingkat II', 'level_numeric' => 2],
            ['name' => 'Tingkat III', 'level_numeric' => 3],
            ['name' => 'Tingkat IV', 'level_numeric' => 4],
            ['name' => 'Tingkat V', 'level_numeric' => 5],
            ['name' => 'Tingkat VI', 'level_numeric' => 6],
            ['name' => 'Tingkat VII', 'level_numeric' => 7],
            ['name' => 'Tingkat VIII', 'level_numeric' => 8],
            ['name' => 'Tingkat IX', 'level_numeric' => 9],
            ['name' => 'Tingkat X', 'level_numeric' => 10],
            ['name' => 'Tingkat XI', 'level_numeric' => 11],
            ['name' => 'Tingkat XII', 'level_numeric' => 12],
        ];

        foreach ($levels as $level) {
            GradeLevel::firstOrCreate(
                ['level_numeric' => $level['level_numeric']],
                ['name' => $level['name']]
            );
        }
    }

    public function down(): void
    {
        // Keep existing records
    }
};
