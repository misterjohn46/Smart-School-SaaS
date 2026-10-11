<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    departments: Object,
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

// Filters
const search = ref(props.filters.search || '');
const isActive = ref(props.filters.is_active ?? '');

const applyFilters = () => {
    router.get(
        route('academic.departments.index'),
        {
            search: search.value,
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
    isActive.value = '';
    applyFilters();
};

// Modal State: Create / Edit
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.code = item.code;
    form.name = item.name;
    form.description = item.description === '-' ? '' : item.description;
    form.is_active = item.is_active;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('academic.departments.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('academic.departments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const toggleStatus = (item) => {
    router.post(route('academic.departments.toggle-status', item.id), {}, {
        preserveScroll: true,
    });
};

const deleteDepartment = (item) => {
    if (confirm(`Yakin ingin menghapus program keahlian "${item.name} (${item.code})"?`)) {
        router.delete(route('academic.departments.destroy', item.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Master Program Keahlian (Jurusan) - Portal Sekolah" />

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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Master Program Keahlian & Jurusan</h1>
                        <p class="text-xs text-slate-400">Manajemen program kejuruan, peminatan, kode kompetensi & rombel binaan</p>
                    </div>
                </div>

                <button
                    @click="openCreateModal"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/20 flex items-center gap-2 transition"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Jurusan</span>
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
                <!-- Statistik Card -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Total Program Keahlian</p>
                            <h3 class="text-xl font-bold text-white">{{ stats.total_departments }} Jurusan</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Jurusan Aktif</p>
                            <h3 class="text-xl font-bold text-emerald-400">{{ stats.active_departments }} Jurusan</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-slate-700/30 text-slate-400 border border-slate-700/50">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Jurusan Nonaktif</p>
                            <h3 class="text-xl font-bold text-slate-400">{{ stats.inactive_departments }} Jurusan</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Total Kelas Terdaftar</p>
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
                                placeholder="Cari nama atau kode jurusan..."
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 pl-8"
                            />
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <select
                            v-model="isActive"
                            @change="applyFilters"
                            class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>

                        <button
                            v-if="search || isActive !== ''"
                            @click="resetFilters"
                            class="text-xs text-slate-400 hover:text-white px-2 py-1 transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Tabel Data Program Keahlian / Jurusan -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Kode</th>
                                    <th class="px-4 py-3 font-semibold">Nama Program Keahlian</th>
                                    <th class="px-4 py-3 font-semibold">Deskripsi</th>
                                    <th class="px-4 py-3 font-semibold text-center">Kelas Binaan</th>
                                    <th class="px-4 py-3 font-semibold text-center">Status</th>
                                    <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr
                                    v-for="item in departments.data"
                                    :key="item.id"
                                    class="hover:bg-slate-800/40 transition"
                                >
                                    <!-- Kode -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-black tracking-wide bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                            {{ item.code }}
                                        </span>
                                    </td>

                                    <!-- Nama Jurusan -->
                                    <td class="px-4 py-3 font-semibold text-white">
                                        {{ item.name }}
                                    </td>

                                    <!-- Deskripsi -->
                                    <td class="px-4 py-3 text-slate-400 max-w-xs truncate">
                                        {{ item.description }}
                                    </td>

                                    <!-- Rombel Binaan -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-800 border border-slate-700 text-slate-200">
                                            {{ item.classrooms_count }} Kelas
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <button
                                            @click="toggleStatus(item)"
                                            :class="[
                                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold border transition',
                                                item.is_active
                                                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 hover:bg-rose-500/10 hover:text-rose-400 hover:border-rose-500/20'
                                                    : 'bg-slate-700/50 text-slate-400 border-slate-600 hover:bg-emerald-500/10 hover:text-emerald-400'
                                            ]"
                                            :title="item.is_active ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan'"
                                        >
                                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="openEditModal(item)"
                                                class="p-1 rounded text-amber-400 hover:text-amber-300 hover:bg-amber-400/10 transition"
                                                title="Edit Jurusan"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <button
                                                @click="deleteDepartment(item)"
                                                class="p-1 rounded text-rose-400 hover:text-rose-300 hover:bg-rose-400/10 transition"
                                                title="Hapus Jurusan"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!departments.data || departments.data.length === 0">
                                    <td colspan="6" class="text-center py-10 text-slate-500">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z" />
                                            </svg>
                                            <p class="text-sm">Belum ada data program keahlian / jurusan.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="departments.links && departments.links.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <div>
                            Menampilkan {{ departments.from || 0 }} - {{ departments.to || 0 }} dari {{ departments.total || 0 }} data
                        </div>
                        <div class="flex items-center gap-1">
                            <Link
                                v-for="(link, i) in departments.links"
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
    <!-- MODAL TAMBAH / EDIT PROGRAM KEAHLIAN -->
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">
                            {{ isEditing ? 'Edit Program Keahlian' : 'Tambah Program Keahlian Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400">Masukkan kode jurusan dan kompetensi keahlian</p>
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
                    <label class="block text-xs font-medium text-slate-300 mb-1">Kode Jurusan *</label>
                    <input
                        type="text"
                        v-model="form.code"
                        required
                        placeholder="Contoh: RPL, TKJ, DKV, IPA"
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 uppercase"
                    />
                    <div v-if="form.errors.code" class="text-rose-400 text-[11px] mt-1">{{ form.errors.code }}</div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Nama Program Keahlian / Jurusan *</label>
                    <input
                        type="text"
                        v-model="form.name"
                        required
                        placeholder="Contoh: Rekayasa Perangkat Lunak"
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    />
                    <div v-if="form.errors.name" class="text-rose-400 text-[11px] mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Deskripsi / Keterangan Kejuruan</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Keterangan kompetensi kejuruan..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    ></textarea>
                    <div v-if="form.errors.description" class="text-rose-400 text-[11px] mt-1">{{ form.errors.description }}</div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-white block">Status Jurusan Aktif</span>
                        <span class="text-[11px] text-slate-400">Jurusan aktif dapat dipilih saat pembuatan rombel kelas</span>
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
