<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CanteenMenu;
use App\Models\CanteenOrder;
use App\Models\CanteenOrderItem;
use App\Models\CanteenStand;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CanteenService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Buat transaksi pesanan jajanan kantin sekolah.
     */
    public function createOrder(
        int $canteenStandId,
        int $buyerUserId,
        ?int $studentProfileId,
        array $items,
        string $paymentMethod = 'wallet',
        ?string $pin = null,
        ?string $notes = null
    ): CanteenOrder {
        if (empty($items)) {
            throw new Exception('Pesanan tidak boleh kosong.');
        }

        return DB::transaction(function () use (
            $canteenStandId,
            $buyerUserId,
            $studentProfileId,
            $items,
            $paymentMethod,
            $pin,
            $notes
        ) {
            $stand = CanteenStand::findOrFail($canteenStandId);
            if (! $stand->is_active) {
                throw new Exception("Stand kantin '{$stand->name}' sedang tutup.");
            }

            $orderNumber = 'ORD/'.date('Ymd').'/'.strtoupper(Str::random(6));
            $totalAmount = 0.0;
            $orderItemsData = [];

            foreach ($items as $item) {
                $menu = CanteenMenu::lockForUpdate()->findOrFail($item['menu_id']);

                if (! $menu->is_available) {
                    throw new Exception("Menu '{$menu->name}' saat ini tidak tersedia.");
                }

                $quantity = (int) $item['quantity'];
                if ($quantity <= 0) {
                    throw new Exception("Jumlah item untuk menu '{$menu->name}' tidak valid.");
                }

                if ($menu->stock < $quantity) {
                    throw new Exception("Stok untuk menu '{$menu->name}' tidak mencukupi (Tersisa: {$menu->stock}).");
                }

                $unitPrice = (float) $menu->price;
                $subtotal = $unitPrice * $quantity;
                $totalAmount += $subtotal;

                $orderItemsData[] = [
                    'menu' => $menu,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'special_request' => $item['special_request'] ?? null,
                ];
            }

            // Validasi & Eksekusi Pembayaran
            if ($paymentMethod === 'wallet') {
                if (! $studentProfileId) {
                    throw new Exception('Pembayaran via dompet KTS memerlukan profil siswa yang valid.');
                }
                if (empty($pin)) {
                    throw new Exception('PIN Dompet 6-digit wajib dimasukkan untuk otorisasi jajan.');
                }

                $wallet = $this->walletService->getOrCreateWallet($studentProfileId);
                $this->walletService->deductForCanteen(
                    $wallet->id,
                    $totalAmount,
                    $pin,
                    "Jajan di {$stand->name} (No. {$orderNumber})"
                );
            }

            // Simpan Data Pesanan
            $order = CanteenOrder::create([
                'order_number' => $orderNumber,
                'canteen_stand_id' => $stand->id,
                'student_profile_id' => $studentProfileId,
                'user_id' => $buyerUserId,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'order_status' => 'pending',
                'order_date' => now(),
                'notes' => $notes,
            ]);

            // Simpan Detail Menu & Kurangi Stok
            foreach ($orderItemsData as $itemData) {
                CanteenOrderItem::create([
                    'canteen_order_id' => $order->id,
                    'canteen_menu_id' => $itemData['menu']->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal'],
                    'special_request' => $itemData['special_request'],
                ]);

                $itemData['menu']->decrement('stock', $itemData['quantity']);
            }

            // Tambahkan omset stand kantin
            $stand->increment('total_revenue', $totalAmount);

            return $order->load(['items.menu', 'stand']);
        });
    }

    /**
     * Update status pesanan kantin (pending -> cooking -> ready -> completed / cancelled).
     */
    public function updateOrderStatus(int $orderId, string $status): CanteenOrder
    {
        $allowed = ['pending', 'cooking', 'ready', 'completed', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            throw new Exception("Status '{$status}' tidak dikenali.");
        }

        return DB::transaction(function () use ($orderId, $status) {
            $order = CanteenOrder::with(['items.menu', 'stand'])->lockForUpdate()->findOrFail($orderId);

            if ($order->order_status === $status) {
                return $order;
            }

            // Jika pesanan dibatalkan (cancelled) dan sebelumnya sudah lunas via wallet, kembalikan saldo & stok!
            if ($status === 'cancelled' && $order->payment_status === 'paid') {
                // Kembalikan stok
                foreach ($order->items as $item) {
                    $item->menu->increment('stock', $item->quantity);
                }

                // Kurangi omset stand
                $order->stand->decrement('total_revenue', (float) $order->total_amount);

                if ($order->payment_method === 'wallet' && $order->student_profile_id) {
                    $wallet = $this->walletService->getOrCreateWallet($order->student_profile_id);
                    $this->walletService->refund(
                        $wallet->id,
                        (float) $order->total_amount,
                        "Pengembalian dana pembatalan pesanan No. {$order->order_number}",
                        $order->id
                    );
                }

                $order->update([
                    'order_status' => 'cancelled',
                    'payment_status' => 'refunded',
                ]);

                return $order;
            }

            $order->update([
                'order_status' => $status,
            ]);

            return $order;
        });
    }
}
