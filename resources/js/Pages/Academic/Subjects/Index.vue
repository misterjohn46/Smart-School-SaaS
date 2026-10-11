<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    subjects: Object,
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
const type = ref(props.filters.type || '');
const isActive = ref(props.filters.is_active !== null && props.filters.is_active !== undefined ? String(props.filters.is_active) : '');

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    code: '',
    name: '',
    kkm: 75,
    type: 'umum',
    is_active: true,
});

// Debounce Filter
let searchTimeout = null;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('academic.subjects.index'),
            {
                search: search.value || undefined,
                type: type.value || undefined,
                is_active: isActive.value !== '' ? isActive.value : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    }, 300);
};

watch([search, type, isActive], () => {
    applyFilters();
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.kkm = 75;
    form.type = 'umum';
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.code = item.code;
    form.name = item.name;
    form.kkm = item.kkm;
    form.type = item.type;
    form.is_active = Boolean(item.is_active);
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('academic.subjects.update', editingId.value), {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post(route('academic.subjects.store'), {
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const toggleSubjectStatus = (item) => {
    router.post(route('academic.subjects.toggle', item.id), {}, {
        preserveScroll: true,
    });
};

const deleteSubject = (item) => {
    if (confirm(`Apakah Anda yakin ingin menghapus mata pelajaran "${item.name}" (${item.code})?`)) {
        router.delete(route('academic.subjects.destroy', item.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Master Data Mata Pelajaran (Mapel) - Portal Sekolah" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <!-- Sidebar Terpadu -->
        <Sidebar :menus="adminMenus" sectionTitle="Master Data Akademik" />

        <!-- Konten Utama -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
            <!-- Header Atas -->
            <header class="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Master Mata Pelajaran (Mapel)</h1>
                        <p class="text-xs text-slate-400">Pengaturan kurikulum pelajaran, ambang KKM, dan kategori peminatan</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        {{ stats.active_count }} Mapel Aktif
                    </span>
                    <Link :href="route('dashboard')" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors border border-slate-700">
                        Dashboard
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

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Mapel</span>
                            <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-white">{{ stats.total }}</span>
                            <span class="text-xs text-slate-400">Pelajaran</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Muatan Nasional (Umum)</span>
                            <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-blue-400">{{ stats.umum_count }}</span>
                            <span class="text-xs text-slate-400">Mapel Wajib</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Peminatan Kejuruan</span>
                            <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-purple-400">{{ stats.kejuruan_count }}</span>
                            <span class="text-xs text-slate-400">Mapel Produktif</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Muatan Lokal (Mulok)</span>
                            <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-emerald-400">{{ stats.mulok_count }}</span>
                            <span class="text-xs text-slate-400">Mapel Mulok</span>
                        </div>
                    </div>
                </div>

                <!-- Kontrol Filter & Tambah Mapel -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
                    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <!-- Input Pencarian -->
                        <div class="relative w-full sm:w-64">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama atau kode mapel..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all pl-9"
                            />
                            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Filter Kategori -->
                        <select
                            v-model="type"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-amber-500 transition-all"
                        >
                            <option value="">Semua Kategori</option>
                            <option value="umum">Muatan Nasional (Umum)</option>
                            <option value="kejuruan">Peminatan Kejuruan</option>
                            <option value="mulok">Muatan Lokal (Mulok)</option>
                        </select>

                        <!-- Filter Status Aktif -->
                        <select
                            v-model="isActive"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-amber-500 transition-all"
                        >
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>

                    <!-- Tombol Tambah Mapel Baru -->
                    <button
                        @click="openCreateModal"
                        class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 shadow-lg shadow-amber-500/20 transition-all shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Mapel Baru</span>
                    </button>
                </div>

                <!-- Tabel Data Mapel -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-300">
                            <thead class="bg-slate-950/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">Kode</th>
                                    <th class="px-6 py-4">Nama Mata Pelajaran</th>
                                    <th class="px-6 py-4 text-center">Ambang KKM</th>
                                    <th class="px-6 py-4">Kategori Pelajaran</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="item in subjects.data" :key="item.id" class="hover:bg-slate-800/30 transition-colors">
                                    <!-- Kode Mapel -->
                                    <td class="px-6 py-4 font-mono font-bold whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs bg-slate-800 border border-slate-700 text-indigo-400">
                                            {{ item.code }}
                                        </span>
                                    </td>

                                    <!-- Nama Mata Pelajaran -->
                                    <td class="px-6 py-4 font-medium text-white whitespace-nowrap">
                                        <div class="font-bold text-white text-base">{{ item.name }}</div>
                                        <div class="text-xs text-slate-400">ID Pelajaran: #{{ item.id }}</div>
                                    </td>

                                    <!-- Ambang KKM -->
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            {{ item.kkm }}
                                        </span>
                                    </td>

                                    <!-- Kategori -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="item.type === 'umum'"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20"
                                        >
                                            Muatan Nasional (Umum)
                                        </span>
                                        <span
                                            v-else-if="item.type === 'kejuruan'"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20"
                                        >
                                            Peminatan Kejuruan
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20"
                                        >
                                            Muatan Lokal (Mulok)
                                        </span>
                                    </td>

                                    <!-- Status Aktif (Toggle Interaktif) -->
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <button
                                            @click="toggleSubjectStatus(item)"
                                            :class="[
                                                item.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700 hover:bg-slate-700',
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition-all cursor-pointer'
                                            ]"
                                            title="Klik untuk ubah status aktif"
                                        >
                                            <span :class="['w-1.5 h-1.5 rounded-full', item.is_active ? 'bg-emerald-400' : 'bg-slate-500']"></span>
                                            <span>{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <button
                                                @click="openEditModal(item)"
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700 text-xs font-medium"
                                                title="Edit Data Mapel"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteSubject(item)"
                                                class="px-2.5 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition-colors text-xs font-medium"
                                                title="Hapus Mapel"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="subjects.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        <p class="font-medium text-slate-400">Tidak ada mata pelajaran yang ditemukan</p>
                                        <p class="text-xs mt-1">Coba sesuaikan kata kunci pencarian atau filter kategori</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="subjects.links && subjects.links.length > 3" class="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                        <div>
                            Menampilkan <span class="font-semibold text-slate-200">{{ subjects.from || 0 }}</span> - <span class="font-semibold text-slate-200">{{ subjects.to || 0 }}</span> dari <span class="font-semibold text-slate-200">{{ subjects.total }}</span> mapel
                        </div>
                        <div class="flex items-center gap-1">
                            <template v-for="(link, i) in subjects.links" :key="i">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        link.active ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800/80 text-slate-400 hover:bg-slate-800 hover:text-slate-200',
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

    <!-- ===== MODAL TAMBAH / EDIT MAPEL ===== -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl animate-scale-up">
            <!-- Header Modal -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-base">
                            {{ isEditing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400">Tentukan kode unik, ambang KKM, dan tipe peminatan kurikulum</p>
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
                    <!-- Kode Mapel -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Kode Mata Pelajaran <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="Contoh: RPL-01, MAT-01, BIN-01"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-100 placeholder-slate-500 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 uppercase"
                            required
                            maxlength="20"
                        />
                        <div v-if="form.errors.code" class="text-xs text-rose-400 mt-1">{{ form.errors.code }}</div>
                    </div>

                    <!-- Nama Mapel -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Nama Mata Pelajaran <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: Pemrograman Web & Perangkat Bergerak"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            required
                            maxlength="100"
                        />
                        <div v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- KKM & Kategori -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                KKM (0 - 100) <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model.number="form.kkm"
                                type="number"
                                min="0"
                                max="100"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                required
                            />
                            <div v-if="form.errors.kkm" class="text-xs text-rose-400 mt-1">{{ form.errors.kkm }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Kategori Pelajaran <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.type"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-amber-500"
                                required
                            >
                                <option value="umum">Muatan Nasional (Umum)</option>
                                <option value="kejuruan">Peminatan Kejuruan</option>
                                <option value="mulok">Muatan Lokal (Mulok)</option>
                            </select>
                            <div v-if="form.errors.type" class="text-xs text-rose-400 mt-1">{{ form.errors.type }}</div>
                        </div>
                    </div>

                    <!-- Status Aktif -->
                    <div class="pt-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-4 h-4 text-amber-500 bg-slate-950 border-slate-800 rounded focus:ring-amber-500 focus:ring-offset-slate-900"
                            />
                            <span class="text-sm font-medium text-slate-200">Mata Pelajaran Aktif untuk Jadwal & Presensi</span>
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
                        class="px-5 py-2 rounded-xl text-sm font-semibold bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-lg shadow-amber-500/20 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Mapel' : 'Simpan Mapel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
