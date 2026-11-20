<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    stands: {
        type: Array,
        default: () => [],
    },
    canManage: {
        type: Boolean,
        default: false,
    },
    allStands: {
        type: Array,
        default: () => [],
    },
    canteenStats: {
        type: Object,
        default: () => ({ total_stands: 0, active_stands: 0, total_menus: 0, total_revenue: 0 }),
    },
    student: Object,
    wallet: Object,
    recentOrders: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref(props.canManage ? 'manage' : 'marketplace');

const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
    { label: 'Materi & Tugas (LMS)', route: 'academic.learning.index', icon: 'book-open' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Tagihan & SPP Massal', route: 'finance.bills.index', icon: 'credit-card' },
    { label: 'POS Kasir SPP', route: 'finance.pos', icon: 'credit-card' },
    { label: 'Jadwal Pelajaran', route: 'academic.schedules', icon: 'calendar' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'Cetak Massal KTS', route: 'kts.batch', icon: 'id-card' },
    { label: 'Presensi GPS & AI', route: 'attendance.gps.index', icon: 'location' },
    { label: 'Verifikasi E-Leave', route: 'attendance.leave.index', icon: 'clipboard-check' },
    { label: 'Dompet Digital Siswa', route: 'wallet.index', icon: 'wallet' },
    { label: 'E-Kantin & Stand', route: 'canteen.index', icon: 'shopping-bag' },
    { isHeader: true, label: 'Master Data Akademik' },
    { label: 'Tahun Ajaran & Semester', route: 'academic.academic-years.index', icon: 'calendar' },
    { label: 'Program Keahlian (Jurusan)', route: 'academic.departments.index', icon: 'academic-cap' },
    { label: 'Data Siswa & Rombel', route: 'academic.students.index', icon: 'users' },
    { label: 'Data Guru & PTK', route: 'academic.teachers.index', icon: 'academic-cap' },
    { label: 'Rombel (Kelas)', route: 'academic.classrooms.index', icon: 'rectangle-stack' },
    { label: 'Mata Pelajaran', route: 'academic.subjects.index', icon: 'book-open' },
];

// ==================== MARKETPLACE STATE ====================
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
        maximumFractionDigits: 0,
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
            }),
        });

        const data = await res.json();
        if (data.success) {
            successMessage.value = `Pesanan ${data.order.order_number} berhasil dibuat! Makanan sedang disiapkan.`;
            cart.value = [];
            walletPin.value = '';
            isCartOpen.value = false;

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

// ==================== MANAGEMENT STATE (STAND & MENU CRUD) ====================
const isStandModalOpen = ref(false);
const editingStand = ref(null);
const standForm = useForm({
    name: '',
    stand_number: '',
    phone: '',
    description: '',
    is_active: true,
});

const openCreateStandModal = () => {
    editingStand.value = null;
    standForm.reset();
    standForm.is_active = true;
    isStandModalOpen.value = true;
};

const openEditStandModal = (stand) => {
    editingStand.value = stand;
    standForm.name = stand.name;
    standForm.stand_number = stand.stand_number;
    standForm.phone = stand.phone || '';
    standForm.description = stand.description || '';
    standForm.is_active = Boolean(stand.is_active);
    isStandModalOpen.value = true;
};

const submitStand = () => {
    if (editingStand.value) {
        standForm.put(route('canteen.stands.update', editingStand.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isStandModalOpen.value = false;
            },
        });
    } else {
        standForm.post(route('canteen.stands.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isStandModalOpen.value = false;
            },
        });
    }
};

const deleteStand = (stand) => {
    if (stand.orders_count > 0) {
        alert(`Stand "${stand.name}" tidak dapat dihapus karena sudah memiliki ${stand.orders_count} riwayat transaksi pesanan.`);
        return;
    }
    if (confirm(`Apakah Anda yakin ingin menghapus stand "${stand.name}" (${stand.stand_number})?`)) {
        router.delete(route('canteen.stands.destroy', stand.id), {
            preserveScroll: true,
        });
    }
};

// Menu Modal
const isMenuModalOpen = ref(false);
const editingMenu = ref(null);
const menuForm = useForm({
    canteen_stand_id: props.allStands?.[0]?.id || '',
    name: '',
    category: 'food',
    price: 10000,
    cost_price: 7000,
    stock: 50,
    is_available: true,
    description: '',
});

const openCreateMenuModal = (standId = null) => {
    editingMenu.value = null;
    menuForm.reset();
    menuForm.canteen_stand_id = standId || props.allStands?.[0]?.id || '';
    menuForm.category = 'food';
    menuForm.price = 10000;
    menuForm.cost_price = 7000;
    menuForm.stock = 50;
    menuForm.is_available = true;
    isMenuModalOpen.value = true;
};

const openEditMenuModal = (menu) => {
    editingMenu.value = menu;
    menuForm.canteen_stand_id = menu.canteen_stand_id;
    menuForm.name = menu.name;
    menuForm.category = menu.category;
    menuForm.price = menu.price;
    menuForm.cost_price = menu.cost_price || 0;
    menuForm.stock = menu.stock;
    menuForm.is_available = Boolean(menu.is_available);
    menuForm.description = menu.description || '';
    isMenuModalOpen.value = true;
};

const submitMenu = () => {
    if (editingMenu.value) {
        menuForm.put(route('canteen.menus.update', editingMenu.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isMenuModalOpen.value = false;
            },
        });
    } else {
        menuForm.post(route('canteen.menus.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isMenuModalOpen.value = false;
            },
        });
    }
};

const deleteMenu = (menu) => {
    if (confirm(`Apakah Anda yakin ingin menghapus menu "${menu.name}"?`)) {
        router.delete(route('canteen.menus.destroy', menu.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="E-Kantin Sekolah - Jajanan Sehat & Manajemen Stand" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <Sidebar v-if="canManage" :menus="adminMenus" sectionTitle="Operasional Sekolah" />

        <div :class="['flex-1 flex flex-col min-w-0 pb-28', canManage ? 'lg:pl-64' : '']">
            <!-- Top Header Navigation -->
            <header class="bg-slate-900/90 border-b border-slate-800 px-6 py-4 sticky top-0 z-20 backdrop-blur-md">
                <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link
                            v-if="!canManage"
                            :href="route('portal.student')"
                            class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-base sm:text-lg font-black tracking-wide text-white">
                                {{ canManage ? 'MANAJEMEN E-KANTIN & STAND SEKOLAH' : 'E-KANTIN SEKOLAH' }}
                            </h1>
                            <p class="text-xs text-slate-400">
                                {{ canManage ? 'Kelola Stand Vendor, Katalog Menu, Stok Harian & POS Antrean Cashless' : 'Pesan Makanan & Minuman Sehat Tanpa Uang Tunai' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <template v-if="canManage">
                            <button
                                type="button"
                                @click="openCreateStandModal"
                                class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow transition"
                            >
                                + Stand Baru
                            </button>
                            <button
                                type="button"
                                @click="openCreateMenuModal()"
                                :disabled="allStands.length === 0"
                                class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow transition disabled:opacity-40"
                            >
                                + Menu Makanan
                            </button>
                        </template>

                        <!-- Wallet Quick Badge -->
                        <Link
                            v-if="wallet"
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
                </div>
            </header>

            <main class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-6 space-y-6">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash?.status" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs sm:text-sm flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ $page.props.flash.status }}</span>
                </div>
                <div v-if="$page.props.errors?.error" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs sm:text-sm flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ $page.props.errors.error }}</span>
                </div>

                <!-- Tab Switcher untuk Admin / Vendor -->
                <div v-if="canManage" class="flex items-center gap-2 border-b border-slate-800 pb-3">
                    <button
                        type="button"
                        @click="activeTab = 'manage'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition',
                            activeTab === 'manage'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                        ]"
                    >
                        🏪 Manajemen Stand & Menu ({{ allStands.length }} Stand)
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'marketplace'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition',
                            activeTab === 'marketplace'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                        ]"
                    >
                        🍔 Katalog Marketplace Siswa
                    </button>
                </div>

                <!-- ==================== TAB MANAJEMEN STAND & MENU ==================== -->
                <div v-if="canManage && activeTab === 'manage'" class="space-y-6">
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Stand Kantin</span>
                            <div class="text-2xl font-black text-white mt-2">{{ canteenStats.total_stands }}</div>
                            <div class="text-xs text-emerald-400 mt-0.5">{{ canteenStats.active_stands }} Stand Buka</div>
                        </div>
                        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Katalog Menu</span>
                            <div class="text-2xl font-black text-indigo-400 mt-2">{{ canteenStats.total_menus }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Makanan, Minuman & Snack</div>
                        </div>
                        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 sm:col-span-2">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Akumulasi Omset E-Kantin</span>
                            <div class="text-2xl font-black text-emerald-400 font-mono mt-2">{{ formatRupiah(canteenStats.total_revenue) }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Total transaksi pembayaran cashless & tunai seluruh stand</div>
                        </div>
                    </div>

                    <!-- Daftar Stand beserta Tabel Menu Masing-masing -->
                    <div v-if="allStands.length === 0" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center text-slate-400">
                        Belum ada stand kantin terdaftar. Klik tombol <strong>+ Stand Baru</strong> di pojok kanan atas.
                    </div>

                    <div v-for="stand in allStands" :key="stand.id" class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden shadow-lg">
                        <!-- Stand Header -->
                        <div class="p-5 bg-slate-950/60 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2.5 py-0.5 rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-mono text-xs font-bold">
                                        {{ stand.stand_number }}
                                    </span>
                                    <h3 class="text-base font-bold text-white">{{ stand.name }}</h3>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                            stand.is_active ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
                                        ]"
                                    >
                                        {{ stand.is_active ? 'Buka' : 'Tutup' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Pengelola: <span class="text-slate-200">{{ stand.vendor_name }}</span>
                                    <span v-if="stand.phone"> • Telp: {{ stand.phone }}</span>
                                    • Omset: <span class="text-emerald-400 font-mono font-bold">{{ formatRupiah(stand.total_revenue) }}</span>
                                    ({{ stand.orders_count }} pesanan)
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    :href="route('canteen.stand.pos', stand.id)"
                                    class="px-3 py-1.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 text-xs font-bold transition"
                                >
                                    🖥️ Layar POS Antrean
                                </Link>
                                <button
                                    type="button"
                                    @click="openCreateMenuModal(stand.id)"
                                    class="px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-bold transition"
                                >
                                    + Tambah Menu
                                </button>
                                <button
                                    type="button"
                                    @click="openEditStandModal(stand)"
                                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition"
                                >
                                    Edit Stand
                                </button>
                                <button
                                    type="button"
                                    @click="deleteStand(stand)"
                                    class="px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-semibold transition"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <!-- Tabel Menu di Stand Ini -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-slate-900/90 text-[11px] font-semibold text-slate-400 uppercase border-b border-slate-800">
                                    <tr>
                                        <th class="px-5 py-3">Nama Menu</th>
                                        <th class="px-5 py-3">Kategori</th>
                                        <th class="px-5 py-3">Harga Jual</th>
                                        <th class="px-5 py-3 text-center">Stok Harian</th>
                                        <th class="px-5 py-3 text-center">Status</th>
                                        <th class="px-5 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <tr v-for="menu in stand.menus" :key="menu.id" class="hover:bg-slate-800/30">
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-white text-sm">{{ menu.name }}</div>
                                            <div v-if="menu.description" class="text-[11px] text-slate-400 line-clamp-1">{{ menu.description }}</div>
                                        </td>
                                        <td class="px-5 py-3.5 uppercase font-bold text-[10px]">
                                            <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                                                {{ menu.category }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 font-mono font-bold text-emerald-400">
                                            {{ formatRupiah(menu.price) }}
                                        </td>
                                        <td class="px-5 py-3.5 text-center font-mono">
                                            <span :class="menu.stock <= 5 ? 'text-rose-400 font-bold' : 'text-slate-200'">
                                                {{ menu.stock }} porsi
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <span
                                                :class="[
                                                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                                    menu.is_available && menu.stock > 0
                                                        ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30'
                                                        : 'bg-slate-800 text-slate-400'
                                                ]"
                                            >
                                                {{ menu.is_available && menu.stock > 0 ? 'Tersedia' : 'Habis / Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    @click="openEditMenuModal(menu)"
                                                    class="px-2.5 py-1 rounded-lg bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 font-semibold"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteMenu(menu)"
                                                    class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-semibold"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!stand.menus || stand.menus.length === 0">
                                        <td colspan="6" class="px-5 py-6 text-center text-slate-500">
                                            Belum ada menu pada stand ini. Klik <strong>+ Tambah Menu</strong> untuk menambahkan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB MARKETPLACE SISWA ==================== -->
                <div v-if="!canManage || activeTab === 'marketplace'" class="space-y-6">
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
                </div>
            </main>
        </div>

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

        <!-- Checkout Modal -->
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

        <!-- ==================== MODAL TAMBAH/EDIT STAND ==================== -->
        <div v-if="isStandModalOpen" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white">
                        {{ editingStand ? 'Edit Stand Kantin' : 'Tambah Stand Kantin Baru' }}
                    </h3>
                    <button type="button" @click="isStandModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitStand" class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <label class="block text-xs font-semibold text-slate-300 mb-1">No. Stand *</label>
                            <input
                                v-model="standForm.stand_number"
                                type="text"
                                placeholder="ST-01"
                                required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                            />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Stand Kantin *</label>
                            <input
                                v-model="standForm.name"
                                type="text"
                                placeholder="Kantin Sehat Bu Siti"
                                required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nomor Telepon / WA Pengelola</label>
                        <input
                            v-model="standForm.phone"
                            type="text"
                            placeholder="081234567890"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Deskripsi Singkat</label>
                        <textarea
                            v-model="standForm.description"
                            rows="2"
                            placeholder="Menyediakan aneka nasi kotak, soto ayam, dan jus buah segar..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        ></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" v-model="standForm.is_active" class="rounded bg-slate-950 border-slate-700 text-indigo-600" />
                        <span>Stand Aktif (Buka untuk menerima pesanan siswa)</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="isStandModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white">
                            Batal
                        </button>
                        <button type="submit" :disabled="standForm.processing" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">
                            {{ standForm.processing ? 'Menyimpan...' : 'Simpan Stand' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL TAMBAH/EDIT MENU ==================== -->
        <div v-if="isMenuModalOpen" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white">
                        {{ editingMenu ? 'Edit Menu Kantin' : 'Tambah Menu Makanan / Minuman' }}
                    </h3>
                    <button type="button" @click="isMenuModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitMenu" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Pilih Stand Kantin *</label>
                        <select
                            v-model="menuForm.canteen_stand_id"
                            required
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        >
                            <option v-for="st in allStands" :key="st.id" :value="st.id">
                                {{ st.stand_number }} - {{ st.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Menu *</label>
                            <input
                                v-model="menuForm.name"
                                type="text"
                                placeholder="Nasi Ayam Geprek"
                                required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Kategori *</label>
                            <select
                                v-model="menuForm.category"
                                required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                            >
                                <option value="food">Makanan</option>
                                <option value="drink">Minuman</option>
                                <option value="snack">Snack</option>
                                <option value="dessert">Dessert</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Harga Jual (Rp) *</label>
                            <input
                                v-model.number="menuForm.price"
                                type="number"
                                min="500"
                                step="500"
                                required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Modal/HPP (Rp)</label>
                            <input
                                v-model.number="menuForm.cost_price"
                                type="number"
                                min="0"
                                step="500"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Stok Porsi *</label>
                            <input
                                v-model.number="menuForm.stock"
                                type="number"
                                min="0"
                                required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Keterangan Menu</label>
                        <textarea
                            v-model="menuForm.description"
                            rows="2"
                            placeholder="Komposisi bahan sehat tanpa pengawet..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        ></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" v-model="menuForm.is_available" class="rounded bg-slate-950 border-slate-700 text-emerald-600" />
                        <span>Menu Tersedia Hari Ini</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="isMenuModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white">
                            Batal
                        </button>
                        <button type="submit" :disabled="menuForm.processing" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold">
                            {{ menuForm.processing ? 'Menyimpan...' : 'Simpan Menu' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
