<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    academicYears: Object,
    activeYear: Object,
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
    { label: 'Tata Tertib & BK', route: 'counseling.violations.index', icon: 'clipboard-check' },
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

// Reactive Filter
const search = ref(props.filters.search || '');
const semester = ref(props.filters.semester || '');
const isActive = ref(props.filters.is_active ?? '');

const applyFilters = () => {
    router.get(
        route('academic.academic-years.index'),
        {
            search: search.value,
            semester: semester.value,
            is_active: isActive.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const resetFilters = () => {
    search.value = '';
    semester.value = '';
    isActive.value = '';
    applyFilters();
};

// Modal State: Create / Edit
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    semester: 'ganjil',
    start_date: '',
    end_date: '',
    is_active: false,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.semester = 'ganjil';
    form.is_active = false;
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.name = item.name;
    form.semester = item.semester;
    form.start_date = item.start_date || '';
    form.end_date = item.end_date || '';
    form.is_active = item.is_active;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('academic.academic-years.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('academic.academic-years.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const activateYear = (item) => {
    if (confirm(`Aktifkan tahun ajaran "${item.name} (${item.semester_label})" sebagai tahun ajaran operasional utama?`)) {
        router.post(route('academic.academic-years.activate', item.id), {}, {
            preserveScroll: true,
        });
    }
};

const deleteYear = (item) => {
    if (confirm(`Yakin ingin menghapus tahun ajaran "${item.name} (${item.semester_label})"?`)) {
        router.delete(route('academic.academic-years.destroy', item.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Master Tahun Ajaran & Semester - Portal Sekolah" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <!-- Sidebar Terpadu -->
        <Sidebar :menus="adminMenus" sectionTitle="Master Data Akademik" />

        <!-- Konten Utama -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
            <!-- Header Atas -->
            <header class="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Master Tahun Ajaran & Semester</h1>
                        <p class="text-xs text-slate-400">Pengaturan tahun kalender akademik, semester aktif & rentang tanggal efektif</p>
                    </div>
                </div>

                <button
                    @click="openCreateModal"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/20 flex items-center gap-2 transition"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Tahun Ajaran</span>
                </button>
            </header>

            <!-- Status Flash Notification -->
            <div v-if="$page.props.flash?.status" class="mx-6 mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ $page.props.flash.status }}</span>
                </div>
            </div>

            <!-- Error Flash Notification -->
            <div v-if="$page.props.errors?.error" class="mx-6 mt-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ $page.props.errors.error }}</span>
                </div>
            </div>

            <main class="p-6 space-y-6">
                <!-- Banner Highlight Tahun Ajaran Aktif Saat Ini -->
                <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-950/40 via-slate-900/80 to-slate-900/60 border border-emerald-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Tahun Ajaran Aktif Operasional</span>
                        </div>
                        <h2 class="text-2xl font-black text-white">
                            {{ activeYear ? `${activeYear.name} - Semester ${activeYear.semester === 'ganjil' ? 'Ganjil' : 'Genap'}` : 'Belum Ditetapkan' }}
                        </h2>
                        <p class="text-xs text-slate-400 flex items-center gap-3">
                            <span>📅 Periode: {{ activeYear?.start_date || 'Belum diatur' }} s/d {{ activeYear?.end_date || 'Belum diatur' }}</span>
                            <span>•</span>
                            <span>🏫 {{ stats.active_classrooms_count }} Rombel Kelas Terdaftar di Periode Ini</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-3.5 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-500/30 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Status: Aktif Berjalan</span>
                        </span>
                    </div>
                </div>

                <!-- Kartu Statistik -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Total Riwayat Tahun Ajaran</p>
                            <h3 class="text-xl font-bold text-white">{{ stats.total_years }} Periode</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Rombel di Periode Aktif</p>
                            <h3 class="text-xl font-bold text-emerald-400">{{ stats.active_classrooms_count }} Kelas</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Total Seluruh Rombel Master</p>
                            <h3 class="text-xl font-bold text-blue-400">{{ stats.total_classrooms }} Kelas</h3>
                        </div>
                    </div>
                </div>

                <!-- Filter & Search Bar -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                        <div class="relative w-full sm:w-64">
                            <input
                                type="text"
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Cari tahun ajaran..."
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 pl-8"
                            />
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <select
                            v-model="semester"
                            @change="applyFilters"
                            class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Semua Semester</option>
                            <option value="ganjil">Ganjil</option>
                            <option value="genap">Genap</option>
                        </select>

                        <select
                            v-model="isActive"
                            @change="applyFilters"
                            class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="1">Sedang Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>

                        <button
                            v-if="search || semester || isActive !== ''"
                            @click="resetFilters"
                            class="text-xs text-slate-400 hover:text-white px-2 py-1 transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Tabel Data Tahun Ajaran -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Tahun Ajaran</th>
                                    <th class="px-4 py-3 font-semibold">Semester</th>
                                    <th class="px-4 py-3 font-semibold">Periode Kalender</th>
                                    <th class="px-4 py-3 font-semibold text-center">Rombel Kelas</th>
                                    <th class="px-4 py-3 font-semibold text-center">Status</th>
                                    <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr
                                    v-for="item in academicYears.data"
                                    :key="item.id"
                                    :class="[
                                        'hover:bg-slate-800/40 transition',
                                        item.is_active ? 'bg-emerald-950/10' : ''
                                    ]"
                                >
                                    <!-- Tahun Ajaran -->
                                    <td class="px-4 py-3 font-semibold text-white">
                                        <div class="flex items-center gap-2">
                                            <span>{{ item.name }}</span>
                                            <span
                                                v-if="item.is_active"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"
                                            >
                                                AKTIF SAAT INI
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Semester -->
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold border',
                                                item.semester === 'ganjil'
                                                    ? 'bg-sky-500/10 text-sky-400 border-sky-500/20'
                                                    : 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                                            ]"
                                        >
                                            {{ item.semester_label }}
                                        </span>
                                    </td>

                                    <!-- Periode Kalender -->
                                    <td class="px-4 py-3 text-slate-300">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ item.start_date_formatted }}</span>
                                            <span class="text-slate-500">→</span>
                                            <span>{{ item.end_date_formatted }}</span>
                                        </div>
                                    </td>

                                    <!-- Rombel Kelas -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-800 border border-slate-700 text-slate-200">
                                            {{ item.classrooms_count }} Rombel
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            v-if="!item.is_active"
                                            @click="activateYear(item)"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-800 hover:bg-emerald-600/30 text-slate-400 hover:text-emerald-300 border border-slate-700 hover:border-emerald-500/40 transition"
                                            title="Klik untuk menjadikan tahun ajaran ini aktif"
                                        >
                                            Jadikan Aktif
                                        </button>
                                        <span
                                            v-else
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1"
                                        >
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Aktif</span>
                                        </span>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="openEditModal(item)"
                                                class="p-1 rounded text-amber-400 hover:text-amber-300 hover:bg-amber-400/10 transition"
                                                title="Edit Data"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <button
                                                v-if="!item.is_active"
                                                @click="deleteYear(item)"
                                                class="p-1 rounded text-rose-400 hover:text-rose-300 hover:bg-rose-400/10 transition"
                                                title="Hapus Tahun Ajaran"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!academicYears.data || academicYears.data.length === 0">
                                    <td colspan="6" class="text-center py-10 text-slate-500">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-sm">Belum ada data tahun ajaran.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="academicYears.links && academicYears.links.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <div>
                            Menampilkan {{ academicYears.from || 0 }} - {{ academicYears.to || 0 }} dari {{ academicYears.total || 0 }} data
                        </div>
                        <div class="flex items-center gap-1">
                            <Link
                                v-for="(link, i) in academicYears.links"
                                :key="i"
                                :href="link.url || '#'"
                                :class="[
                                    'px-2.5 py-1 rounded text-xs transition',
                                    link.active ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-slate-800 text-slate-300',
                                    !link.url ? 'opacity-40 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL TAMBAH / EDIT TAHUN AJARAN -->
    <!-- ======================================================== -->
    <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
    >
        <div class="relative w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 text-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">
                            {{ isEditing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400">Atur kalender semester dan status keaktifan sistem</p>
                    </div>
                </div>
                <button
                    @click="showModal = false"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4 mt-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Nama Tahun Ajaran *</label>
                    <input
                        type="text"
                        v-model="form.name"
                        required
                        placeholder="Contoh: 2024/2025"
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    />
                    <div v-if="form.errors.name" class="text-rose-400 text-[11px] mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Semester *</label>
                    <select
                        v-model="form.semester"
                        required
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    >
                        <option value="ganjil">Semester Ganjil</option>
                        <option value="genap">Semester Genap</option>
                    </select>
                    <div v-if="form.errors.semester" class="text-rose-400 text-[11px] mt-1">{{ form.errors.semester }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tanggal Mulai Semester</label>
                        <input
                            type="date"
                            v-model="form.start_date"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tanggal Selesai Semester</label>
                        <input
                            type="date"
                            v-model="form.end_date"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        />
                        <div v-if="form.errors.end_date" class="text-rose-400 text-[11px] mt-1">{{ form.errors.end_date }}</div>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-white block">Tahun Ajaran Aktif</span>
                        <span class="text-[11px] text-slate-400">Jadikan periode ini sebagai acuan aktif KBM, presensi & nilai</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button
                        type="button"
                        @click="showModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/20 transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui' : 'Simpan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
