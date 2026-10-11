<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    classrooms: {
        type: Array,
        default: () => [],
    },
    selectedClassroomId: [Number, String],
    selectedClassroom: Object,
    selectedDate: String,
    dayOfWeek: String,
    schedules: {
        type: Array,
        default: () => [],
    },
    selectedScheduleId: [Number, String],
    students: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    canEdit: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();

// Navigasi Admin
const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Tagihan & SPP Massal', route: 'finance.bills.index', icon: 'credit-card' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'POS Kasir SPP', route: 'finance.pos', icon: 'credit-card' },
    { label: 'Cetak Massal KTS', route: 'kts.batch', icon: 'id-card' },
    { label: 'Presensi GPS & AI', route: 'attendance.gps.index', icon: 'location' },
    { label: 'Verifikasi E-Leave', route: 'attendance.leave.index', icon: 'clipboard-check' },
    { label: 'Dompet Digital Siswa', route: 'wallet.index', icon: 'wallet' },
    { label: 'E-Kantin & Stand', route: 'canteen.index', icon: 'shopping-bag' },
    { isHeader: true, label: 'Master Data Akademik' },
    { label: 'Data Siswa & Rombel', route: 'academic.students.index', icon: 'users' },
    { label: 'Data Guru & PTK', route: 'academic.teachers.index', icon: 'academic-cap' },
    { label: 'Rombel (Kelas)', route: 'academic.classrooms.index', icon: 'rectangle-stack' },
    { label: 'Mata Pelajaran', route: 'academic.subjects.index', icon: 'book-open' },
];

// Reactive states
const selectedClass = ref(props.selectedClassroomId || (props.classrooms[0]?.id ?? ''));
const dateModel = ref(props.selectedDate || new Date().toISOString().split('T')[0]);
const selectedSchedule = ref(props.selectedScheduleId || '');
const searchQuery = ref('');
const statusFilter = ref('all');
const isSaving = ref(false);
const saveSuccessMessage = ref('');

// Local state for interactive instant student attendance
const studentsList = ref(JSON.parse(JSON.stringify(props.students || [])));

// Watch props updates to keep local state in sync
watch(
    () => props.students,
    (newVal) => {
        studentsList.value = JSON.parse(JSON.stringify(newVal || []));
    },
    { deep: true }
);

// Switch filters (Class, Date, Schedule)
const onFilterChange = () => {
    router.get(
        route('attendance.classroom.index'),
        {
            classroom_id: selectedClass.value,
            date: dateModel.value,
            schedule_id: selectedSchedule.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

// Power Actions
const markAllPresent = () => {
    studentsList.value.forEach((s) => {
        s.status = 'hadir';
    });
};

const setAllStatus = (newStatus) => {
    studentsList.value.forEach((s) => {
        s.status = newStatus;
    });
};

const setStudentStatus = (student, newStatus) => {
    student.status = newStatus;
};

// Computed Live Stats
const liveStats = computed(() => {
    const list = studentsList.value;
    const total = list.length;
    const hadir = list.filter((s) => s.status === 'hadir').length;
    const terlambat = list.filter((s) => s.status === 'terlambat').length;
    const sakit = list.filter((s) => s.status === 'sakit').length;
    const izin = list.filter((s) => s.status === 'izin').length;
    const alpa = list.filter((s) => s.status === 'alpa').length;

    const rate = total > 0 ? (((hadir + terlambat) / total) * 100).toFixed(1) + '%' : '0%';

    return { total, hadir, terlambat, sakit, izin, alpa, rate };
});

// Filtered students for search & tab filter
const filteredStudents = computed(() => {
    return studentsList.value.filter((s) => {
        const matchesSearch =
            !searchQuery.value ||
            s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            s.nis.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesStatus = statusFilter.value === 'all' || s.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

// Save Batch Attendance
const saveAttendance = () => {
    if (isSaving.value) return;
    isSaving.value = true;
    saveSuccessMessage.value = '';

    const payload = {
        classroom_id: selectedClass.value,
        lesson_schedule_id: selectedSchedule.value || null,
        date: dateModel.value,
        attendances: studentsList.value.map((s) => ({
            student_user_id: s.user_id,
            status: s.status,
            notes: s.notes || null,
        })),
    };

    router.post(route('attendance.classroom.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            saveSuccessMessage.value = 'Presensi kelas berhasil disimpan dan disinkronkan ke sistem harian!';
            setTimeout(() => {
                saveSuccessMessage.value = '';
            }, 4000);
        },
        onError: () => {
            isSaving.value = false;
        },
    });
};

// Print Journal
const printJournal = () => {
    window.print();
};
</script>

<template>
    <Head title="Presensi Kelas & Absensi Siswa - SIAKAD" />

    <AuthenticatedLayout :menus="adminMenus" section-title="Presensi Kelas">
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto print:p-0 print:m-0 print:max-w-none">
            
            <!-- 1. Executive Top Hero Header -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 shadow-2xl p-6 sm:p-8 text-white print:hidden">
                <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <!-- Pill status -->
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                                Presensi Kelas Terpadu
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-medium bg-slate-800/80 text-slate-300 border border-slate-700/60">
                                Hari {{ dayOfWeek }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                Realtime Sync
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                            Presensi Siswa per Kelas & Mapel
                            <span v-if="selectedClassroom" class="text-xs px-2.5 py-1 rounded-lg bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 font-normal">
                                {{ selectedClassroom.name }}
                            </span>
                        </h1>
                        <p class="text-sm text-slate-300 mt-2 max-w-3xl leading-relaxed">
                            Pencatatan kehadiran siswa per jam pelajaran atau harian. Cukup 1-klik untuk menandai semua hadir, tombol H/S/I/A reaktif, dan otomatis tersinkronkan ke rekap harian sekolah.
                        </p>
                    </div>

                    <!-- Header Quick Actions -->
                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <button
                            @click="printJournal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 hover:text-white font-semibold text-xs border border-slate-700 transition"
                            title="Cetak format cetak jurnal presensi"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Jurnal
                        </button>

                        <button
                            @click="saveAttendance"
                            :disabled="isSaving"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 disabled:opacity-50 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/25 transition transform active:scale-95"
                        >
                            <svg v-if="!isSaving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-4 h-4 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Presensi Kelas' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Print-Only Header -->
            <div class="hidden print:block mb-6 border-b-2 border-black pb-4 text-center">
                <h1 class="text-xl font-bold uppercase tracking-wider">JURNAL & PRESENSI KELAS SISWA</h1>
                <p class="text-sm font-medium mt-1">
                    Kelas: <strong>{{ selectedClassroom?.name || '-' }}</strong> • Tanggal: <strong>{{ selectedDate }} ({{ dayOfWeek }})</strong>
                </p>
                <p v-if="selectedSchedule" class="text-xs text-gray-700">
                    Mata Pelajaran: <strong>{{ schedules.find(s => s.id === selectedSchedule)?.subject_name || '-' }}</strong>
                    • Guru: <strong>{{ schedules.find(s => s.id === selectedSchedule)?.teacher_name || '-' }}</strong>
                </p>
            </div>

            <!-- Success Alert Banner -->
            <div
                v-if="saveSuccessMessage || page.props.flash?.status"
                class="rounded-2xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-emerald-400 flex items-center justify-between shadow-lg print:hidden"
            >
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-emerald-300">Berhasil Disimpan</p>
                        <p class="text-xs text-emerald-400/90">{{ saveSuccessMessage || page.props.flash?.status }}</p>
                    </div>
                </div>
            </div>

            <!-- 2. Controls & Selectors Card (Class, Date, Lesson Schedule) -->
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-sm print:hidden">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Selector 1: Rombel Kelas -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                            Pilih Rombongan Belajar (Kelas)
                        </label>
                        <div class="relative">
                            <select
                                v-model="selectedClass"
                                @change="onFilterChange"
                                class="w-full rounded-2xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-sm font-semibold py-2.5 pl-3.5 pr-8 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                    {{ c.name }} ({{ c.department }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Selector 2: Tanggal Presensi -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                            Tanggal Presensi
                        </label>
                        <input
                            type="date"
                            v-model="dateModel"
                            @change="onFilterChange"
                            class="w-full rounded-2xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-sm font-semibold py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <!-- Selector 3: Jam Pelajaran / Mapel -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                            Sesi Mapel / Jam Pelajaran
                        </label>
                        <select
                            v-model="selectedSchedule"
                            @change="onFilterChange"
                            class="w-full rounded-2xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-sm font-semibold py-2.5 pl-3.5 pr-8 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Presensi Harian Kelas (Umum)</option>
                            <option v-for="s in schedules" :key="s.id" :value="s.id">
                                Jam ke-{{ s.order }} ({{ s.time_slot }}): {{ s.subject_name }} - {{ s.teacher_name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. Dynamic Live KPI Counters Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 print:grid-cols-6">
                <!-- Total Siswa -->
                <div class="rounded-2xl bg-white dark:bg-slate-800 p-4 border border-gray-200/80 dark:border-slate-700/80 shadow-sm">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Total Siswa</span>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ liveStats.total }}</div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">1 Rombel Aktif</span>
                </div>

                <!-- Hadir -->
                <div class="rounded-2xl bg-white dark:bg-slate-800 p-4 border border-emerald-200 dark:border-emerald-900/60 shadow-sm">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block">Hadir (H)</span>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ liveStats.hadir }}</div>
                    <span class="text-[11px] text-emerald-700 dark:text-emerald-500 mt-0.5 block">Di Kelas</span>
                </div>

                <!-- Terlambat -->
                <div class="rounded-2xl bg-white dark:bg-slate-800 p-4 border border-purple-200 dark:border-purple-900/60 shadow-sm">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 block">Terlambat (T)</span>
                    <div class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ liveStats.terlambat }}</div>
                    <span class="text-[11px] text-purple-700 dark:text-purple-500 mt-0.5 block">Dispensasi</span>
                </div>

                <!-- Sakit -->
                <div class="rounded-2xl bg-white dark:bg-slate-800 p-4 border border-amber-200 dark:border-amber-900/60 shadow-sm">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 block">Sakit (S)</span>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ liveStats.sakit }}</div>
                    <span class="text-[11px] text-amber-700 dark:text-amber-500 mt-0.5 block">Surat/Klinik</span>
                </div>

                <!-- Izin -->
                <div class="rounded-2xl bg-white dark:bg-slate-800 p-4 border border-sky-200 dark:border-sky-900/60 shadow-sm">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400 block">Izin (I)</span>
                    <div class="text-2xl font-black text-sky-600 dark:text-sky-400 mt-1">{{ liveStats.izin }}</div>
                    <span class="text-[11px] text-sky-700 dark:text-sky-500 mt-0.5 block">Dispensasi</span>
                </div>

                <!-- Alpa / Persentase -->
                <div class="rounded-2xl bg-white dark:bg-slate-800 p-4 border border-rose-200 dark:border-rose-900/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 block">Alpa (A)</span>
                        <span class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                            {{ liveStats.rate }}
                        </span>
                    </div>
                    <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ liveStats.alpa }}</div>
                    <span class="text-[11px] text-rose-700 dark:text-rose-500 mt-0.5 block">Kehadiran</span>
                </div>
            </div>

            <!-- 4. Fast Action Toolbar & Search Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-200/80 dark:border-slate-800 shadow-sm print:hidden">
                <!-- Power Batch Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="markAllPresent"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        ⚡ Tandai Semua Hadir
                    </button>

                    <button
                        @click="setAllStatus('alpa')"
                        type="button"
                        class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-300 text-xs font-semibold transition"
                    >
                        Reset Semua
                    </button>
                </div>

                <!-- Search & Status Filter Chips -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative min-w-[200px]">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari nama / NISN..."
                            class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2 pl-8 pr-3 focus:ring-2 focus:ring-indigo-500"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Filter pills -->
                    <div class="inline-flex rounded-xl bg-gray-100 dark:bg-slate-800 p-1 text-xs">
                        <button
                            v-for="st in ['all', 'hadir', 'sakit', 'izin', 'alpa']"
                            :key="st"
                            @click="statusFilter = st"
                            :class="[
                                'px-2.5 py-1 rounded-lg font-bold capitalize transition',
                                statusFilter === st
                                    ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white'
                            ]"
                        >
                            {{ st === 'all' ? 'Semua' : st }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- 5. Interactive Attendance Table / Grid -->
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200/80 dark:border-slate-800 shadow-sm overflow-hidden print:border-black print:rounded-none">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/80 dark:bg-slate-800/80 border-b border-gray-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 print:text-black print:bg-gray-100">
                            <tr>
                                <th scope="col" class="py-3.5 px-4 w-12 text-center">No</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[240px]">Identitas Siswa</th>
                                <th scope="col" class="py-3.5 px-4 text-center min-w-[280px]">Status Presensi (H/T/S/I/A)</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[220px]">Catatan / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200/70 dark:divide-slate-800/70">
                            <tr
                                v-for="(student, idx) in filteredStudents"
                                :key="student.id"
                                :class="[
                                    'transition hover:bg-gray-50/60 dark:hover:bg-slate-800/50',
                                    student.status === 'alpa' ? 'bg-rose-50/30 dark:bg-rose-950/10' : '',
                                    student.status === 'sakit' ? 'bg-amber-50/30 dark:bg-amber-950/10' : '',
                                ]"
                            >
                                <!-- No Urut -->
                                <td class="py-3.5 px-4 text-center font-mono text-xs text-gray-400 dark:text-gray-500 font-bold">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Siswa -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Avatar / Initials -->
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                            {{ student.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white leading-tight">
                                                {{ student.name }}
                                            </div>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono mt-0.5 flex items-center gap-2">
                                                <span>NIS: {{ student.nis }}</span>
                                                <span>•</span>
                                                <span :class="student.gender === 'L' ? 'text-blue-500 font-bold' : 'text-pink-500 font-bold'">
                                                    {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Segmented Button Selector (H / T / S / I / A) -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Hadir (H) -->
                                        <button
                                            type="button"
                                            @click="setStudentStatus(student, 'hadir')"
                                            :class="[
                                                'px-3 py-1.5 rounded-xl font-bold text-xs transition-all flex items-center gap-1 active:scale-95',
                                                student.status === 'hadir'
                                                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 ring-2 ring-emerald-500/50'
                                                    : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600'
                                            ]"
                                            title="Hadir"
                                        >
                                            <span class="w-2 h-2 rounded-full" :class="student.status === 'hadir' ? 'bg-white' : 'bg-emerald-500'"></span>
                                            H
                                        </button>

                                        <!-- Terlambat (T) -->
                                        <button
                                            type="button"
                                            @click="setStudentStatus(student, 'terlambat')"
                                            :class="[
                                                'px-3 py-1.5 rounded-xl font-bold text-xs transition-all flex items-center gap-1 active:scale-95',
                                                student.status === 'terlambat'
                                                    ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30 ring-2 ring-purple-500/50'
                                                    : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 hover:bg-purple-50 dark:hover:bg-purple-950/40 hover:text-purple-600'
                                            ]"
                                            title="Terlambat"
                                        >
                                            <span class="w-2 h-2 rounded-full" :class="student.status === 'terlambat' ? 'bg-white' : 'bg-purple-500'"></span>
                                            T
                                        </button>

                                        <!-- Sakit (S) -->
                                        <button
                                            type="button"
                                            @click="setStudentStatus(student, 'sakit')"
                                            :class="[
                                                'px-3 py-1.5 rounded-xl font-bold text-xs transition-all flex items-center gap-1 active:scale-95',
                                                student.status === 'sakit'
                                                    ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/30 ring-2 ring-amber-400/50'
                                                    : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 hover:text-amber-600'
                                            ]"
                                            title="Sakit"
                                        >
                                            <span class="w-2 h-2 rounded-full" :class="student.status === 'sakit' ? 'bg-slate-950' : 'bg-amber-500'"></span>
                                            S
                                        </button>

                                        <!-- Izin (I) -->
                                        <button
                                            type="button"
                                            @click="setStudentStatus(student, 'izin')"
                                            :class="[
                                                'px-3 py-1.5 rounded-xl font-bold text-xs transition-all flex items-center gap-1 active:scale-95',
                                                student.status === 'izin'
                                                    ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30 ring-2 ring-sky-500/50'
                                                    : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 hover:text-sky-600'
                                            ]"
                                            title="Izin"
                                        >
                                            <span class="w-2 h-2 rounded-full" :class="student.status === 'izin' ? 'bg-white' : 'bg-sky-500'"></span>
                                            I
                                        </button>

                                        <!-- Alpa (A) -->
                                        <button
                                            type="button"
                                            @click="setStudentStatus(student, 'alpa')"
                                            :class="[
                                                'px-3 py-1.5 rounded-xl font-bold text-xs transition-all flex items-center gap-1 active:scale-95',
                                                student.status === 'alpa'
                                                    ? 'bg-rose-600 text-white shadow-md shadow-rose-600/30 ring-2 ring-rose-500/50'
                                                    : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600'
                                            ]"
                                            title="Alpa / Tanpa Keterangan"
                                        >
                                            <span class="w-2 h-2 rounded-full" :class="student.status === 'alpa' ? 'bg-white' : 'bg-rose-500'"></span>
                                            A
                                        </button>
                                    </div>

                                    <!-- Print Only Status Text -->
                                    <div class="hidden print:block text-center font-bold uppercase">
                                        {{ student.status }}
                                    </div>
                                </td>

                                <!-- Catatan / Keterangan -->
                                <td class="py-3.5 px-4">
                                    <input
                                        type="text"
                                        v-model="student.notes"
                                        placeholder="Keterangan (opsional)..."
                                        class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/60 text-gray-900 dark:text-white text-xs py-1.5 px-3 focus:ring-2 focus:ring-indigo-500 print:border-none print:bg-transparent"
                                    />
                                </td>
                            </tr>

                            <tr v-if="filteredStudents.length === 0">
                                <td colspan="4" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Tidak ada siswa ditemukan</p>
                                    <p class="text-xs text-gray-500 mt-1">Coba pilih kelas lain atau ubah filter pencarian Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Action Bar -->
                <div class="p-4 sm:p-5 bg-gray-50/80 dark:bg-slate-800/80 border-t border-gray-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Menampilkan <strong>{{ filteredStudents.length }}</strong> dari <strong>{{ studentsList.length }}</strong> siswa di kelas ini.
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            @click="saveAttendance"
                            :disabled="isSaving"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 disabled:opacity-50 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/25 transition transform active:scale-95"
                        >
                            <svg v-if="!isSaving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-4 h-4 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Presensi Kelas' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Print-Only Footer Signatures -->
            <div class="hidden print:flex justify-between items-start mt-12 px-8 text-xs text-black">
                <div class="text-center">
                    <p>Mengetahui,</p>
                    <p class="font-bold">Wali Kelas</p>
                    <div class="h-16"></div>
                    <p class="font-bold underline">( {{ selectedClassroom?.homeroom_teacher || '_____________________' }} )</p>
                </div>

                <div class="text-center">
                    <p>Kota Probolinggo, {{ selectedDate }}</p>
                    <p class="font-bold">Guru Pengajar / Guru Piket</p>
                    <div class="h-16"></div>
                    <p class="font-bold underline">( _____________________ )</p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
}
</style>
