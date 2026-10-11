<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    violations: Object,
    radarStudents: Array,
    categories: Array,
    categoriesList: Array,
    classrooms: Array,
    students: Array,
    activeYear: Object,
    filters: Object,
    stats: Object,
});

const page = usePage();

// Navigasi Terpadu Administrator Sekolah
const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Tata Tertib & BK', route: 'counseling.violations.index', icon: 'clipboard-check' },
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

// Active Tab: 'log', 'radar', 'categories'
const activeTab = ref('log');

// Filter state
const filterForm = ref({
    search: props.filters.search || '',
    classroom_id: props.filters.classroom_id || '',
    severity: props.filters.severity || '',
    status: props.filters.status || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const applyFilters = () => {
    router.get(route('counseling.violations.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.value = {
        search: '',
        classroom_id: '',
        severity: '',
        status: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};

// Modal 1: Catat Pelanggaran Siswa
const showViolationModal = ref(false);
const isEditingViolation = ref(false);
const editingViolationId = ref(null);

const todayDate = new Date().toISOString().split('T')[0];

const violationForm = useForm({
    student_id: '',
    violation_category_id: '',
    date: todayDate,
    time: '',
    notes: '',
    sanction: '',
    follow_up: '',
    status: 'tercatat',
    points: null,
});

const openCreateViolation = () => {
    isEditingViolation.value = false;
    editingViolationId.value = null;
    violationForm.reset();
    violationForm.clearErrors();
    violationForm.date = todayDate;
    violationForm.status = 'tercatat';
    showViolationModal.value = true;
};

const openEditViolation = (item) => {
    isEditingViolation.value = true;
    editingViolationId.value = item.id;
    violationForm.clearErrors();
    violationForm.student_id = item.student_id;
    violationForm.violation_category_id = item.category_id;
    violationForm.date = item.date;
    violationForm.time = item.time === '-' ? '' : item.time;
    violationForm.notes = item.notes;
    violationForm.sanction = item.sanction === '-' ? '' : item.sanction;
    violationForm.follow_up = item.follow_up === '-' ? '' : item.follow_up;
    violationForm.status = item.status;
    violationForm.points = item.points;
    showViolationModal.value = true;
};

const submitViolation = () => {
    if (isEditingViolation.value) {
        violationForm.put(route('counseling.violations.update', editingViolationId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showViolationModal.value = false;
            },
        });
    } else {
        violationForm.post(route('counseling.violations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showViolationModal.value = false;
            },
        });
    }
};

const deleteViolation = (item) => {
    if (confirm(`Hapus catatan pelanggaran siswa: ${item.student_name} (${item.category_name})?`)) {
        router.delete(route('counseling.violations.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

const resolveViolation = (item) => {
    if (confirm(`Tandai kasus pembinaan untuk ${item.student_name} telah selesai?`)) {
        router.post(route('counseling.violations.resolve', item.id), {}, {
            preserveScroll: true,
        });
    }
};

const notifyParent = (item) => {
    if (confirm(`Kirimkan surat panggilan / notifikasi orang tua untuk siswa ${item.student_name}?`)) {
        router.post(route('counseling.violations.notify-parent', item.id), {}, {
            preserveScroll: true,
        });
    }
};

// Modal 2: Master Aturan Tata Tertib
const showCategoryModal = ref(false);
const isEditingCategory = ref(false);
const editingCategoryId = ref(null);

const categoryForm = useForm({
    code: '',
    name: '',
    type: 'pelanggaran',
    severity: 'ringan',
    points: 5,
    action_recommendation: '',
    is_active: true,
});

const openCreateCategory = () => {
    isEditingCategory.value = false;
    editingCategoryId.value = null;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.type = 'pelanggaran';
    categoryForm.severity = 'ringan';
    categoryForm.points = 5;
    categoryForm.is_active = true;
    showCategoryModal.value = true;
};

const openEditCategory = (item) => {
    isEditingCategory.value = true;
    editingCategoryId.value = item.id;
    categoryForm.clearErrors();
    categoryForm.code = item.code;
    categoryForm.name = item.name;
    categoryForm.type = item.type;
    categoryForm.severity = item.severity;
    categoryForm.points = item.points;
    categoryForm.action_recommendation = item.action_recommendation === '-' ? '' : item.action_recommendation;
    categoryForm.is_active = item.is_active;
    showCategoryModal.value = true;
};

const submitCategory = () => {
    if (isEditingCategory.value) {
        categoryForm.put(route('counseling.categories.update', editingCategoryId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showCategoryModal.value = false;
            },
        });
    } else {
        categoryForm.post(route('counseling.categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showCategoryModal.value = false;
            },
        });
    }
};

const deleteCategory = (item) => {
    if (confirm(`Hapus aturan tata tertib: "${item.name} (${item.code})"?`)) {
        router.delete(route('counseling.categories.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

// Modal 3: Surat Panggilan Orang Tua (Print)
const showLetterModal = ref(false);
const selectedStudentLetter = ref(null);

const openParentLetter = (student) => {
    selectedStudentLetter.value = student;
    showLetterModal.value = true;
};

const printLetter = () => {
    window.print();
};

const getSeverityBadge = (severity) => {
    switch (severity) {
        case 'ringan':
            return { label: 'Ringan', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' };
        case 'sedang':
            return { label: 'Sedang', class: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' };
        case 'berat':
            return { label: 'Berat', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20' };
        case 'sangat_berat':
            return { label: 'Sangat Berat', class: 'bg-rose-500/10 text-rose-400 border-rose-500/20' };
        case 'prestasi':
            return { label: 'Prestasi', class: 'bg-sky-500/10 text-sky-400 border-sky-500/20' };
        default:
            return { label: severity, class: 'bg-slate-700 text-slate-300 border-slate-600' };
    }
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'tercatat':
            return { label: 'Tercatat', class: 'bg-slate-800 text-slate-300 border-slate-700' };
        case 'dalam_pembinaan':
            return { label: 'Dalam Pembinaan BK', class: 'bg-blue-500/10 text-blue-400 border-blue-500/20' };
        case 'panggilan_orang_tua':
            return { label: 'Panggilan Orang Tua', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20' };
        case 'selesai':
            return { label: 'Selesai / Tuntas', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' };
        default:
            return { label: status, class: 'bg-slate-700 text-slate-300 border-slate-600' };
    }
};
</script>

<template>
    <Head title="Kedisiplinan Siswa & Konseling BK - Portal Sekolah" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <!-- Sidebar Terpadu -->
        <Sidebar :menus="adminMenus" sectionTitle="Operasional Sekolah" />

        <!-- Konten Utama -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
            <!-- Header Atas (Hidden saat Print) -->
            <header class="no-print h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Kedisiplinan, Tata Tertib & Konseling BK</h1>
                        <p class="text-xs text-slate-400">Pencatatan pelanggaran, radar akumulasi poin, bimbingan konseling & pemanggilan orang tua</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="activeTab === 'categories'"
                        @click="openCreateCategory"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/20 flex items-center gap-2 transition"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Aturan Tata Tertib</span>
                    </button>

                    <button
                        v-else
                        @click="openCreateViolation"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-lg shadow-rose-600/20 flex items-center gap-2 transition"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Catat Pelanggaran Siswa</span>
                    </button>
                </div>
            </header>

            <!-- Status Flash Notification -->
            <div v-if="$page.props.flash?.status" class="no-print mx-6 mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ $page.props.flash.status }}</span>
                </div>
            </div>

            <!-- Error Flash Notification -->
            <div v-if="$page.props.errors?.error" class="no-print mx-6 mt-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ $page.props.errors.error }}</span>
                </div>
            </div>

            <main class="p-6 space-y-6">
                <!-- Statistik Card Ringkasan Kedisiplinan -->
                <div class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-2.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-400">Total Kasus Tercatat</p>
                            <h3 class="text-lg font-bold text-white">{{ stats.total_cases }} Kasus</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-2.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-400">Siswa SP 1 / SP 2 / SP 3</p>
                            <h3 class="text-lg font-bold text-rose-400">{{ stats.sp_students_count }} Siswa</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-2.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-400">Panggilan Orang Tua</p>
                            <h3 class="text-lg font-bold text-amber-400">{{ stats.parent_calls }} Siswa</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-2.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-400">Dalam Pembinaan BK</p>
                            <h3 class="text-lg font-bold text-blue-400">{{ stats.active_cases }} Kasus</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-2.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-400">Kasus Selesai / Tuntas</p>
                            <h3 class="text-lg font-bold text-emerald-400">{{ stats.resolved_cases }} Kasus</h3>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigasi Halaman -->
                <div class="no-print flex items-center gap-2 border-b border-slate-800 pb-2">
                    <button
                        @click="activeTab = 'log'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2',
                            activeTab === 'log'
                                ? 'bg-rose-600/20 text-rose-300 border border-rose-500/30'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Log Pelanggaran Harian ({{ stats.total_cases }})</span>
                    </button>

                    <button
                        @click="activeTab = 'radar'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2',
                            activeTab === 'radar'
                                ? 'bg-amber-600/20 text-amber-300 border border-amber-500/30'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Radar Skor & Leaderboard Siswa</span>
                    </button>

                    <button
                        @click="activeTab = 'categories'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2',
                            activeTab === 'categories'
                                ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Katalog Master Tata Tertib ({{ categories.length }})</span>
                    </button>
                </div>

                <!-- ======================================================== -->
                <!-- TAB 1: LOG PELANGGARAN HARIAN -->
                <!-- ======================================================== -->
                <div v-if="activeTab === 'log'" class="space-y-4">
                    <!-- Filter Bar -->
                    <div class="no-print p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Cari Siswa / Kasus</label>
                                <input
                                    type="text"
                                    v-model="filterForm.search"
                                    placeholder="Nama siswa, NISN, aturan..."
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Kelas</label>
                                <select
                                    v-model="filterForm.classroom_id"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="">Semua Kelas</option>
                                    <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Tingkat Keparahan</label>
                                <select
                                    v-model="filterForm.severity"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="">Semua Tingkat</option>
                                    <option value="ringan">Ringan (5 - 10 poin)</option>
                                    <option value="sedang">Sedang (15 - 25 poin)</option>
                                    <option value="berat">Berat (30 - 50 poin)</option>
                                    <option value="sangat_berat">Sangat Berat (75+ poin)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Status Penanganan</label>
                                <select
                                    v-model="filterForm.status"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="tercatat">Tercatat</option>
                                    <option value="dalam_pembinaan">Dalam Pembinaan BK</option>
                                    <option value="panggilan_orang_tua">Panggilan Orang Tua</option>
                                    <option value="selesai">Selesai / Tuntas</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Dari Tanggal</label>
                                <input
                                    type="date"
                                    v-model="filterForm.date_from"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Sampai Tanggal</label>
                                <input
                                    type="date"
                                    v-model="filterForm.date_to"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button
                                @click="resetFilters"
                                class="px-3 py-1 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-400 hover:text-slate-200 text-xs font-medium transition"
                            >
                                Reset
                            </button>
                            <button
                                @click="applyFilters"
                                class="px-4 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition"
                            >
                                Terapkan Filter
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Log Pelanggaran -->
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Tgl & Jam</th>
                                        <th class="px-4 py-3 font-semibold">Siswa & Kelas</th>
                                        <th class="px-4 py-3 font-semibold">Pelanggaran & Bobot Poin</th>
                                        <th class="px-4 py-3 font-semibold">Kronologi & Sanksi</th>
                                        <th class="px-4 py-3 font-semibold text-center">Status</th>
                                        <th class="px-4 py-3 font-semibold text-center">Ortu</th>
                                        <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <tr
                                        v-for="v in violations.data"
                                        :key="v.id"
                                        class="hover:bg-slate-800/40 transition"
                                    >
                                        <!-- Tanggal & Jam -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="font-semibold text-white">{{ v.date_formatted }}</div>
                                            <div class="text-[11px] text-slate-400">🕒 {{ v.time }}</div>
                                        </td>

                                        <!-- Siswa & Kelas -->
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-white">{{ v.student_name }}</div>
                                            <div class="text-[11px] text-slate-400">NISN: {{ v.nisn }} • Kelas: {{ v.classroom_name }}</div>
                                        </td>

                                        <!-- Pelanggaran & Poin -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold border', getSeverityBadge(v.severity).class]">
                                                    {{ getSeverityBadge(v.severity).label }}
                                                </span>
                                                <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                    +{{ v.points }} Poin
                                                </span>
                                            </div>
                                            <div class="font-medium text-slate-200 mt-1">{{ v.category_name }}</div>
                                        </td>

                                        <!-- Kronologi & Sanksi -->
                                        <td class="px-4 py-3 max-w-xs sm:max-w-md">
                                            <p class="text-[11px] text-slate-300 line-clamp-2">{{ v.notes }}</p>
                                            <p v-if="v.sanction && v.sanction !== '-'" class="text-[10px] text-amber-400 mt-0.5">
                                                Sanksi: {{ v.sanction }}
                                            </p>
                                            <p v-if="v.follow_up && v.follow_up !== '-'" class="text-[10px] text-blue-400 mt-0.5">
                                                Tindak Lanjut: {{ v.follow_up }}
                                            </p>
                                        </td>

                                        <!-- Status Penanganan -->
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border', getStatusBadge(v.status).class]">
                                                {{ getStatusBadge(v.status).label }}
                                            </span>
                                        </td>

                                        <!-- Status Notifikasi Ortu -->
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span
                                                v-if="v.parent_notified"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                                                :title="`Dipanggil: ${v.parent_notified_at || '-'}`"
                                            >
                                                Dipanggil
                                            </span>
                                            <button
                                                v-else
                                                @click="notifyParent(v)"
                                                class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 hover:bg-amber-500/20 text-slate-400 hover:text-amber-300 border border-slate-700 transition"
                                                title="Panggil orang tua siswa"
                                            >
                                                Panggil
                                            </button>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button
                                                    v-if="v.status !== 'selesai'"
                                                    @click="resolveViolation(v)"
                                                    class="p-1 rounded text-emerald-400 hover:text-emerald-300 hover:bg-emerald-400/10 transition"
                                                    title="Selesaikan Kasus Pembinaan"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>

                                                <button
                                                    @click="openEditViolation(v)"
                                                    class="p-1 rounded text-amber-400 hover:text-amber-300 hover:bg-amber-400/10 transition"
                                                    title="Edit Catatan"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>

                                                <button
                                                    @click="deleteViolation(v)"
                                                    class="p-1 rounded text-rose-400 hover:text-rose-300 hover:bg-rose-400/10 transition"
                                                    title="Hapus Catatan"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="!violations.data || violations.data.length === 0">
                                        <td colspan="7" class="text-center py-10 text-slate-500">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <svg class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p class="text-sm">Tidak ada catatan pelanggaran yang sesuai filter.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="violations.links && violations.links.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                            <div>
                                Menampilkan {{ violations.from || 0 }} - {{ violations.to || 0 }} dari {{ violations.total || 0 }} catatan
                            </div>
                            <div class="flex items-center gap-1">
                                <Link
                                    v-for="(link, i) in violations.links"
                                    :key="i"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-2.5 py-1 rounded text-xs transition',
                                        link.active ? 'bg-rose-600 text-white font-bold' : 'hover:bg-slate-800 text-slate-300',
                                        !link.url ? 'opacity-40 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- TAB 2: RADAR DISIPLIN & SKOR SISWA -->
                <!-- ======================================================== -->
                <div v-if="activeTab === 'radar'" class="space-y-4">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <h3 class="text-sm font-bold text-white mb-1">Ambang Batas Peringatan & Sanksi Kedisiplinan</h3>
                        <p class="text-xs text-slate-400 mb-3">Sistem akumulasi skor otomatis berdasarkan bobot pelanggaran tata tertib tahun ajaran berjalan:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                            <div class="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-300">
                                <span class="font-bold block">0 - 20 Poin</span>
                                <span class="text-[11px] text-slate-400">Status Aman • Teguran lisan & bimbingan wali kelas</span>
                            </div>
                            <div class="p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20 text-yellow-300">
                                <span class="font-bold block">21 - 40 Poin</span>
                                <span class="text-[11px] text-slate-400">Surat Peringatan 1 (SP 1) • Konseling Guru BK</span>
                            </div>
                            <div class="p-3 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-300">
                                <span class="font-bold block">41 - 74 Poin</span>
                                <span class="text-[11px] text-slate-400">Surat Peringatan 2 (SP 2) • Pemanggilan Orang Tua</span>
                            </div>
                            <div class="p-3 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-300">
                                <span class="font-bold block">75+ Poin</span>
                                <span class="text-[11px] text-slate-400">Surat Peringatan 3 (SP 3) • Sidang Kasus & Skorsing</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Leaderboard Radar Siswa -->
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Nama Siswa</th>
                                        <th class="px-4 py-3 font-semibold">Kelas</th>
                                        <th class="px-4 py-3 font-semibold text-center">Total Kasus</th>
                                        <th class="px-4 py-3 font-semibold text-center">Akumulasi Skor</th>
                                        <th class="px-4 py-3 font-semibold text-center">Level Peringatan</th>
                                        <th class="px-4 py-3 font-semibold text-right">Surat Panggilan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <tr
                                        v-for="s in radarStudents"
                                        :key="s.id"
                                        class="hover:bg-slate-800/40 transition"
                                    >
                                        <td class="px-4 py-3 font-bold text-white">
                                            <div>{{ s.name }}</div>
                                            <div class="text-[10px] text-slate-400">NISN: {{ s.nisn }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-300">{{ s.classroom_name }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-800 border border-slate-700">
                                                {{ s.total_violations }} Kasus
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                {{ s.total_points }} Poin
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold border',
                                                    s.warning_level === 'sp3' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' :
                                                    s.warning_level === 'sp2' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' :
                                                    s.warning_level === 'sp1' ? 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30' :
                                                    'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                                                ]"
                                            >
                                                {{ s.warning_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                @click="openParentLetter(s)"
                                                class="px-3 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-medium transition flex items-center gap-1.5 ml-auto"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                <span>Cetak Surat Panggilan</span>
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="!radarStudents || radarStudents.length === 0">
                                        <td colspan="6" class="text-center py-10 text-slate-500">
                                            <p class="text-sm">Tidak ada data pelanggaran siswa yang tercatat.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- TAB 3: KATALOG MASTER TATA TERTIB -->
                <!-- ======================================================== -->
                <div v-if="activeTab === 'categories'" class="space-y-4">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Kode</th>
                                        <th class="px-4 py-3 font-semibold">Nama Aturan Pelanggaran</th>
                                        <th class="px-4 py-3 font-semibold text-center">Jenis & Tingkat</th>
                                        <th class="px-4 py-3 font-semibold text-center">Bobot Skor</th>
                                        <th class="px-4 py-3 font-semibold">Rekomendasi Tindakan / Sanksi</th>
                                        <th class="px-4 py-3 font-semibold text-center">Total Kasus</th>
                                        <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <tr
                                        v-for="c in categories"
                                        :key="c.id"
                                        class="hover:bg-slate-800/40 transition"
                                    >
                                        <td class="px-4 py-3 whitespace-nowrap font-bold text-white">
                                            <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-indigo-300">
                                                {{ c.code }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-200">
                                            {{ c.name }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border', getSeverityBadge(c.severity).class]">
                                                {{ getSeverityBadge(c.severity).label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                {{ c.points }} Poin
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-400 max-w-xs">
                                            {{ c.action_recommendation }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded text-xs bg-slate-800 border border-slate-700">
                                                {{ c.cases_count }} Kasus
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button
                                                    @click="openEditCategory(c)"
                                                    class="p-1 rounded text-amber-400 hover:text-amber-300 hover:bg-amber-400/10 transition"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    @click="deleteCategory(c)"
                                                    class="p-1 rounded text-rose-400 hover:text-rose-300 hover:bg-rose-400/10 transition"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="!categories || categories.length === 0">
                                        <td colspan="7" class="text-center py-10 text-slate-500">
                                            <p class="text-sm">Belum ada aturan tata tertib.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL CATAT PELANGGARAN SISWA -->
    <!-- ======================================================== -->
    <div
        v-if="showViolationModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
    >
        <div class="relative w-full max-w-xl rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 text-slate-100 my-8">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">
                            {{ isEditingViolation ? 'Edit Catatan Pelanggaran Siswa' : 'Catat Pelanggaran Kedisiplinan' }}
                        </h3>
                        <p class="text-xs text-slate-400">Masukkan kronologi insiden, pelanggaran aturan & sanksi pembinaan</p>
                    </div>
                </div>
                <button
                    @click="showViolationModal = false"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitViolation" class="space-y-4 mt-4">
                <div v-if="!isEditingViolation">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Pilih Siswa Terlapor *</label>
                    <select
                        v-model="violationForm.student_id"
                        required
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-rose-500"
                    >
                        <option value="" disabled>Pilih Siswa</option>
                        <option v-for="s in students" :key="s.id" :value="s.id">
                            {{ s.name }} (NISN: {{ s.nisn }}) - Kelas {{ s.classroom_name }}
                        </option>
                    </select>
                    <div v-if="violationForm.errors.student_id" class="text-rose-400 text-[11px] mt-1">{{ violationForm.errors.student_id }}</div>
                </div>

                <div v-if="!isEditingViolation">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Jenis Pelanggaran / Aturan Tata Tertib *</label>
                    <select
                        v-model="violationForm.violation_category_id"
                        required
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-rose-500"
                    >
                        <option value="" disabled>Pilih Pelanggaran</option>
                        <option v-for="cat in categoriesList" :key="cat.id" :value="cat.id">
                            [{{ cat.code }}] {{ cat.name }} (+{{ cat.points }} Poin • {{ cat.severity }})
                        </option>
                    </select>
                    <div v-if="violationForm.errors.violation_category_id" class="text-rose-400 text-[11px] mt-1">{{ violationForm.errors.violation_category_id }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tanggal Kejadian *</label>
                        <input
                            type="date"
                            v-model="violationForm.date"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-rose-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Waktu Kejadian (Opsional)</label>
                        <input
                            type="time"
                            v-model="violationForm.time"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-rose-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Kronologi Kejadian / Uraian Pelanggaran *</label>
                    <textarea
                        v-model="violationForm.notes"
                        required
                        rows="3"
                        placeholder="Deskripsikan fakta insiden, saksi, barang bukti jika ada..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-rose-500"
                    ></textarea>
                    <div v-if="violationForm.errors.notes" class="text-rose-400 text-[11px] mt-1">{{ violationForm.errors.notes }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Sanksi yang Dikenakan</label>
                        <input
                            type="text"
                            v-model="violationForm.sanction"
                            placeholder="Contoh: Membersihkan perpustakaan, skorsing"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-rose-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Status Penanganan *</label>
                        <select
                            v-model="violationForm.status"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-rose-500"
                        >
                            <option value="tercatat">Tercatat</option>
                            <option value="dalam_pembinaan">Dalam Pembinaan BK</option>
                            <option value="panggilan_orang_tua">Panggilan Orang Tua</option>
                            <option value="selesai">Selesai / Tuntas</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Tindak Lanjut Pembinaan BK / Wali Kelas</label>
                    <textarea
                        v-model="violationForm.follow_up"
                        rows="2"
                        placeholder="Rencana konseling, evaluasi perkembangan karakter..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-rose-500"
                    ></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button
                        type="button"
                        @click="showViolationModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="violationForm.processing"
                        class="px-5 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-lg shadow-rose-600/20 transition disabled:opacity-50"
                    >
                        {{ violationForm.processing ? 'Menyimpan...' : (isEditingViolation ? 'Perbarui' : 'Simpan Pelanggaran') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL TAMBAH / EDIT ATURAN TATA TERTIB -->
    <!-- ======================================================== -->
    <div
        v-if="showCategoryModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
    >
        <div class="relative w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 text-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">
                            {{ isEditingCategory ? 'Edit Aturan Tata Tertib' : 'Tambah Aturan Tata Tertib Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400">Tentukan kode, tingkat keparahan dan bobot skor poin</p>
                    </div>
                </div>
                <button
                    @click="showCategoryModal = false"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitCategory" class="space-y-4 mt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Kode Aturan *</label>
                        <input
                            type="text"
                            v-model="categoryForm.code"
                            required
                            placeholder="Contoh: TATIB-01"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 uppercase"
                        />
                        <div v-if="categoryForm.errors.code" class="text-rose-400 text-[11px] mt-1">{{ categoryForm.errors.code }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Bobot Poin *</label>
                        <input
                            type="number"
                            min="1"
                            v-model="categoryForm.points"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        />
                        <div v-if="categoryForm.errors.points" class="text-rose-400 text-[11px] mt-1">{{ categoryForm.errors.points }}</div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Nama Aturan Pelanggaran *</label>
                    <input
                        type="text"
                        v-model="categoryForm.name"
                        required
                        placeholder="Contoh: Terlambat Masuk Sekolah Lebih dari 15 Menit"
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    />
                    <div v-if="categoryForm.errors.name" class="text-rose-400 text-[11px] mt-1">{{ categoryForm.errors.name }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Jenis *</label>
                        <select
                            v-model="categoryForm.type"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="pelanggaran">Pelanggaran Kedisiplinan</option>
                            <option value="prestasi">Prestasi / Penghargaan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tingkat Keparahan *</label>
                        <select
                            v-model="categoryForm.severity"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="ringan">Ringan</option>
                            <option value="sedang">Sedang</option>
                            <option value="berat">Berat</option>
                            <option value="sangat_berat">Sangat Berat</option>
                            <option value="prestasi">Prestasi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Rekomendasi Tindakan / Sanksi Standar</label>
                    <textarea
                        v-model="categoryForm.action_recommendation"
                        rows="2"
                        placeholder="Contoh: Teguran lisan, peringatan tertulis, pemanggilan wali..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    ></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button
                        type="button"
                        @click="showCategoryModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="categoryForm.processing"
                        class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/20 transition disabled:opacity-50"
                    >
                        {{ categoryForm.processing ? 'Menyimpan...' : (isEditingCategory ? 'Perbarui' : 'Simpan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL SURAT PANGGILAN ORANG TUA (PRINT READY) -->
    <!-- ======================================================== -->
    <div
        v-if="showLetterModal && selectedStudentLetter"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
    >
        <div class="relative w-full max-w-2xl rounded-2xl bg-white text-black p-8 my-8 shadow-2xl">
            <!-- Action Bar (Hidden saat print) -->
            <div class="no-print flex items-center justify-between pb-4 mb-4 border-b border-gray-300">
                <span class="text-xs font-bold text-gray-600 uppercase">Pratinjau Surat Panggilan Orang Tua</span>
                <div class="flex items-center gap-2">
                    <button
                        @click="printLetter"
                        class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center gap-1.5 transition"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Surat (Print / PDF)</span>
                    </button>
                    <button
                        @click="showLetterModal = false"
                        class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 text-xs font-medium transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>

            <!-- Surat Fisik Resmi -->
            <div class="space-y-4 font-serif text-sm">
                <!-- Kop Surat -->
                <div class="text-center border-b-2 border-black pb-3">
                    <h2 class="text-base font-bold uppercase tracking-wider">PEMERINTAH PROVINSI / YAYASAN PENDIDIKAN</h2>
                    <h1 class="text-xl font-black uppercase">SMK / SMA NEGERI 1 CONTOH</h1>
                    <p class="text-xs text-gray-700">Jl. Pendidikan No. 123, Telp: (021) 555-1234, Email: info@sekolah.sch.id</p>
                </div>

                <div class="flex justify-between text-xs pt-2">
                    <div>
                        <p>Nomor : 421.3 / BK / SP / {{ new Date().getFullYear() }}</p>
                        <p>Lampiran : -</p>
                        <p>Hal : <strong>Panggilan Orang Tua / Wali Siswa</strong></p>
                    </div>
                    <div class="text-right">
                        <p>{{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                    </div>
                </div>

                <div class="pt-2 text-xs">
                    <p>Kepada Yth.</p>
                    <p><strong>Bapak / Ibu Orang Tua / Wali dari {{ selectedStudentLetter.name }}</strong></p>
                    <p>di Tempat</p>
                </div>

                <div class="space-y-2 text-xs text-justify leading-relaxed">
                    <p>Dengan hormat,</p>
                    <p>
                        Sehubungan dengan pembinaan kedisiplinan dan tata tertib siswa di sekolah, kami mengharapkan kehadiran Bapak/Ibu Orang Tua/Wali Murid pada:
                    </p>

                    <div class="pl-6 space-y-1">
                        <p><span class="font-bold w-32 inline-block">Nama Siswa:</span> {{ selectedStudentLetter.name }}</p>
                        <p><span class="font-bold w-32 inline-block">NISN:</span> {{ selectedStudentLetter.nisn }}</p>
                        <p><span class="font-bold w-32 inline-block">Kelas / Rombel:</span> {{ selectedStudentLetter.classroom_name }}</p>
                        <p><span class="font-bold w-32 inline-block">Akumulasi Skor:</span> <strong class="text-red-600">{{ selectedStudentLetter.total_points }} Poin ({{ selectedStudentLetter.warning_label }})</strong></p>
                    </div>

                    <p>
                        Untuk hadir di ruang Bimbingan & Konseling (BK) guna membahas perkembangan perilaku dan kedisiplinan putra/putri Bapak/Ibu demi kelancaran proses pembelajaran siswa di sekolah.
                    </p>
                    <p>
                        Demikian surat panggilan ini kami sampaikan. Atas perhatian, kehadiran, dan kerjasama yang baik dari Bapak/Ibu, kami ucapkan terima kasih.
                    </p>
                </div>

                <!-- Tanda Tangan -->
                <div class="pt-8 grid grid-cols-2 text-center text-xs">
                    <div>
                        <p>Mengetahui,</p>
                        <p class="font-bold">Kepala Sekolah</p>
                        <div class="h-16"></div>
                        <p class="font-bold underline">( .................................................. )</p>
                        <p class="text-[10px] text-gray-600">NIP. ..........................................</p>
                    </div>
                    <div>
                        <p>Guru Pembina,</p>
                        <p class="font-bold">Koordinator BK / Kesiswaan</p>
                        <div class="h-16"></div>
                        <p class="font-bold underline">( .................................................. )</p>
                        <p class="text-[10px] text-gray-600">NIP. ..........................................</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
