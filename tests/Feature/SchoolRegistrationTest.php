<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_can_register_new_school_trial(): void
    {
        $plan = Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro Test',
                'price_monthly' => 999000,
                'price_yearly' => 9990000,
                'max_students' => 750,
                'max_teachers' => 75,
                'max_storage_mb' => 30720,
                'is_active' => true,
            ]
        );

        $payload = [
            'school_name' => 'SMA Negeri 1 Indonesia',
            'npsn' => '20199887',
            'subdomain' => 'sman1indo-'.uniqid(),
            'admin_name' => 'Bpk. Hendra, M.Kom',
            'admin_email' => 'admin@sman1indo.sch.id',
            'admin_phone' => '081299887766',
            'plan_slug' => $plan->slug,
        ];

        $response = $this->postJson('/register-school', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertTrue(Tenant::where('school_name', 'SMA Negeri 1 Indonesia')->exists());
        $this->assertDatabaseHas('subscriptions', [
            'status' => 'trial',
            'billing_cycle' => 'monthly',
        ]);
    }
}
