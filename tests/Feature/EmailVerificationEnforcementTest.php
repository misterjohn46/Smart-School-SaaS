<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Verifikasi email ditegakkan.
 *
 * Sebelumnya App\Models\User tidak mengimplementasikan kontrak MustVerifyEmail,
 * sehingga middleware `verified` meloloskan semua pengguna dan halaman profil
 * selalu melaporkan mustVerifyEmail => false.
 */
class EmailVerificationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_implements_the_must_verify_email_contract(): void
    {
        $this->assertInstanceOf(MustVerifyEmail::class, new User);
    }

    public function test_unverified_user_is_redirected_away_from_the_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_may_open_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->hasVerifiedEmail());
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_self_registration_sends_a_verification_notification(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Pendaftar Mandiri',
            'email' => 'mandiri@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'mandiri@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_profile_page_reports_that_verification_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('mustVerifyEmail', true)
            );
    }

    public function test_api_protected_routes_reject_an_unverified_user(): void
    {
        $unverified = User::factory()->unverified()->create();
        $token = $unverified->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/user')->assertForbidden();
    }

    public function test_api_protected_routes_accept_a_verified_user(): void
    {
        $verified = User::factory()->create();
        $token = $verified->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.email', $verified->email);
    }

    public function test_accounts_provisioned_by_school_staff_are_pre_verified(): void
    {
        $this->artisan('db:seed-central');

        $admin = User::where('email', 'admin@sekolah.id')->firstOrFail();

        // Siswa dibuat petugas sekolah
        $this->actingAs($admin)->post('/academic/students', [
            'full_name' => 'Siswa Provisi',
            'email' => 'siswa.provisi@sekolah.id',
            'nisn' => '9900112233',
            'classroom_id' => Classroom::firstOrFail()->id,
            'gender' => 'L',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $student = StudentProfile::where('nisn', '9900112233')->firstOrFail();
        $this->assertTrue($student->user->hasVerifiedEmail());

        // Guru dibuat petugas sekolah
        $this->actingAs($admin)->post('/academic/teachers', [
            'full_name' => 'Guru Provisi',
            'email' => 'guru.provisi@sekolah.id',
            'gender' => 'P',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $teacher = TeacherProfile::where('full_name', 'Guru Provisi')->firstOrFail();
        $this->assertTrue($teacher->user->hasVerifiedEmail());
    }

    public function test_seeded_development_accounts_are_verified(): void
    {
        $this->artisan('db:seed-central');

        foreach (['superadmin@platform.id', 'admin@sekolah.id', 'guru@sekolah.id', 'siswa@sekolah.id'] as $email) {
            $this->assertTrue(
                User::where('email', $email)->firstOrFail()->hasVerifiedEmail(),
                "Akun seeder {$email} seharusnya terverifikasi."
            );
        }
    }
}
