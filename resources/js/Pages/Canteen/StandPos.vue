<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    stand: Object,
    menus: Array,
    todayOrders: Array,
});

const activeTab = ref('pos'); // 'pos' or 'orders'
const cart = ref([]);
const buyerIdentifier = ref('0051234567'); // Default demo student NISN
const buyerPin = ref('123456'); // Demo PIN
const paymentMethod = ref('wallet');
const isSubmitting = ref(false);
const statusMessage = ref('');
const errorMessage = ref('');

const ordersList = ref([...props.todayOrders]);

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
        });
    }
};

const updateQty = (item, delta) => {
    item.quantity += delta;
    if (item.quantity <= 0) {
        cart.value = cart.value.filter(i => i.menu_id !== item.menu_id);
    }
};

const totalAmount = computed(() => {
    return cart.value.reduce((sum, i) => sum + (i.price * i.quantity), 0);
});

const submitOrder = async () => {
    if (cart.value.length === 0) return;

    isSubmitting.value = true;
    statusMessage.value = '';
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
                canteen_stand_id: props.stand.id,
                items: cart.value.map(i => ({
                    menu_id: i.menu_id,
                    quantity: i.quantity,
                })),
                payment_method: paymentMethod.value,
                pin: paymentMethod.value === 'wallet' ? buyerPin.value : null,
            })
        });

        const data = await res.json();
        if (data.success) {
            statusMessage.value = `Pesanan ${data.order.order_number} berhasil dicatat!`;
            ordersList.value.unshift({
                id: data.order.id,
                order_number: data.order.order_number,
                buyer_name: 'Siswa / Pembeli',
                total_amount: data.order.total_amount,
                payment_method: paymentMethod.value,
                order_status: data.order.order_status,
                order_date: 'Baru Saja',
                items: [...cart.value.map(c => ({ name: c.name, qty: c.quantity, subtotal: c.price * c.quantity }))],
            });
            cart.value = [];
        } else {
            errorMessage.value = data.message || 'Gagal memproses pesanan.';
        }
    } catch (e) {
        errorMessage.value = 'Terjadi kesalahan sistem.';
    } finally {
        isSubmitting.value = false;
    }
};

const updateOrderStatus = async (orderId, newStatus) => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(`/canteen/orders/${orderId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ status: newStatus })
        });

        const data = await res.json();
        if (data.success) {
            const order = ordersList.value.find(o => o.id === orderId);
            if (order) {
                order.order_status = newStatus;
            }
        }
    } catch (e) {
        alert('Gagal memperbarui status order.');
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col">
        <Head :title="'POS Kasir Stand - ' + stand.name" />

        <!-- Header -->
        <header class="bg-slate-900 border-b border-slate-800 px-6 py-4 flex items-center justify-between sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <Link
                    :href="route('dashboard')"
                    class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-base font-black text-white uppercase tracking-wide">{{ stand.name }} ({{ stand.stand_number }})</h1>
                    <p class="text-xs text-slate-400">Terminal Kasir Vendor • Omset Hari Ini: <span class="text-emerald-400 font-bold font-mono">{{ formatRupiah(stand.total_revenue) }}</span></p>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center gap-2 bg-slate-950 p-1 rounded-xl border border-slate-800">
                <button
                    type="button"
                    @click="activeTab = 'pos'"
                    :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition', activeTab === 'pos' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white']"
                >
                    Kasir Baru
                </button>
                <button
                    type="button"
                    @click="activeTab = 'orders'"
                    :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5', activeTab === 'orders' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white']"
                >
                    <span>Antrean Pesanan</span>
                    <span class="px-1.5 py-0.2 rounded-full bg-emerald-500 text-white text-[10px] font-mono">{{ ordersList.length }}</span>
                </button>
            </div>
        </header>

        <!-- POS Tab Layout -->
        <div v-if="activeTab === 'pos'" class="max-w-7xl mx-auto w-full px-6 py-6 grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1">
            <!-- Left: Touch Menu Buttons (7 Cols) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Katalog Menu Stand</span>
                    <span class="text-xs text-slate-500">{{ menus.length }} Menu Tersedia</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <button
                        v-for="m in menus"
                        :key="m.id"
                        type="button"
                        @click="addToCart(m)"
                        :disabled="m.stock <= 0"
                        class="p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-indigo-500 text-left transition flex flex-col justify-between h-32 active:scale-95 disabled:opacity-40"
                    >
                        <div>
                            <span class="text-[9px] px-2 py-0.5 rounded uppercase font-bold bg-slate-800 text-slate-400">{{ m.category }}</span>
                            <div class="text-xs font-bold text-white mt-1 line-clamp-2">{{ m.name }}</div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/80">
                            <span class="text-xs font-bold text-emerald-400 font-mono">{{ formatRupiah(m.price) }}</span>
                            <span class="text-[10px] text-slate-500">Stok: {{ m.stock }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Right: Cart & Quick Checkout (5 Cols) -->
            <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-2xl flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-3">
                        <h2 class="text-sm font-bold text-white">Struk Pesanan Aktif</h2>
                        <button
                            v-if="cart.length > 0"
                            type="button"
                            @click="cart = []"
                            class="text-xs text-rose-400 hover:text-rose-300"
                        >
                            Kosongkan
                        </button>
                    </div>

                    <!-- Banners -->
                    <div v-if="statusMessage" class="p-3 mb-3 rounded-xl bg-emerald-950/60 border border-emerald-600 text-emerald-300 text-xs">
                        {{ statusMessage }}
                    </div>
                    <div v-if="errorMessage" class="p-3 mb-3 rounded-xl bg-rose-950/60 border border-rose-600 text-rose-300 text-xs">
                        {{ errorMessage }}
                    </div>

                    <!-- Cart Items -->
                    <div v-if="cart.length === 0" class="text-center py-12 text-slate-500 text-xs">
                        Klik menu di samping untuk menambahkan ke struk pesanan.
                    </div>

                    <div v-else class="space-y-2 max-h-64 overflow-y-auto pr-1">
                        <div
                            v-for="item in cart"
                            :key="item.menu_id"
                            class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between text-xs"
                        >
                            <div>
                                <div class="font-bold text-white">{{ item.name }}</div>
                                <div class="text-slate-400 font-mono">{{ formatRupiah(item.price) }} x {{ item.quantity }}</div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="font-bold font-mono text-emerald-400">{{ formatRupiah(item.price * item.quantity) }}</span>
                                <button
                                    type="button"
                                    @click="updateQty(item, -1)"
                                    class="w-6 h-6 rounded bg-slate-800 text-white font-bold flex items-center justify-center hover:bg-slate-700"
                                >
                                    -
                                </button>
                                <button
                                    type="button"
                                    @click="updateQty(item, 1)"
                                    class="w-6 h-6 rounded bg-slate-800 text-white font-bold flex items-center justify-center hover:bg-slate-700"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="border-t border-slate-800 pt-4 space-y-3">
                    <div class="flex justify-between items-center text-sm font-bold">
                        <span class="text-slate-300">Total Tagihan:</span>
                        <span class="text-2xl font-black text-emerald-400 font-mono">{{ formatRupiah(totalAmount) }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="paymentMethod = 'wallet'"
                            :class="['py-2 rounded-xl text-xs font-bold border transition', paymentMethod === 'wallet' ? 'bg-indigo-600 text-white border-indigo-400' : 'bg-slate-950 text-slate-300 border-slate-800']"
                        >
                            💳 Tap KTS / Wallet
                        </button>
                        <button
                            type="button"
                            @click="paymentMethod = 'cash'"
                            :class="['py-2 rounded-xl text-xs font-bold border transition', paymentMethod === 'cash' ? 'bg-indigo-600 text-white border-indigo-400' : 'bg-slate-950 text-slate-300 border-slate-800']"
                        >
                            💵 Tunai (Kasir)
                        </button>
                    </div>

                    <div v-if="paymentMethod === 'wallet'" class="grid grid-cols-2 gap-2">
                        <input
                            type="text"
                            v-model="buyerIdentifier"
                            placeholder="NISN Siswa..."
                            class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none focus:border-indigo-500"
                        />
                        <input
                            type="password"
                            v-model="buyerPin"
                            maxlength="6"
                            placeholder="PIN Siswa..."
                            class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs font-mono text-center tracking-widest text-emerald-400 focus:outline-none focus:border-indigo-500"
                        />
                    </div>

                    <button
                        type="button"
                        @click="submitOrder"
                        :disabled="isSubmitting || cart.length === 0"
                        class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-40"
                    >
                        {{ isSubmitting ? 'Memproses Pesanan...' : 'SIMPAN & SELESAIKAN PESANAN' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders Queue Tab Layout -->
        <div v-else class="max-w-5xl mx-auto w-full px-6 py-6 space-y-4">
            <h2 class="text-sm font-bold text-white">Antrean Pesanan Masuk Hari Ini ({{ ordersList.length }})</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="ord in ordersList"
                    :key="ord.id"
                    class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-3"
                >
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <div>
                            <div class="text-xs font-bold text-white">{{ ord.buyer_name }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ ord.order_number }} • {{ ord.order_date }}</div>
                        </div>
                        <span
                            :class="[
                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono',
                                ord.order_status === 'ready' ? 'bg-emerald-500/20 text-emerald-300' :
                                ord.order_status === 'cooking' ? 'bg-amber-500/20 text-amber-300' :
                                ord.order_status === 'completed' ? 'bg-slate-800 text-slate-400' : 'bg-blue-500/20 text-blue-300'
                            ]"
                        >
                            {{ ord.order_status }}
                        </span>
                    </div>

                    <!-- Items -->
                    <div class="space-y-1 text-xs text-slate-300">
                        <div v-for="(it, idx) in ord.items" :key="idx" class="flex justify-between">
                            <span>{{ it.qty }}x {{ it.name }}</span>
                            <span class="font-mono text-slate-400">{{ formatRupiah(it.subtotal) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                        <span class="text-xs font-bold font-mono text-emerald-400">{{ formatRupiah(ord.total_amount) }} ({{ ord.payment_method }})</span>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-1.5">
                            <button
                                v-if="ord.order_status === 'pending'"
                                type="button"
                                @click="updateOrderStatus(ord.id, 'cooking')"
                                class="px-2.5 py-1 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold transition"
                            >
                                🍳 Masak
                            </button>
                            <button
                                v-if="ord.order_status === 'cooking'"
                                type="button"
                                @click="updateOrderStatus(ord.id, 'ready')"
                                class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold transition"
                            >
                                🔔 Siap Ambil
                            </button>
                            <button
                                v-if="ord.order_status === 'ready'"
                                type="button"
                                @click="updateOrderStatus(ord.id, 'completed')"
                                class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold transition"
                            >
                                ✓ Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
