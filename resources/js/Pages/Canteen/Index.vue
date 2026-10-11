<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    stands: Array,
    student: Object,
    wallet: Object,
    recentOrders: Array,
});

const selectedStandId = ref(props.stands?.[0]?.id || null);
const selectedCategory = ref('all');
const cart = ref([]);
const isCartOpen = ref(false);
const walletPin = ref('');
const isSubmitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const currentStand = computed(() => {
    return props.stands?.find(s => s.id === selectedStandId.value) || null;
});

const filteredMenus = computed(() => {
    if (!currentStand.value) return [];
    if (selectedCategory.value === 'all') {
        return currentStand.value.menus || [];
    }
    return (currentStand.value.menus || []).filter(m => m.category === selectedCategory.value);
});

const totalCartAmount = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const totalCartCount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

const addToCart = (menu) => {
    const existing = cart.value.find(i => i.menu_id === menu.id);
    if (existing) {
        if (existing.quantity < menu.stock) {
            existing.quantity++;
        }
    } else {
        cart.value.push({
            menu_id: menu.id,
            name: menu.name,
            price: parseFloat(menu.price),
            quantity: 1,
            stock: menu.stock,
            special_request: '',
        });
    }
    successMessage.value = '';
    errorMessage.value = '';
};

const updateQuantity = (item, delta) => {
    item.quantity += delta;
    if (item.quantity <= 0) {
        cart.value = cart.value.filter(i => i.menu_id !== item.menu_id);
    } else if (item.quantity > item.stock) {
        item.quantity = item.stock;
    }
};

const checkout = async () => {
    if (cart.value.length === 0) return;
    if (!walletPin.value || walletPin.value.length !== 6) {
        errorMessage.value = 'Masukkan 6-digit PIN dompet digital KTS Anda untuk konfirmasi jajan.';
        return;
    }

    if (totalCartAmount.value > (props.wallet?.balance || 0)) {
        errorMessage.value = 'Saldo dompet tidak mencukupi. Silakan top up tabungan terlebih dahulu.';
        return;
    }

    if (totalCartAmount.value > (props.wallet?.remaining_limit || 0)) {
        errorMessage.value = 'Pesanan melebihi sisa batas jajan harian Anda hari ini.';
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/canteen/order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                canteen_stand_id: selectedStandId.value,
                items: cart.value.map(i => ({
                    menu_id: i.menu_id,
                    quantity: i.quantity,
                    special_request: i.special_request || null,
                })),
                payment_method: 'wallet',
                pin: walletPin.value,
            })
        });

        const data = await res.json();
        if (data.success) {
            successMessage.value = `Pesanan ${data.order.order_number} berhasil dibuat! Makanan sedang disiapkan.`;
            cart.value = [];
            walletPin.value = '';
            isCartOpen.value = false;

            // Kurangi saldo sementara di view
            if (props.wallet) {
                props.wallet.balance -= data.order.total_amount;
                props.wallet.remaining_limit -= data.order.total_amount;
            }
        } else {
            errorMessage.value = data.message || 'Gagal memproses pesanan.';
        }
    } catch (e) {
        errorMessage.value = 'Terjadi kesalahan sistem saat checkout.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 pb-28">
        <Head title="E-Kantin Sekolah - Jajanan Sehat & Cashless" />

        <!-- Top Header Navigation -->
        <header class="bg-slate-900/90 border-b border-slate-800 px-6 py-4 sticky top-0 z-20 backdrop-blur-md">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('portal.student')"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-base font-black tracking-wide text-white">E-KANTIN SEKOLAH</h1>
                        <p class="text-xs text-slate-400">Pesan Makanan & Minuman Sehat Tanpa Uang Tunai</p>
                    </div>
                </div>

                <!-- Wallet Quick Badge -->
                <Link
                    :href="route('wallet.index')"
                    class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-indigo-950/80 border border-indigo-500/30 hover:bg-indigo-900/60 transition"
                >
                    <div class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></div>
                    <div class="text-right">
                        <div class="text-[9px] text-indigo-300 uppercase font-mono">Saldo KTS</div>
                        <div class="text-xs font-bold text-white font-mono">{{ formatRupiah(wallet?.balance) }}</div>
                    </div>
                </Link>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 py-6 space-y-6">
            <!-- Notification Banners -->
            <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-600 text-emerald-300 text-xs flex items-center gap-2">
                <span>🎉</span>
                <span>{{ successMessage }}</span>
            </div>
            <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-950/60 border border-rose-600 text-rose-300 text-xs flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ errorMessage }}</span>
            </div>

            <!-- Stand Selection Tabs -->
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-3">Pilih Stand Kantin Buka:</span>
                <div class="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-none">
                    <button
                        v-for="s in stands"
                        :key="s.id"
                        type="button"
                        @click="selectedStandId = s.id"
                        :class="[
                            'px-4 py-2.5 rounded-2xl border transition text-left shrink-0',
                            selectedStandId === s.id
                                ? 'bg-indigo-600 text-white border-indigo-400 shadow-lg shadow-indigo-600/30'
                                : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-slate-700'
                        ]"
                    >
                        <div class="text-xs font-bold">{{ s.name }}</div>
                        <div class="text-[10px] opacity-75 font-mono">{{ s.stand_number }}</div>
                    </button>
                </div>
            </div>

            <!-- Category Filter Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button
                    v-for="cat in [
                        { id: 'all', label: 'Semua Menu' },
                        { id: 'food', label: '🍲 Makanan' },
                        { id: 'drink', label: '🥤 Minuman' },
                        { id: 'snack', label: '🍟 Snack' },
                        { id: 'dessert', label: '🍧 Dessert' }
                    ]"
                    :key="cat.id"
                    type="button"
                    @click="selectedCategory = cat.id"
                    :class="[
                        'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition',
                        selectedCategory === cat.id
                            ? 'bg-slate-200 text-slate-900 font-bold'
                            : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                    ]"
                >
                    {{ cat.label }}
                </button>
            </div>

            <!-- Menu Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="m in filteredMenus"
                    :key="m.id"
                    class="bg-slate-900 border border-slate-800 hover:border-indigo-500/40 rounded-2xl p-4 transition shadow-lg flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full uppercase font-bold tracking-wider"
                                :class="m.category === 'food' ? 'bg-amber-500/20 text-amber-300' : 'bg-blue-500/20 text-blue-300'">
                                {{ m.category }}
                            </span>
                            <span class="text-[10px] text-slate-400">Stok: {{ m.stock }}</span>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1">{{ m.name }}</h3>
                        <p class="text-xs text-slate-400 line-clamp-2 mb-3">{{ m.description || 'Menu lezat dan higienis bersertifikasi kantin sehat sekolah.' }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-800">
                        <span class="text-base font-black text-emerald-400 font-mono">{{ formatRupiah(m.price) }}</span>
                        <button
                            type="button"
                            @click="addToCart(m)"
                            :disabled="m.stock <= 0"
                            class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow transition flex items-center gap-1 disabled:opacity-40"
                        >
                            <span>+ Tambah</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Tracker -->
            <div v-if="recentOrders && recentOrders.length > 0" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Pesanan Terakhir Saya</h3>

                <div class="divide-y divide-slate-800/80">
                    <div
                        v-for="ord in recentOrders"
                        :key="ord.id"
                        class="py-3 flex items-center justify-between gap-4 text-xs"
                    >
                        <div>
                            <div class="font-bold text-white">{{ ord.stand_name }} ({{ ord.items_count }} item)</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ ord.order_number }} • {{ ord.order_date }}</div>
                        </div>

                        <div class="text-right">
                            <span
                                :class="[
                                    'inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                    ord.order_status === 'ready' ? 'bg-emerald-500/20 text-emerald-300 animate-pulse' :
                                    ord.order_status === 'cooking' ? 'bg-amber-500/20 text-amber-300' :
                                    ord.order_status === 'completed' ? 'bg-slate-800 text-slate-300' : 'bg-blue-500/20 text-blue-300'
                                ]"
                            >
                                {{ ord.order_status === 'ready' ? '🔔 Siap Diambil!' : ord.order_status === 'cooking' ? '🍳 Dimasak' : ord.order_status }}
                            </span>
                            <div class="font-mono text-emerald-400 font-bold mt-0.5">{{ formatRupiah(ord.total_amount) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Floating Cart Bar (Bottom) -->
        <div v-if="totalCartCount > 0" class="fixed bottom-4 inset-x-4 max-w-xl mx-auto z-30">
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 border border-indigo-400/40 rounded-2xl p-4 shadow-2xl backdrop-blur-md flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center font-bold text-white text-base">
                        🛍️
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">{{ totalCartCount }} Menu Terpilih</div>
                        <div class="text-sm font-black text-amber-300 font-mono">{{ formatRupiah(totalCartAmount) }}</div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="isCartOpen = true"
                    class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-indigo-950 text-xs font-black shadow transition flex items-center gap-1.5"
                >
                    Lihat Keranjang & Bayar →
                </button>
            </div>
        </div>

        <!-- Checkout Modal / Drawer -->
        <div v-if="isCartOpen" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>🛒</span> Keranjang Jajan E-Kantin
                    </h3>
                    <button
                        type="button"
                        @click="isCartOpen = false"
                        class="text-slate-400 hover:text-white text-sm font-bold"
                    >
                        ✕
                    </button>
                </div>

                <!-- Items List -->
                <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                    <div
                        v-for="item in cart"
                        :key="item.menu_id"
                        class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-3 text-xs"
                    >
                        <div class="flex-1">
                            <div class="font-bold text-white">{{ item.name }}</div>
                            <div class="text-slate-400 font-mono">{{ formatRupiah(item.price) }}</div>
                        </div>

                        <!-- Qty Controls -->
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="updateQuantity(item, -1)"
                                class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center"
                            >
                                -
                            </button>
                            <span class="font-mono font-bold w-4 text-center">{{ item.quantity }}</span>
                            <button
                                type="button"
                                @click="updateQuantity(item, 1)"
                                class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center"
                            >
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Total & PIN Input -->
                <div class="space-y-3 border-t border-slate-800 pt-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Total Tagihan Jajan:</span>
                        <span class="font-black text-emerald-400 font-mono text-base">{{ formatRupiah(totalCartAmount) }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Konfirmasi 6-Digit PIN Dompet KTS:</label>
                        <input
                            type="password"
                            v-model="walletPin"
                            maxlength="6"
                            placeholder="******"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2 text-center text-lg tracking-widest font-mono text-emerald-400 focus:outline-none focus:border-indigo-500"
                        />
                        <p class="text-[10px] text-slate-400 text-center mt-1">PIN akun demo siswa: <span class="text-white font-mono">123456</span></p>
                    </div>

                    <button
                        type="button"
                        @click="checkout"
                        :disabled="isSubmitting || totalCartCount === 0"
                        class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-black rounded-xl shadow transition disabled:opacity-50"
                    >
                        {{ isSubmitting ? 'Memproses Transaksi...' : 'BAYAR DENGAN SALDO DOMPET KTS' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
