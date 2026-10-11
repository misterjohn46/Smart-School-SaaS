<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    bills: Object,
    classrooms: Array,
    gradeLevels: Array,
    feeCategories: Array,
    academicYears: Array,
    filters: Object,
    stats: Object,
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

// Reactive Filters
const search = ref(props.filters.search || '');
const classroomId = ref(props.filters.classroom_id || '');
const feeCategoryId = ref(props.filters.fee_category_id || '');
const status = ref(props.filters.status || '');
const month = ref(props.filters.month || '');
const year = ref(props.filters.year || '');

// Modal Batch Generator
const isBatchModalOpen = ref(false);

const months = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;

const batchForm = useForm({
    fee_category_id: props.feeCategories[0]?.id || '',
    academic_year_id: props.academicYears.find(y => y.is_active)?.id || props.academicYears[0]?.id || '',
    target_type: 'classroom',
    classroom_id: props.classrooms[0]?.id || '',
    grade_level_id: props.gradeLevels[0]?.id || '',
    title: '',
    month: currentMonth,
    year: currentYear,
    amount: props.feeCategories[0]?.default_amount || 250000,
    due_date: new Date(currentYear, currentMonth - 1, 10).toISOString().split('T')[0],
    notes: '',
});

// Update title and default amount automatically when category or month changes
const updateBatchPresets = () => {
    const selectedCategory = props.feeCategories.find(c => c.id === Number(batchForm.fee_category_id));
    const selectedMonth = months.find(m => m.value === Number(batchForm.month));
    
    if (selectedCategory) {
        batchForm.amount = Number(selectedCategory.default_amount) || 250000;
        if (selectedCategory.is_recurring && selectedMonth) {
            batchForm.title = `${selectedCategory.name} Bulan ${selectedMonth.label} ${batchForm.year}`;
        } else {
            batchForm.title = `${selectedCategory.name} Tahun ${batchForm.year}`;
        }
    }
};

const openBatchModal = () => {
    batchForm.reset();
    batchForm.fee_category_id = props.feeCategories[0]?.id || '';
    batchForm.academic_year_id = props.academicYears.find(y => y.is_active)?.id || props.academicYears[0]?.id || '';
    batchForm.target_type = 'classroom';
    batchForm.classroom_id = props.classrooms[0]?.id || '';
    batchForm.month = currentMonth;
    batchForm.year = currentYear;
    updateBatchPresets();
    isBatchModalOpen.value = true;
};

const closeBatchModal = () => {
    isBatchModalOpen.value = false;
    batchForm.reset();
    batchForm.clearErrors();
};

const submitBatchBilling = () => {
    batchForm.post(route('finance.bills.batch'), {
        onSuccess: () => {
            closeBatchModal();
        },
    });
};

const deleteBill = (item) => {
    if (item.paid_amount > 0) {
        alert(`Tagihan ${item.bill_number} tidak dapat dihapus karena sudah memiliki pembayaran sebesar ${formatRupiah(item.paid_amount)}.`);
        return;
    }

    if (confirm(`Apakah Anda yakin ingin menghapus tagihan "${item.title}" untuk ${item.student_name}?`)) {
        router.delete(route('finance.bills.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

// Debounce Filter
let searchTimeout = null;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('finance.bills.index'),
            {
                search: search.value || undefined,
                classroom_id: classroomId.value || undefined,
                fee_category_id: feeCategoryId.value || undefined,
                status: status.value || undefined,
                month: month.value || undefined,
                year: year.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    }, 300);
};

watch([search, classroomId, feeCategoryId, status, month, year], () => {
    applyFilters();
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val) || 0);
};
</script>

<template>
    <Head title="Manajemen Tagihan & SPP Massal - Portal Sekolah" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <!-- Sidebar Terpadu -->
        <Sidebar :menus="adminMenus" sectionTitle="Operasional Sekolah" />

        <!-- Konten Utama -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
            <!-- Header Atas -->
            <header class="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Manajemen Tagihan Siswa & SPP Massal</h1>
                        <p class="text-xs text-slate-400">Penerbitan tagihan serentak, kontrol piutang SPP, dan status pelunasan</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('finance.fee-categories.index')"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors border border-slate-700 inline-flex items-center gap-1.5"
                    >
                        <span>Pos Tarif Biaya</span>
                    </Link>
                    <Link
                        :href="route('finance.pos')"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white transition-colors shadow-sm inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Buka POS Kasir</span>
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

                <!-- KPI Metric Cards Finansial -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Terbit Tagihan</span>
                            <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-black text-white truncate">{{ formatRupiah(stats.total_billed) }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ stats.total_bills }} Tagihan Diterbitkan</div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kas Masuk (Terbayar)</span>
                            <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-black text-emerald-400 truncate">{{ formatRupiah(stats.total_collected) }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ stats.paid_count }} Tagihan Telah Lunas</div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Piutang Tertunggak</span>
                            <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-black text-rose-400 truncate">{{ formatRupiah(stats.total_arrears) }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ stats.unpaid_count }} Tagihan Belum Lunas</div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rasio Pelunasan</span>
                            <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-black text-amber-400">
                                {{ stats.total_billed > 0 ? Math.round((stats.total_collected / stats.total_billed) * 100) : 0 }}%
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5">Tingkat Kolektibilitas Tagihan</div>
                        </div>
                    </div>
                </div>

                <!-- Kontrol Filter & Generator Tagihan -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
                    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <!-- Input Pencarian -->
                        <div class="relative w-full sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama, NISN, no invoice..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all pl-9"
                            />
                            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Filter Kelas -->
                        <select
                            v-model="classroomId"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="">Semua Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>

                        <!-- Filter Kategori Biaya -->
                        <select
                            v-model="feeCategoryId"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in feeCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>

                        <!-- Filter Status -->
                        <select
                            v-model="status"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="">Semua Status</option>
                            <option value="unpaid">Belum Lunas</option>
                            <option value="partial">Bayar Sebagian</option>
                            <option value="paid">Lunas</option>
                        </select>

                        <!-- Filter Bulan -->
                        <select
                            v-model="month"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="">Semua Bulan</option>
                            <option v-for="m in months" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Tombol Terbitkan Tagihan Massal -->
                    <button
                        @click="openBatchModal"
                        class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-lg shadow-emerald-600/20 transition-all shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Terbitkan Tagihan Massal</span>
                    </button>
                </div>

                <!-- Tabel Tagihan Siswa -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-300">
                            <thead class="bg-slate-950/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">Nomor & Judul Tagihan</th>
                                    <th class="px-6 py-4">Siswa & Rombel</th>
                                    <th class="px-6 py-4">Nominal</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4">Jatuh Tempo</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="item in bills.data" :key="item.id" class="hover:bg-slate-800/30 transition-colors">
                                    <!-- Nomor & Judul -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-mono text-xs text-indigo-400 font-bold">{{ item.bill_number }}</div>
                                        <div class="font-bold text-white text-sm mt-0.5">{{ item.title }}</div>
                                        <div class="text-xs text-slate-400">{{ item.category_name }}</div>
                                    </td>

                                    <!-- Siswa & Kelas -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-100">{{ item.student_name }}</div>
                                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                            <span>NISN: {{ item.student_nisn }}</span>
                                            <span>•</span>
                                            <span class="text-indigo-400 font-medium">{{ item.classroom_name }}</span>
                                        </div>
                                    </td>

                                    <!-- Nominal -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-white">{{ formatRupiah(item.amount) }}</div>
                                        <div v-if="item.status === 'partial'" class="text-xs text-amber-400 mt-0.5">
                                            Sisa: {{ formatRupiah(item.remaining_amount) }}
                                        </div>
                                        <div v-else-if="item.status === 'paid'" class="text-xs text-emerald-400 mt-0.5">
                                            Lunas
                                        </div>
                                        <div v-else class="text-xs text-rose-400 mt-0.5">
                                            Belum Terbayar
                                        </div>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span
                                            v-if="item.status === 'paid'"
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                                        >
                                            Lunas
                                        </span>
                                        <span
                                            v-else-if="item.status === 'partial'"
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20"
                                        >
                                            Sebagian
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20"
                                        >
                                            Belum Bayar
                                        </span>
                                    </td>

                                    <!-- Jatuh Tempo -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-300">
                                        {{ item.due_date_formatted }}
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <Link
                                                v-if="item.status !== 'paid'"
                                                :href="route('finance.pos')"
                                                class="px-2.5 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 transition-colors text-xs font-semibold"
                                                title="Bayar di POS Kasir"
                                            >
                                                Bayar
                                            </Link>
                                            <button
                                                @click="deleteBill(item)"
                                                :disabled="item.paid_amount > 0"
                                                :class="[
                                                    item.paid_amount > 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-rose-500/20',
                                                    'px-2.5 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 transition-colors text-xs font-medium'
                                                ]"
                                                title="Hapus Tagihan"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="bills.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="font-medium text-slate-400">Tidak ada tagihan siswa yang cocok dengan filter</p>
                                        <p class="text-xs mt-1">Gunakan tombol "Terbitkan Tagihan Massal" untuk membuat tagihan baru</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="bills.links && bills.links.length > 3" class="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                        <div>
                            Menampilkan <span class="font-semibold text-slate-200">{{ bills.from || 0 }}</span> - <span class="font-semibold text-slate-200">{{ bills.to || 0 }}</span> dari <span class="font-semibold text-slate-200">{{ bills.total }}</span> tagihan
                        </div>
                        <div class="flex items-center gap-1">
                            <template v-for="(link, i) in bills.links" :key="i">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        link.active ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-800/80 text-slate-400 hover:bg-slate-800 hover:text-slate-200',
                                        'px-3 py-1.5 rounded-lg border border-slate-700/50 transition-colors'
                                    ]"
                                    v-html="link.label"
                                    preserve-scroll
                                />
                                <span
                                    v-else
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-slate-600 border border-slate-800 cursor-not-allowed opacity-50'
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ===== MODAL GENERATOR TAGIHAN MASSAL (BATCH BILLING) ===== -->
    <div v-if="isBatchModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl animate-scale-up">
            <!-- Header Modal -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-base">Terbitkan Tagihan Siswa Massal (Batch 1-Klik)</h3>
                        <p class="text-xs text-slate-400">Generate tagihan untuk rombel kelas atau seluruh angkatan sekaligus</p>
                    </div>
                </div>
                <button @click="closeBatchModal" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitBatchBilling" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Pos Biaya -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Kategori / Pos Biaya <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="batchForm.fee_category_id"
                            @change="updateBatchPresets"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                            required
                        >
                            <option v-for="cat in feeCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }} ({{ cat.is_recurring ? 'Bulanan' : 'Insidental' }})
                            </option>
                        </select>
                        <div v-if="batchForm.errors.fee_category_id" class="text-xs text-rose-400 mt-1">{{ batchForm.errors.fee_category_id }}</div>
                    </div>

                    <!-- Tahun Ajaran -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Tahun Ajaran <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="batchForm.academic_year_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                            required
                        >
                            <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                                {{ yr.name }} {{ yr.is_active ? '(Aktif)' : '' }}
                            </option>
                        </select>
                        <div v-if="batchForm.errors.academic_year_id" class="text-xs text-rose-400 mt-1">{{ batchForm.errors.academic_year_id }}</div>
                    </div>

                    <!-- Target Siswa -->
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Target Siswa Penerima Tagihan <span class="text-rose-400">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <label :class="[
                                batchForm.target_type === 'classroom' ? 'bg-emerald-500/10 border-emerald-500 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-400',
                                'border rounded-xl p-2.5 flex items-center gap-2 cursor-pointer transition-all text-xs font-semibold'
                            ]">
                                <input type="radio" v-model="batchForm.target_type" value="classroom" class="text-emerald-500 focus:ring-0" />
                                <span>Per Rombel Kelas</span>
                            </label>

                            <label :class="[
                                batchForm.target_type === 'grade_level' ? 'bg-emerald-500/10 border-emerald-500 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-400',
                                'border rounded-xl p-2.5 flex items-center gap-2 cursor-pointer transition-all text-xs font-semibold'
                            ]">
                                <input type="radio" v-model="batchForm.target_type" value="grade_level" class="text-emerald-500 focus:ring-0" />
                                <span>Per Tingkat Kelas</span>
                            </label>

                            <label :class="[
                                batchForm.target_type === 'all' ? 'bg-emerald-500/10 border-emerald-500 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-400',
                                'border rounded-xl p-2.5 flex items-center gap-2 cursor-pointer transition-all text-xs font-semibold'
                            ]">
                                <input type="radio" v-model="batchForm.target_type" value="all" class="text-emerald-500 focus:ring-0" />
                                <span>Semua Siswa Aktif</span>
                            </label>
                        </div>
                    </div>

                    <!-- Dropdown Target Kelas -->
                    <div v-if="batchForm.target_type === 'classroom'" class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Pilih Rombongan Belajar (Kelas) <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="batchForm.classroom_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                            required
                        >
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Dropdown Target Tingkat -->
                    <div v-if="batchForm.target_type === 'grade_level'" class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Pilih Tingkat Kelas <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="batchForm.grade_level_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                            required
                        >
                            <option v-for="lvl in gradeLevels" :key="lvl.id" :value="lvl.id">
                                {{ lvl.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Judul Tagihan -->
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Judul Tagihan <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="batchForm.title"
                            type="text"
                            placeholder="Contoh: SPP Bulan Oktober 2026"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                            required
                        />
                        <div v-if="batchForm.errors.title" class="text-xs text-rose-400 mt-1">{{ batchForm.errors.title }}</div>
                    </div>

                    <!-- Bulan & Tahun -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Bulan Pembayaran
                        </label>
                        <select
                            v-model="batchForm.month"
                            @change="updateBatchPresets"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option v-for="m in months" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Tahun <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model.number="batchForm.year"
                            @change="updateBatchPresets"
                            type="number"
                            min="2020"
                            max="2035"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
                            required
                        />
                    </div>

                    <!-- Nominal Tagihan & Jatuh Tempo -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Nominal Tagihan per Siswa (Rp) <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model.number="batchForm.amount"
                            type="number"
                            min="1000"
                            step="1000"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-emerald-500 font-bold"
                            required
                        />
                        <div v-if="batchForm.errors.amount" class="text-xs text-rose-400 mt-1">{{ batchForm.errors.amount }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Jatuh Tempo (Due Date)
                        </label>
                        <input
                            v-model="batchForm.due_date"
                            type="date"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <!-- Info Peringatan Idempoten -->
                <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-xs text-indigo-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Sistem secara otomatis memeriksa duplikasi. Siswa yang sudah memiliki tagihan pada pos dan bulan yang sama akan otomatis dilewati.</span>
                </div>

                <!-- Footer Modal -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="closeBatchModal"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="batchForm.processing"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/20 transition-all disabled:opacity-50 inline-flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>{{ batchForm.processing ? 'Menerbitkan...' : 'Terbitkan Tagihan Sekarang' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
