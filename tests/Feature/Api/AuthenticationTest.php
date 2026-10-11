<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_errors_use_the_standard_response_shape(): void
    {
        $this->getJson('/api/v1/missing')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'data' => null,
                'message' => 'Resource not found',
            ]);

        $this->getJson('/api/v1/user')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
    }

    public function test_user_can_register_and_receives_default_role_and_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'api@example.com')
            ->assertJsonPath('data.user.roles.0', 'user')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertTrue(User::whereEmail('api@example.com')->firstOrFail()->hasRole('user'));
    }

    public function test_user_can_login_and_previous_tokens_are_revoked(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('old-token')->plainTextToken;

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', $oldToken),
        ]);
        $this->assertCount(1, $user->fresh()->tokens);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_login_is_rate_limited_after_repeated_attempts(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'email' => 'limited@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/login', [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', $token),
        ]);
    }
}
