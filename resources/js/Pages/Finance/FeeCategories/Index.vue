<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    categories: Array,
});

const page = usePage();

// Navigasi Terpadu Administrator Sekolah
const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
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

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    code: '',
    name: '',
    default_amount: 250000,
    is_recurring: true,
    is_active: true,
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.default_amount = 250000;
    form.is_recurring = true;
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.code = item.code;
    form.name = item.name;
    form.default_amount = Number(item.default_amount);
    form.is_recurring = Boolean(item.is_recurring);
    form.is_active = Boolean(item.is_active);
    form.description = item.description || '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('finance.fee-categories.update', editingId.value), {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post(route('finance.fee-categories.store'), {
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const deleteCategory = (item) => {
    if (item.bills_count > 0) {
        alert(`Pos biaya ${item.name} tidak dapat dihapus karena sudah digunakan oleh ${item.bills_count} tagihan siswa.`);
        return;
    }

    if (confirm(`Apakah Anda yakin ingin menghapus pos tarif "${item.name}"?`)) {
        router.delete(route('finance.fee-categories.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val) || 0);
};
</script>

<template>
    <Head title="Master Pos Tarif & Kategori Biaya - Portal Sekolah" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <!-- Sidebar Terpadu -->
        <Sidebar :menus="adminMenus" sectionTitle="Operasional Sekolah" />

        <!-- Konten Utama -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
            <!-- Header Atas -->
            <header class="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Master Pos Tarif & Kategori Biaya</h1>
                        <p class="text-xs text-slate-400">Pengaturan jenis biaya sekolah, tarif default, dan sifat tagihan</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('finance.bills.index')"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors border border-slate-700 inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Daftar Tagihan Siswa</span>
                    </Link>
                </div>
            </header>

            <!-- Main Workspace -->
            <main class="p-6 space-y-6 flex-1">
                <!-- Flash Notification -->
                <div v-if="$page.props.flash?.status" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 flex items-center justify-between text-sm animate-fade-in">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $page.props.flash.status }}</span>
                    </div>
                </div>

                <div v-if="$page.props.errors?.error" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 flex items-center justify-between text-sm animate-fade-in">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $page.props.errors.error }}</span>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Pos Biaya</span>
                            <span class="p-2 rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-white">{{ categories.length }}</span>
                            <span class="text-xs text-slate-400">Kategori Biaya</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tagihan Bulanan (SPP)</span>
                            <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-blue-400">
                                {{ categories.filter(c => c.is_recurring).length }}
                            </span>
                            <span class="text-xs text-slate-400">Pos Berulang</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Biaya Insidental</span>
                            <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-purple-400">
                                {{ categories.filter(c => !c.is_recurring).length }}
                            </span>
                            <span class="text-xs text-slate-400">Pos Sekali Bayar</span>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                    <div>
                        <h3 class="font-bold text-white text-sm">Daftar Kategori & Tarif Standar</h3>
                        <p class="text-xs text-slate-400">Tarif default akan otomatis mengisi form saat menerbitkan tagihan massal</p>
                    </div>

                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white shadow-lg shadow-teal-600/20 transition-all shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Pos Tarif</span>
                    </button>
                </div>

                <!-- Tabel Pos Tarif -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-300">
                            <thead class="bg-slate-950/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">Kode</th>
                                    <th class="px-6 py-4">Nama Pos Biaya</th>
                                    <th class="px-6 py-4">Tarif Standar</th>
                                    <th class="px-6 py-4">Sifat Tagihan</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Tagihan Terkait</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="item in categories" :key="item.id" class="hover:bg-slate-800/30 transition-colors">
                                    <!-- Kode -->
                                    <td class="px-6 py-4 font-mono font-bold whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs bg-slate-800 border border-slate-700 text-teal-400">
                                            {{ item.code }}
                                        </span>
                                    </td>

                                    <!-- Nama & Deskripsi -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-white text-base">{{ item.name }}</div>
                                        <div class="text-xs text-slate-400">{{ item.description || 'Tidak ada keterangan tambahan' }}</div>
                                    </td>

                                    <!-- Tarif Standar -->
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-white">
                                        {{ formatRupiah(item.default_amount) }}
                                    </td>

                                    <!-- Sifat Tagihan -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="item.is_recurring"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20"
                                        >
                                            Bulanan (Berulang)
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20"
                                        >
                                            Insidental (Sekali)
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span
                                            :class="[
                                                item.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-slate-500 border-slate-700',
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border'
                                            ]"
                                        >
                                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>

                                    <!-- Tagihan Terkait -->
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-xs text-slate-400">
                                        <span class="font-bold text-white">{{ item.bills_count }}</span> Tagihan
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <button
                                                @click="openEditModal(item)"
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700 text-xs font-medium"
                                                title="Edit Pos Tarif"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteCategory(item)"
                                                :disabled="item.bills_count > 0"
                                                :class="[
                                                    item.bills_count > 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-rose-500/20',
                                                    'px-2.5 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 transition-colors text-xs font-medium'
                                                ]"
                                                title="Hapus Pos Tarif"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="categories.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                        <p class="font-medium text-slate-400">Belum ada pos tarif yang terdaftar</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ===== MODAL TAMBAH / EDIT POS TARIF ===== -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl animate-scale-up">
            <!-- Header Modal -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-base">
                            {{ isEditing ? 'Edit Pos Tarif Biaya' : 'Tambah Pos Tarif Biaya Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400">Tentukan kode, nama pos biaya, dan tarif standar</p>
                    </div>
                </div>
                <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <div class="space-y-4">
                    <!-- Kode Pos Biaya -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Kode Pos Biaya <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="Contoh: SPP-BULANAN, DSP-GEDUNG"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-100 placeholder-slate-500 focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 uppercase"
                            required
                            maxlength="50"
                        />
                        <div v-if="form.errors.code" class="text-xs text-rose-400 mt-1">{{ form.errors.code }}</div>
                    </div>

                    <!-- Nama Pos Biaya -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Nama Pos Biaya <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: SPP Reguler Bulanan"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                            required
                            maxlength="100"
                        />
                        <div v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Tarif Standar -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Tarif Standar (Rp) <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model.number="form.default_amount"
                            type="number"
                            min="0"
                            step="1000"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 font-bold"
                            required
                        />
                        <div v-if="form.errors.default_amount" class="text-xs text-rose-400 mt-1">{{ form.errors.default_amount }}</div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Keterangan (Opsional)
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Catatan pos biaya..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                        ></textarea>
                    </div>

                    <!-- Checkbox Opsi -->
                    <div class="space-y-2 pt-1">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="form.is_recurring"
                                type="checkbox"
                                class="w-4 h-4 text-teal-500 bg-slate-950 border-slate-800 rounded focus:ring-teal-500 focus:ring-offset-slate-900"
                            />
                            <span class="text-sm font-medium text-slate-200">Tagihan Berulang (Bulanan seperti SPP)</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-4 h-4 text-teal-500 bg-slate-950 border-slate-800 rounded focus:ring-teal-500 focus:ring-offset-slate-900"
                            />
                            <span class="text-sm font-medium text-slate-200">Pos Tarif Aktif</span>
                        </label>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-xl text-sm font-semibold bg-teal-600 hover:bg-teal-500 text-white shadow-lg shadow-teal-600/20 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Pos Biaya' : 'Simpan Pos Biaya') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
