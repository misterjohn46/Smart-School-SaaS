<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_a_verified_administrator(): void
    {
        $this->artisan('app:create-admin', ['email' => 'owner@example.com'])
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Password', 'strong-password')
            ->expectsQuestion('Confirm password', 'strong-password')
            ->assertSuccessful();

        $admin = User::whereEmail('owner@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertNotNull($admin->email_verified_at);
    }
}
