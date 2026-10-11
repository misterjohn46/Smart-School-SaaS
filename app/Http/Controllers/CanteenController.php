<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CanteenMenu;
use App\Models\CanteenOrder;
use App\Models\CanteenStand;
use App\Models\StudentProfile;
use App\Services\CanteenService;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CanteenController extends Controller
{
    public function __construct(
        protected CanteenService $canteenService,
        protected WalletService $walletService
    ) {}

    /**
     * Marketplace Jajanan E-Kantin Siswa & Guru.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $student = StudentProfile::with('wallet')
            ->where('user_id', $user->id)
            ->orWhere('parent_user_id', $user->id)
            ->first();

        $stands = CanteenStand::with(['menus' => function ($q) {
            $q->where('is_available', true);
        }])->where('is_active', true)->get();

        $today = now()->toDateString();
        $wallet = $student ? $this->walletService->getOrCreateWallet($student->id) : null;
        $spentToday = ($wallet && $wallet->last_spent_date?->toDateString() === $today) ? (float) $wallet->spent_today : 0.0;
        $dailyLimit = $wallet ? (float) $wallet->daily_limit : 50000.0;

        // Riwayat pesanan terakhir siswa
        $recentOrders = CanteenOrder::with(['stand', 'items.menu'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($ord) {
                return [
                    'id' => $ord->id,
                    'order_number' => $ord->order_number,
                    'stand_name' => $ord->stand?->name ?? 'Stand Kantin',
                    'total_amount' => (float) $ord->total_amount,
                    'order_status' => $ord->order_status,
                    'order_date' => $ord->order_date->format('d M Y, H:i'),
                    'items_count' => $ord->items->sum('quantity'),
                ];
            });

        return Inertia::render('Canteen/Index', [
            'stands' => $stands,
            'student' => $student ? [
                'id' => $student->id,
                'name' => $student->full_name,
                'nisn' => $student->nisn,
                'classroom' => $student->classroom?->name ?? '-',
            ] : null,
            'wallet' => $wallet ? [
                'balance' => (float) $wallet->balance,
                'daily_limit' => $dailyLimit,
                'spent_today' => $spentToday,
                'remaining_limit' => max(0, $dailyLimit - $spentToday),
            ] : null,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Layar POS Kasir Stand Kantin (Untuk Vendor Stand).
     */
    public function standPos(Request $request, int $standId): Response
    {
        $stand = CanteenStand::with(['menus' => function ($q) {
            $q->orderBy('name');
        }])->findOrFail($standId);

        $todayOrders = CanteenOrder::with(['items.menu', 'studentProfile.user'])
            ->where('canteen_stand_id', $stand->id)
            ->whereDate('order_date', now()->toDateString())
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'buyer_name' => $order->studentProfile?->full_name ?? $order->user?->name ?? 'Pembeli Umum',
                    'buyer_nisn' => $order->studentProfile?->nisn ?? '-',
                    'total_amount' => (float) $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'order_status' => $order->order_status,
                    'order_date' => $order->order_date->format('H:i:s'),
                    'items' => $order->items->map(fn ($item) => [
                        'name' => $item->menu?->name ?? 'Item',
                        'qty' => $item->quantity,
                        'subtotal' => (float) $item->subtotal,
                        'special_request' => $item->special_request,
                    ]),
                ];
            });

        return Inertia::render('Canteen/StandPos', [
            'stand' => [
                'id' => $stand->id,
                'name' => $stand->name,
                'stand_number' => $stand->stand_number,
                'is_active' => (bool) $stand->is_active,
                'total_revenue' => (float) $stand->total_revenue,
            ],
            'menus' => $stand->menus->map(fn ($menu) => [
                'id' => $menu->id,
                'name' => $menu->name,
                'category' => $menu->category,
                'price' => (float) $menu->price,
                'stock' => $menu->stock,
                'is_available' => (bool) $menu->is_available,
            ]),
            'todayOrders' => $todayOrders,
        ]);
    }

    /**
     * Buat Pesanan Jajanan Kantin Baru.
     */
    public function storeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'canteen_stand_id' => 'required|integer|exists:canteen_stands,id',
            'student_profile_id' => 'nullable|integer|exists:student_profiles,id',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|integer|exists:canteen_menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_request' => 'nullable|string|max:100',
            'payment_method' => 'required|in:wallet,cash',
            'pin' => 'nullable|string',
            'notes' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $studentProfileId = $validated['student_profile_id'] ?? null;

        // Jika tidak dikirim explicitly, coba ambil dari profil siswa yang login
        if (! $studentProfileId) {
            $student = StudentProfile::where('user_id', $user->id)->first();
            $studentProfileId = $student?->id;
        }

        try {
            $order = $this->canteenService->createOrder(
                (int) $validated['canteen_stand_id'],
                (int) $user->id,
                $studentProfileId ? (int) $studentProfileId : null,
                $validated['items'],
                $validated['payment_method'],
                $validated['pin'] ?? null,
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat! Silakan tunggu makanan disiapkan.',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => (float) $order->total_amount,
                    'order_status' => $order->order_status,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update Status Pesanan oleh Vendor (Cooking, Ready, Completed, Cancelled).
     */
    public function updateStatus(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,cooking,ready,completed,cancelled',
        ]);

        try {
            $order = $this->canteenService->updateOrderStatus($orderId, $validated['status']);

            return response()->json([
                'success' => true,
                'message' => "Status pesanan diubah menjadi: {$validated['status']}",
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => $order->order_status,
                    'payment_status' => $order->payment_status,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
