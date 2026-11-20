<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    user: Object,
    school: Object,
    kpi: Object,
    recentAttendances: Array,
    recentPayments: Array,
    pendingLeaves: Array,
    classroomsOverview: Array,
});

// Menu Navigasi Khusus Administrator Sekolah (Operasional Cepat)
const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
    { label: 'Materi & Tugas (LMS)', route: 'academic.learning.index', icon: 'book-open' },
    { label: 'CBT Ujian Online', route: 'academic.cbt.index', icon: 'desktop' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Buku Rapor & Leger', route: 'academic.reports.index', icon: 'document' },
    { label: 'Tagihan & SPP Massal', route: 'finance.bills.index', icon: 'credit-card' },
    { label: 'POS Kasir SPP', route: 'finance.pos', icon: 'credit-card' },
    { label: 'Jadwal Pelajaran', route: 'academic.schedules', icon: 'calendar' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'Tata Tertib & BK', route: 'counseling.violations.index', icon: 'clipboard-check' },
    { label: 'Cetak Massal KTS', route: 'kts.batch', icon: 'id-card' },
    { label: 'Radius GPS & Shift', route: 'attendance.settings.index', icon: 'location' },
    { label: 'Presensi GPS & AI', route: 'attendance.gps.index', icon: 'location' },
    { label: 'Verifikasi E-Leave', route: 'attendance.leave.index', icon: 'clipboard-check' },
    { label: 'Dompet Digital Siswa', route: 'wallet.index', icon: 'wallet' },
    { label: 'E-Kantin & Stand', route: 'canteen.index', icon: 'shopping-bag' },
    { label: 'PPDB Murid Baru', route: 'academic.ppdb.index', icon: 'users' },
    { label: 'Rapat & BAP Digital', route: 'academic.meetings.index', icon: 'document' },
    { isHeader: true, label: 'Master Data Akademik' },
    { label: 'Tahun Ajaran & Semester', route: 'academic.academic-years.index', icon: 'calendar' },
    { label: 'Program Keahlian (Jurusan)', route: 'academic.departments.index', icon: 'academic-cap' },
    { label: 'Data Siswa & Rombel', route: 'academic.students.index', icon: 'users' },
    { label: 'Data Guru & PTK', route: 'academic.teachers.index', icon: 'academic-cap' },
    { label: 'Rombel (Kelas)', route: 'academic.classrooms.index', icon: 'rectangle-stack' },
    { label: 'Mata Pelajaran', route: 'academic.subjects.index', icon: 'book-open' },
];

// Tab Interaktif
const activeTab = ref('attendances');

// Jam Real-time Digital WIB
const currentTime = ref('');
let timer = null;

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }) + ' WIB';
};

onMounted(() => {
    updateClock();
    timer = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <Head :title="`${school.name} - Executive School Command Center`" />

    <AuthenticatedLayout :menus="adminMenus" section-title="Operasional Sekolah">
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
            
            <!-- 1. Executive Top Hero Header -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 shadow-2xl p-6 sm:p-8 text-white">
                <!-- Background Decorative Glows -->
                <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <!-- Pill Status Bar -->
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                                {{ user.role_label }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Latency {{ school.response_time }} (SPA 0ms)
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-medium bg-slate-800/80 text-slate-300 border border-slate-700/60">
                                TA {{ school.academic_year }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                {{ currentTime }}
                            </span>
                        </div>

                        <!-- School & Greeting -->
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                            {{ school.name }}
                            <span class="text-xs px-2.5 py-1 rounded-lg bg-white/10 text-slate-300 font-mono font-normal">NPSN: {{ school.npsn }}</span>
                        </h1>
                        <p class="text-sm text-slate-300 mt-2 max-w-3xl leading-relaxed">
                            Pusat Komando Eksekutif Operasional Sekolah. Akses seketika ke POS kasir SPP, cetak kartu pelajar ISO CR-80, absensi geofencing, dompet digital, dan transaksi e-kantin tanpa jeda waktu (zero reload).
                        </p>
                    </div>

                    <!-- Quick Central Switcher / External Database Link -->
                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <Link
                            :href="route('finance.pos')"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/25 transition transform active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            Buka POS Kasir SPP
                        </Link>
                        
                        <a
                            href="/admin"
                            class="inline-flex items-center gap-2 px-4 py-3 rounded-2xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 hover:text-white font-semibold text-xs border border-slate-700 transition"
                            title="Panel Manajemen Database Master Filament"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                            Database Master
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. Executive KPI Cards (6 Grid Metrics) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <!-- Stat 1: Total Siswa Aktif -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-slate-800 dark:border-slate-700/80 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Siswa</span>
                        <span class="p-2 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-gray-900 dark:text-white">{{ kpi.total_students }}</div>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-1">
                            100% Terdaftar ISO KTS
                        </p>
                    </div>
                </div>

                <!-- Stat 2: Dewan Guru & Staf -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-slate-800 dark:border-slate-700/80 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Guru & Staf</span>
                        <span class="p-2 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-gray-900 dark:text-white">{{ kpi.total_teachers }}</div>
                        <p class="text-xs text-purple-600 dark:text-purple-400 font-medium mt-1">
                            {{ kpi.total_classrooms }} Rombel Kelas
                        </p>
                    </div>
                </div>

                <!-- Stat 3: Presensi Hari Ini -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-slate-800 dark:border-slate-700/80 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Presensi Hari Ini</span>
                        <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ kpi.attendance.rate }}</div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ kpi.attendance.present }} Hadir • {{ kpi.attendance.late }} Terlambat
                        </p>
                    </div>
                </div>

                <!-- Stat 4: SPP Terhimpun Bulan Ini -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-slate-800 dark:border-slate-700/80 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kas SPP Bulan Ini</span>
                        <span class="p-2 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl font-black text-amber-600 dark:text-amber-400 truncate">{{ kpi.finance.formatted_collected }}</div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">
                            Pending: {{ kpi.finance.formatted_pending }}
                        </p>
                    </div>
                </div>

                <!-- Stat 5: E-Kantin & Dompet Digital -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-slate-800 dark:border-slate-700/80 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Omset Kantin Hari Ini</span>
                        <span class="p-2 rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl font-black text-teal-600 dark:text-teal-400 truncate">{{ kpi.canteen_and_wallet.formatted_canteen_turnover }}</div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">
                            Saldo Siswa: {{ kpi.canteen_and_wallet.formatted_wallet_balance }}
                        </p>
                    </div>
                </div>

                <!-- Stat 6: Status CBT Online Node -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-slate-800 dark:border-slate-700/80 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Node CBT Cluster</span>
                        <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ kpi.cbt.nodes_online }} Node</div>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-1 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            {{ kpi.cbt.system_health }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. Quick Action Launchers (Pusat Operasional Cepat 1-Klik) -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            Pusat Peluncuran Cepat (Quick Action Hub)
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Akses langsung modul administrasi sekolah tanpa memuat ulang halaman (0ms Single Page Application)
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3 sm:gap-4">
                    <!-- Action 0: Presensi Kelas & Mapel -->
                    <Link
                        :href="route('attendance.classroom.index')"
                        class="group p-4 rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-indigo-500/50 dark:hover:border-indigo-500/50 transition-all flex flex-col items-center text-center text-slate-800 dark:text-slate-100"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <span class="mt-3 text-xs font-bold block">Presensi Kelas</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Absensi Jam/Mapel</span>
                    </Link>

                    <!-- Action 1: POS Kasir SPP -->
                    <Link
                        :href="route('finance.pos')"
                        class="group p-4 rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-emerald-500/50 dark:hover:border-emerald-500/50 transition-all flex flex-col items-center text-center text-slate-800 dark:text-slate-100"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <span class="mt-3 text-xs font-bold block">POS Kasir SPP</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Kuitansi & Kasir</span>
                    </Link>

                    <!-- Action 2: Cetak Massal KTS -->
                    <Link
                        :href="route('kts.batch')"
                        class="group p-4 rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-indigo-500/50 dark:hover:border-indigo-500/50 transition-all flex flex-col items-center text-center text-slate-800 dark:text-slate-100"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <span class="mt-3 text-xs font-bold block">Cetak KTS CR-80</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Kartu Pelajar Massal</span>
                    </Link>

                    <!-- Action 3: Presensi GPS & Face AI -->
                    <Link
                        :href="route('attendance.gps.index')"
                        class="group p-4 rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-purple-500/50 dark:hover:border-purple-500/50 transition-all flex flex-col items-center text-center text-slate-800 dark:text-slate-100"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-pink-500 text-white flex items-center justify-center shadow-lg shadow-purple-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="mt-3 text-xs font-bold block">Presensi GPS Face</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Geofence & AI Radius</span>
                    </Link>

                    <!-- Action 4: Verifikasi E-Leave -->
                    <Link
                        :href="route('attendance.leave.index')"
                        class="group p-4 rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-amber-500/50 dark:hover:border-amber-500/50 transition-all flex flex-col items-center text-center text-slate-800 dark:text-slate-100"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="mt-3 text-xs font-bold block">Verifikasi Izin</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Sakit & Dispensasi</span>
                    </Link>

                    <!-- Action 5: Dompet & Tabungan Siswa -->
                    <Link
                        :href="route('wallet.index')"
                        class="group p-4 rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-blue-500/50 dark:hover:border-blue-500/50 transition-all flex flex-col items-center text-center text-slate-800 dark:text-slate-100"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-500 text-white flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <span class="mt-3 text-xs font-bold block">Dompet Digital</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Top-up & Saldo Siswa</span>
                    </Link>

                    <!-- Action 6: E-Kantin & Stand POS -->
                    <Link
                        :href="route('canteen.index')"
                        class="group p-4 rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-rose-500/50 dark:hover:border-rose-500/50 transition-all flex flex-col items-center text-center text-slate-800 dark:text-slate-100"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-red-500 text-white flex items-center justify-center shadow-lg shadow-rose-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <span class="mt-3 text-xs font-bold block">E-Kantin Sekolah</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Kasir Stand & Menu</span>
                    </Link>
                </div>
            </div>

            <!-- 4. Interactive Tabbed Live Activity Feed -->
            <div class="rounded-3xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm overflow-hidden">
                <!-- Tab Headers -->
                <div class="border-b border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-850 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                        <button
                            @click="activeTab = 'attendances'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                            :class="activeTab === 'attendances'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700'"
                        >
                            📡 Presensi Real-Time
                        </button>
                        <button
                            @click="activeTab = 'payments'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                            :class="activeTab === 'payments'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700'"
                        >
                            💳 Arus Kasir SPP
                        </button>
                        <button
                            @click="activeTab = 'leaves'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                            :class="activeTab === 'leaves'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700'"
                        >
                            📝 Antrean E-Leave
                        </button>
                        <button
                            @click="activeTab = 'classrooms'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                            :class="activeTab === 'classrooms'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700'"
                        >
                            🏫 Rombel & Kelas
                        </button>
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live Client Feed (0ms delay)
                    </div>
                </div>

                <!-- Tab 1: Live Attendances Feed -->
                <div v-show="activeTab === 'attendances'" class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                                    <th class="pb-3">Siswa / Tenaga</th>
                                    <th class="pb-3">Rombel Kelas</th>
                                    <th class="pb-3">Waktu Tapping</th>
                                    <th class="pb-3">Metode Verifikasi</th>
                                    <th class="pb-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
                                <tr v-for="att in recentAttendances" :key="att.id" class="hover:bg-gray-50/60 dark:hover:bg-slate-750/50 transition">
                                    <td class="py-3.5 font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white flex items-center justify-center font-black text-xs shrink-0">
                                            {{ att.name.charAt(0) }}
                                        </div>
                                        <span>{{ att.name }}</span>
                                    </td>
                                    <td class="py-3.5 text-gray-600 dark:text-gray-300">
                                        <span class="px-2 py-1 rounded-md bg-gray-100 dark:bg-slate-700 font-semibold text-[11px]">
                                            {{ att.classroom }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 font-mono text-gray-600 dark:text-gray-300">
                                        {{ att.time }}
                                    </td>
                                    <td class="py-3.5 text-gray-600 dark:text-gray-300">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                            {{ att.method }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 text-right">
                                        <span
                                            class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                            :class="att.status === 'present'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300'
                                                : 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300'"
                                        >
                                            {{ att.status === 'present' ? 'Tepat Waktu' : 'Terlambat' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Recent SPP Payments -->
                <div v-show="activeTab === 'payments'" class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                                    <th class="pb-3">Siswa Pembayar</th>
                                    <th class="pb-3">Kelas</th>
                                    <th class="pb-3">Nominal Terbayar</th>
                                    <th class="pb-3">Kanal Pembayaran</th>
                                    <th class="pb-3">Tanggal / Waktu</th>
                                    <th class="pb-3 text-right">Aksi Kuitansi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
                                <tr v-for="pay in recentPayments" :key="pay.id" class="hover:bg-gray-50/60 dark:hover:bg-slate-750/50 transition">
                                    <td class="py-3.5 font-bold text-gray-900 dark:text-white">
                                        {{ pay.student_name }}
                                    </td>
                                    <td class="py-3.5 text-gray-600 dark:text-gray-300">
                                        {{ pay.classroom }}
                                    </td>
                                    <td class="py-3.5 font-mono font-black text-emerald-600 dark:text-emerald-400">
                                        {{ pay.amount }}
                                    </td>
                                    <td class="py-3.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                            {{ pay.method }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 text-gray-500 dark:text-gray-400">
                                        {{ pay.date }}
                                    </td>
                                    <td class="py-3.5 text-right">
                                        <Link
                                            :href="route('finance.receipt', pay.hash_id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:hover:bg-indigo-900 dark:text-indigo-300 font-bold text-[11px] transition"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Cetak Struk
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: Pending E-Leaves -->
                <div v-show="activeTab === 'leaves'" class="p-6">
                    <div class="space-y-3">
                        <div
                            v-for="leave in pendingLeaves"
                            :key="leave.id"
                            class="p-4 rounded-2xl bg-gray-50 dark:bg-slate-900/40 border border-gray-200/60 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ leave.user_name }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                        {{ leave.type }}
                                    </span>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ leave.start_date }} ({{ leave.days_count }} hari)
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1.5 italic">
                                    "{{ leave.reason }}"
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <Link
                                    :href="route('attendance.leave.index')"
                                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow transition"
                                >
                                    Verifikasi Sekarang →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Classrooms Overview -->
                <div v-show="activeTab === 'classrooms'" class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="cls in classroomsOverview"
                            :key="cls.id"
                            class="p-4 rounded-2xl bg-gray-50 dark:bg-slate-900/40 border border-gray-200/60 dark:border-slate-700/60 flex items-center justify-between"
                        >
                            <div>
                                <h4 class="text-sm font-black text-gray-900 dark:text-white">{{ cls.name }}</h4>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mt-0.5">{{ cls.department }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-black text-gray-900 dark:text-white">{{ cls.students_count }}</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block">Siswa Aktif</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
