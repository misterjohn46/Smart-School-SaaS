<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CanteenMenu;
use App\Models\CanteenOrder;
use App\Models\CanteenStand;
use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Services\CanteenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanteenOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');
    }

    public function test_student_can_order_canteen_menus_with_wallet(): void
    {
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $stand = CanteenStand::first();
        $menu = CanteenMenu::where('canteen_stand_id', $stand->id)->first();
        $wallet = StudentWallet::where('student_profile_id', $student->id)->first();

        $initialBalance = (float) $wallet->balance;
        $initialStock = $menu->stock;
        $orderQty = 2;
        $expectedTotal = (float) $menu->price * $orderQty;

        $response = $this->actingAs($student->user)->postJson('/canteen/order', [
            'canteen_stand_id' => $stand->id,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => $orderQty,
                    'special_request' => 'Pedas sedang',
                ],
            ],
            'payment_method' => 'wallet',
            'pin' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verifikasi saldo terpotong tepat
        $this->assertEquals($initialBalance - $expectedTotal, (float) $wallet->fresh()->balance);

        // Verifikasi stok menu berkurang tepat
        $this->assertEquals($initialStock - $orderQty, $menu->fresh()->stock);

        // Verifikasi order tersimpan
        $order = CanteenOrder::where('canteen_stand_id', $stand->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('pending', $order->order_status);
    }

    public function test_order_fails_if_stock_is_insufficient(): void
    {
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $stand = CanteenStand::first();
        $menu = CanteenMenu::where('canteen_stand_id', $stand->id)->first();

        // Buat stok menu hanya tersisa 1
        $menu->update(['stock' => 1]);

        $response = $this->actingAs($student->user)->postJson('/canteen/order', [
            'canteen_stand_id' => $stand->id,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 5, // melebihi stok
                ],
            ],
            'payment_method' => 'wallet',
            'pin' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_cancelled_canteen_order_refunds_wallet_and_restores_stock(): void
    {
        $student = StudentProfile::where('nisn', '0051234567')->first();
        $stand = CanteenStand::first();
        $menu = CanteenMenu::where('canteen_stand_id', $stand->id)->first();
        $wallet = StudentWallet::where('student_profile_id', $student->id)->first();

        $canteenService = app(CanteenService::class);

        $initialBalance = (float) $wallet->balance;
        $initialStock = $menu->stock;

        // Buat order
        $order = $canteenService->createOrder(
            $stand->id,
            $student->user_id,
            $student->id,
            [['menu_id' => $menu->id, 'quantity' => 1]],
            'wallet',
            '123456'
        );

        $this->assertEquals($initialStock - 1, $menu->fresh()->stock);
        $this->assertEquals($initialBalance - (float) $menu->price, (float) $wallet->fresh()->balance);

        // Batalkan pesanan (misal stok habis mendadak di dapur)
        $cancelledOrder = $canteenService->updateOrderStatus($order->id, 'cancelled');

        $this->assertEquals('cancelled', $cancelledOrder->order_status);
        $this->assertEquals('refunded', $cancelledOrder->payment_status);

        // Pastikan stok kembali dan saldo ter-refund ke dompet siswa!
        $this->assertEquals($initialStock, $menu->fresh()->stock);
        $this->assertEquals($initialBalance, (float) $wallet->fresh()->balance);
    }
}
