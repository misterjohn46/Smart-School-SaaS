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
use Illuminate\Http\RedirectResponse;
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
     * Marketplace Jajanan E-Kantin Siswa & Guru + Manajemen Stand & Menu (Admin/Vendor).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canManage = (bool) $user?->hasAnyRole(['admin', 'kepala_sekolah', 'kantin', 'vendor_kantin', 'kasir_kantin']);

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

        $allStands = [];
        $canteenStats = [
            'total_stands' => 0,
            'active_stands' => 0,
            'total_menus' => 0,
            'total_revenue' => 0.0,
        ];

        if ($canManage) {
            $allStands = CanteenStand::with(['user', 'menus' => function ($q) {
                $q->orderBy('name');
            }])
                ->withCount('orders')
                ->orderBy('stand_number')
                ->get()
                ->map(fn (CanteenStand $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'stand_number' => $s->stand_number,
                    'phone' => $s->phone,
                    'description' => $s->description,
                    'is_active' => (bool) $s->is_active,
                    'total_revenue' => (float) $s->total_revenue,
                    'orders_count' => $s->orders_count,
                    'vendor_name' => $s->user?->name ?? 'Pengelola Stand',
                    'menus' => $s->menus->map(fn (CanteenMenu $m) => [
                        'id' => $m->id,
                        'canteen_stand_id' => $m->canteen_stand_id,
                        'name' => $m->name,
                        'category' => $m->category,
                        'price' => (float) $m->price,
                        'cost_price' => (float) $m->cost_price,
                        'stock' => (int) $m->stock,
                        'is_available' => (bool) $m->is_available,
                        'description' => $m->description,
                    ]),
                ]);

            $canteenStats = [
                'total_stands' => CanteenStand::count(),
                'active_stands' => CanteenStand::where('is_active', true)->count(),
                'total_menus' => CanteenMenu::count(),
                'total_revenue' => (float) CanteenStand::sum('total_revenue'),
            ];
        }

        return Inertia::render('Canteen/Index', [
            'stands' => $stands,
            'canManage' => $canManage,
            'allStands' => $allStands,
            'canteenStats' => $canteenStats,
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

    /**
     * Tambah Stand Kantin Baru
     */
    public function storeStand(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'stand_number' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        CanteenStand::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'stand_number' => $validated['stand_number'],
            'phone' => $validated['phone'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('status', "Stand kantin '{$validated['name']}' berhasil ditambahkan.");
    }

    /**
     * Perbarui Stand Kantin
     */
    public function updateStand(Request $request, int|string $id): RedirectResponse
    {
        $stand = CanteenStand::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'stand_number' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $stand->update($validated);

        return back()->with('status', "Stand kantin '{$stand->name}' berhasil diperbarui.");
    }

    /**
     * Hapus Stand Kantin
     */
    public function destroyStand(int|string $id): RedirectResponse
    {
        $stand = CanteenStand::withCount('orders')->findOrFail($id);

        if ($stand->orders_count > 0) {
            return back()->withErrors([
                'error' => "Stand '{$stand->name}' tidak dapat dihapus karena sudah memiliki {$stand->orders_count} riwayat transaksi.",
            ]);
        }

        $name = $stand->name;
        $stand->delete();

        return back()->with('status', "Stand kantin '{$name}' berhasil dihapus.");
    }

    /**
     * Tambah Menu Makanan/Minuman Baru ke Stand Kantin
     */
    public function storeMenu(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'canteen_stand_id' => ['required', 'integer', 'exists:canteen_stands,id'],
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:food,drink,snack,dessert'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $stand = CanteenStand::findOrFail($validated['canteen_stand_id']);

        CanteenMenu::create([
            'canteen_stand_id' => $stand->id,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'price' => $validated['price'],
            'cost_price' => $validated['cost_price'] ?? 0,
            'stock' => $validated['stock'],
            'is_available' => $validated['is_available'] ?? true,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('status', "Menu '{$validated['name']}' berhasil ditambahkan ke {$stand->name}.");
    }

    /**
     * Perbarui Menu Makanan/Minuman Kantin
     */
    public function updateMenu(Request $request, int|string $id): RedirectResponse
    {
        $menu = CanteenMenu::findOrFail($id);

        $validated = $request->validate([
            'canteen_stand_id' => ['required', 'integer', 'exists:canteen_stands,id'],
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:food,drink,snack,dessert'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $menu->update([
            'canteen_stand_id' => $validated['canteen_stand_id'],
            'name' => $validated['name'],
            'category' => $validated['category'],
            'price' => $validated['price'],
            'cost_price' => $validated['cost_price'] ?? 0,
            'stock' => $validated['stock'],
            'is_available' => $validated['is_available'] ?? true,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('status', "Menu '{$menu->name}' berhasil diperbarui.");
    }

    /**
     * Hapus Menu Makanan/Minuman Kantin
     */
    public function destroyMenu(int|string $id): RedirectResponse
    {
        $menu = CanteenMenu::withCount('orderItems')->findOrFail($id);

        if ($menu->order_items_count > 0) {
            return back()->withErrors([
                'error' => "Menu '{$menu->name}' tidak dapat dihapus karena sudah tercatat dalam {$menu->order_items_count} item pesanan. Nonaktifkan ketersediaan menu sebagai gantinya.",
            ]);
        }

        $name = $menu->name;
        $menu->delete();

        return back()->with('status', "Menu '{$name}' berhasil dihapus.");
    }
}
