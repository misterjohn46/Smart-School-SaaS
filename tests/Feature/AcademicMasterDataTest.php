<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\User;
use App\Services\HashIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_academic_master_data_seeded_properly(): void
    {
        $this->assertDatabaseHas('academic_years', [
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('departments', [
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->assertDatabaseHas('classrooms', [
            'name' => 'X - RPL 1',
        ]);

        $this->assertDatabaseHas('subjects', [
            'code' => 'RPL-01',
            'kkm' => 75,
        ]);
    }

    public function test_obfuscated_hash_id_service_and_models(): void
    {
        $classroom = Classroom::first();
        $this->assertNotNull($classroom);

        // Ambil obfuscated ID dari atribut model
        $hashId = $classroom->obfuscated_id;
        $this->assertIsString($hashId);
        $this->assertNotEmpty($hashId);

        // Uji decoding kembali ke ID asli integer
        $decodedId = HashIdService::decode($hashId);
        $this->assertEquals($classroom->id, $decodedId);

        // Uji pencarian model via Hash ID
        $foundClassroom = Classroom::findByHashId($hashId);
        $this->assertNotNull($foundClassroom);
        $this->assertEquals($classroom->id, $foundClassroom->id);
    }
}
