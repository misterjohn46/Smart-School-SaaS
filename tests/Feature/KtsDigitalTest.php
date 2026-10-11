<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KtsDigitalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_student_can_preview_digital_kts_card(): void
    {
        $studentUser = User::where('email', 'siswa@sekolah.id')->first();
        $this->assertNotNull($studentUser);

        $studentProfile = StudentProfile::where('user_id', $studentUser->id)->first();
        $this->assertNotNull($studentProfile);

        $response = $this->actingAs($studentUser)->get("/kts/preview/{$studentProfile->hash_id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Attendance/KtsCardPreview')
            ->has('student')
            ->has('template')
            ->has('school')
            ->where('student.nisn', '0051234567')
        );
    }

    public function test_batch_print_kts_sheet_renders_successfully(): void
    {
        $admin = User::where('email', 'admin@sekolah.id')->first();

        $response = $this->actingAs($admin)->get('/kts/batch-print');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Attendance/KtsBatchPrint')
            ->has('students')
            ->has('classrooms')
        );
    }
}
