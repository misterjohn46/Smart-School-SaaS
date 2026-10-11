<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    classrooms: Object,
    academicYears: Array,
    gradeLevels: Array,
    departments: Array,
    teachers: Array,
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
const gradeLevelId = ref(props.filters.grade_level_id || '');
const departmentId = ref(props.filters.department_id || '');
const academicYearId = ref(props.filters.academic_year_id || '');

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    academic_year_id: props.activeYear?.id || (props.academicYears[0]?.id ?? ''),
    grade_level_id: '',
    department_id: '',
    homeroom_teacher_id: '',
    capacity: 36,
});

// Debounce Filter
let searchTimeout = null;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('academic.classrooms.index'),
            {
                search: search.value || undefined,
                grade_level_id: gradeLevelId.value || undefined,
                department_id: departmentId.value || undefined,
                academic_year_id: academicYearId.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    }, 300);
};

watch([search, gradeLevelId, departmentId, academicYearId], () => {
    applyFilters();
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.academic_year_id = props.activeYear?.id || (props.academicYears[0]?.id ?? '');
    form.capacity = 36;
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.name = item.name;
    form.academic_year_id = item.academic_year_id;
    form.grade_level_id = item.grade_level_id;
    form.department_id = item.department_id || '';
    form.homeroom_teacher_id = item.homeroom_teacher_id || '';
    form.capacity = item.capacity;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('academic.classrooms.update', editingId.value), {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post(route('academic.classrooms.store'), {
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const deleteClassroom = (item) => {
    if (item.students_count > 0) {
        alert(`Kelas ${item.name} tidak dapat dihapus karena memiliki ${item.students_count} siswa terdaftar. Silakan pindahkan siswa terlebih dahulu.`);
        return;
    }

    if (confirm(`Apakah Anda yakin ingin menghapus kelas "${item.name}"?`)) {
        router.delete(route('academic.classrooms.destroy', item.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Master Data Rombongan Belajar (Kelas) - Portal Sekolah" />

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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Master Rombel & Ruang Kelas</h1>
                        <p class="text-xs text-slate-400">Manajemen rombongan belajar, penetapan wali kelas, dan kuota kapasitas</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="activeYear" class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        TA Aktif: {{ activeYear.name }} ({{ activeYear.semester }})
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

                <div v-if="$page.props.errors?.error" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 flex items-center justify-between text-sm animate-fade-in">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $page.props.errors.error }}</span>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Rombel</span>
                            <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-white">{{ stats.total_classrooms }}</span>
                            <span class="text-xs text-slate-400">Kelas Aktif</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Siswa Terdaftar</span>
                            <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-emerald-400">{{ stats.total_students }}</span>
                            <span class="text-xs text-slate-400">Siswa di Rombel</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Wali Kelas Terisi</span>
                            <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-amber-400">{{ stats.assigned_homeroom }}</span>
                            <span class="text-xs text-slate-400">/ {{ stats.total_classrooms }} Rombel</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Program Keahlian</span>
                            <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-purple-400">{{ stats.total_departments }}</span>
                            <span class="text-xs text-slate-400">Jurusan Terdaftar</span>
                        </div>
                    </div>
                </div>

                <!-- Kontrol Filter & Tambah Rombel -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
                    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <!-- Input Pencarian -->
                        <div class="relative w-full sm:w-64">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama kelas, jurusan, wali..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all pl-9"
                            />
                            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Filter Tingkat -->
                        <select
                            v-model="gradeLevelId"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="">Semua Tingkat</option>
                            <option v-for="level in gradeLevels" :key="level.id" :value="level.id">
                                {{ level.name }}
                            </option>
                        </select>

                        <!-- Filter Jurusan -->
                        <select
                            v-model="departmentId"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="">Semua Jurusan</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }} ({{ dept.code }})
                            </option>
                        </select>

                        <!-- Filter Tahun Ajaran -->
                        <select
                            v-model="academicYearId"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="">Semua Tahun Ajaran</option>
                            <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                                {{ yr.name }} {{ yr.is_active ? '(Aktif)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Tombol Tambah Rombel Baru -->
                    <button
                        @click="openCreateModal"
                        class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/20 transition-all shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Kelas Baru</span>
                    </button>
                </div>

                <!-- Tabel Data Rombel -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-300">
                            <thead class="bg-slate-950/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">Nama Rombel</th>
                                    <th class="px-6 py-4">Tingkat & Jurusan</th>
                                    <th class="px-6 py-4">Wali Kelas (Guru)</th>
                                    <th class="px-6 py-4">Kapasitas & Siswa</th>
                                    <th class="px-6 py-4">Tahun Ajaran</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="item in classrooms.data" :key="item.id" class="hover:bg-slate-800/30 transition-colors">
                                    <!-- Nama Rombel -->
                                    <td class="px-6 py-4 font-medium text-white whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold">
                                                {{ item.name.substring(0, 2) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-white text-base">{{ item.name }}</div>
                                                <div class="text-xs text-slate-400">ID Kelas: #{{ item.id }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Tingkat & Jurusan -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                                {{ item.grade_level_name }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                                {{ item.department_code }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-1">{{ item.department_name }}</div>
                                    </td>

                                    <!-- Wali Kelas -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="item.homeroom_teacher_id" class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-bold text-indigo-400">
                                                {{ item.homeroom_teacher_name.charAt(0) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-200">{{ item.homeroom_teacher_name }}</div>
                                                <div class="text-xs text-slate-500">NIP: {{ item.homeroom_teacher_nip }}</div>
                                            </div>
                                        </div>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            Belum Ditentukan
                                        </span>
                                    </td>

                                    <!-- Kapasitas & Siswa Terdaftar -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="space-y-1.5 w-36">
                                            <div class="flex justify-between text-xs">
                                                <span class="font-semibold text-white">{{ item.students_count }} / {{ item.capacity }}</span>
                                                <span :class="[
                                                    item.students_count >= item.capacity ? 'text-rose-400' : 
                                                    item.students_count / item.capacity > 0.8 ? 'text-amber-400' : 'text-emerald-400',
                                                    'font-semibold'
                                                ]">
                                                    {{ Math.round((item.students_count / item.capacity) * 100) }}%
                                                </span>
                                            </div>
                                            <!-- Mini Progress Bar -->
                                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                                <div
                                                    :class="[
                                                        item.students_count >= item.capacity ? 'bg-rose-500' : 
                                                        item.students_count / item.capacity > 0.8 ? 'bg-amber-500' : 'bg-emerald-500',
                                                        'h-full rounded-full transition-all duration-300'
                                                    ]"
                                                    :style="{ width: Math.min(100, Math.round((item.students_count / item.capacity) * 100)) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Tahun Ajaran -->
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">
                                        <span class="font-medium">{{ item.academic_year_name }}</span>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <button
                                                @click="openEditModal(item)"
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700 text-xs font-medium"
                                                title="Edit Data Rombel"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteClassroom(item)"
                                                class="px-2.5 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition-colors text-xs font-medium"
                                                title="Hapus Rombel"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="classrooms.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <p class="font-medium text-slate-400">Tidak ada rombongan belajar yang ditemukan</p>
                                        <p class="text-xs mt-1">Coba sesuaikan kata kunci pencarian atau filter tingkat/jurusan</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="classrooms.links && classrooms.links.length > 3" class="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                        <div>
                            Menampilkan <span class="font-semibold text-slate-200">{{ classrooms.from || 0 }}</span> - <span class="font-semibold text-slate-200">{{ classrooms.to || 0 }}</span> dari <span class="font-semibold text-slate-200">{{ classrooms.total }}</span> rombel
                        </div>
                        <div class="flex items-center gap-1">
                            <template v-for="(link, i) in classrooms.links" :key="i">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        link.active ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-800/80 text-slate-400 hover:bg-slate-800 hover:text-slate-200',
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

    <!-- ===== MODAL TAMBAH / EDIT KELAS ===== -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl animate-scale-up">
            <!-- Header Modal -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-base">
                            {{ isEditing ? 'Edit Rombongan Belajar' : 'Tambah Rombel (Kelas) Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400">Pastikan relasi tingkat, jurusan, dan wali kelas sudah sesuai</p>
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nama Kelas -->
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Nama Kelas / Rombel <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: X - RPL 1, XII - TKJ 2"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            required
                        />
                        <div v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Tahun Ajaran -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Tahun Ajaran <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="form.academic_year_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            required
                        >
                            <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                                {{ yr.name }} {{ yr.is_active ? '(Aktif)' : '' }}
                            </option>
                        </select>
                        <div v-if="form.errors.academic_year_id" class="text-xs text-rose-400 mt-1">{{ form.errors.academic_year_id }}</div>
                    </div>

                    <!-- Tingkat Kelas -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Tingkat Kelas <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="form.grade_level_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            required
                        >
                            <option value="" disabled>Pilih Tingkat</option>
                            <option v-for="level in gradeLevels" :key="level.id" :value="level.id">
                                {{ level.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.grade_level_id" class="text-xs text-rose-400 mt-1">{{ form.errors.grade_level_id }}</div>
                    </div>

                    <!-- Program Keahlian / Jurusan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Program Keahlian / Jurusan
                        </label>
                        <select
                            v-model="form.department_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Umum / Tanpa Jurusan</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }} ({{ dept.code }})
                            </option>
                        </select>
                        <div v-if="form.errors.department_id" class="text-xs text-rose-400 mt-1">{{ form.errors.department_id }}</div>
                    </div>

                    <!-- Kapasitas Maksimal Siswa -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Kapasitas Siswa <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model.number="form.capacity"
                            type="number"
                            min="1"
                            max="100"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            required
                        />
                        <div v-if="form.errors.capacity" class="text-xs text-rose-400 mt-1">{{ form.errors.capacity }}</div>
                    </div>

                    <!-- Wali Kelas -->
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Wali Kelas (Tenaga Pendidik)
                        </label>
                        <select
                            v-model="form.homeroom_teacher_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Belum Ditentukan (Kosongkan)</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                {{ teacher.name }} (NIP: {{ teacher.nip }})
                            </option>
                        </select>
                        <div v-if="form.errors.homeroom_teacher_id" class="text-xs text-rose-400 mt-1">{{ form.errors.homeroom_teacher_id }}</div>
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
                        class="px-5 py-2 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/20 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Kelas' : 'Simpan Kelas') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
