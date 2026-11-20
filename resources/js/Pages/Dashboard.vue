<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: Object,
    stats: Object,
    schedule: Array,
    meetings: Array,
    cbtOverview: Object,
    academicYear: String,
});

const menus = [
    { label: 'Dashboard Guru', route: 'dashboard', icon: 'home' },
    { label: 'Jadwal Mengajar', route: 'academic.schedules', icon: 'calendar' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
    { label: 'Materi & LMS', route: 'academic.learning.index', icon: 'book-open' },
    { label: 'Penilaian Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Buku Rapor & Leger', route: 'academic.reports.index', icon: 'document' },
    { label: 'CBT Ujian Online', route: 'academic.cbt.index', icon: 'desktop' },
    { label: 'Verifikasi E-Leave', route: 'attendance.leave.index', icon: 'clipboard-check' },
    { label: 'Tata Tertib Siswa', route: 'counseling.violations.index', icon: 'clipboard-check' },
];

const selectedTab = ref('schedule');
</script>

<template>
    <Head title="Dashboard Guru - SIAKAD & LMS Terpadu" />

    <AuthenticatedLayout :menus="menus">
        <!-- Main Content -->
        <div class="p-4 sm:p-6 lg:p-8 space-y-6">
            <!-- 1. Hero Welcome Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-700 via-indigo-900 to-slate-900 p-6 shadow-xl text-white">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                👨‍🏫 {{ user.role_label }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                Wali Kelas {{ user.homeroom_class }}
                            </span>
                            <span class="text-xs text-indigo-200 font-mono">
                                TA {{ academicYear }}
                            </span>
                        </div>
                        <h1 class="text-2xl font-black tracking-tight">
                            Selamat Datang, {{ user.name }}! 👋
                        </h1>
                        <p class="text-sm text-indigo-200 mt-1">
                            NIP: {{ user.nip }} • NUPTK: {{ user.nuptk }} • Sistem Informasi Akademik & LMS Terpadu
                        </p>
                    </div>

                    <!-- Quick Action Chips -->
                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="route('academic.learning.index')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold backdrop-blur-md transition border border-white/20"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Buat Materi LMS
                        </Link>
                        <Link
                            :href="route('attendance.classroom.index')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold shadow-lg transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            Mulai Presensi Kelas
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. Ringkasan Statistik Utama -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Stat 1: Jam Mengajar Hari Ini -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-gray-800 dark:border-gray-700/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jam Mengajar Hari Ini</span>
                        <span class="p-2 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-gray-900 dark:text-white">{{ stats.total_teaching_hours }} JP</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">({{ stats.classes_count }} Rombel Kelas)</span>
                    </div>
                    <div class="mt-2 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                        ✓ 1 Kelas telah selesai diajar
                    </div>
                </div>

                <!-- Stat 2: Kelas Binaan Wali Kelas -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-gray-800 dark:border-gray-700/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kelas Binaan (Wali)</span>
                        <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-gray-900 dark:text-white">{{ stats.homeroom_class }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">({{ stats.homeroom_students }} Siswa)</span>
                    </div>
                    <div class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                        Rekayasa Perangkat Lunak
                    </div>
                </div>

                <!-- Stat 3: Rata-Rata Kehadiran Siswa -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-gray-800 dark:border-gray-700/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Presensi Siswa Hari Ini</span>
                        <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ stats.attendance_rate }}</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Sinkronisasi IoT RFID Gate Sekolah
                    </div>
                </div>

                <!-- Stat 4: Tugas Masuk Perlu Dinilai -->
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-gray-800 dark:border-gray-700/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tugas Perlu Dinilai</span>
                        <span class="p-2 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ stats.pending_assignments }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">Pengumpulan Siswa</span>
                    </div>
                    <div class="mt-2 text-xs text-amber-700 dark:text-amber-300 font-medium">
                        Modul Pemrograman Web
                    </div>
                </div>
            </div>

            <!-- 3. Jadwal Mengajar Hari Ini & Live CBT Node Overview -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Kolom Kiri (2 Kolom): Jadwal Pelajaran Hari Ini -->
                <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm border border-gray-200/80 dark:bg-gray-800 dark:border-gray-700/80">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Jadwal Mengajar Hari Ini
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Senin, Semester Ganjil 2024/2025</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-bold dark:bg-indigo-950 dark:text-indigo-300">
                            3 Sesi Kelas
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(item, idx) in schedule"
                            :key="idx"
                            class="p-4 rounded-xl border transition hover:shadow-sm"
                            :class="[
                                item.status === 'Berlangsung'
                                    ? 'bg-indigo-50/50 border-indigo-200 dark:bg-indigo-950/20 dark:border-indigo-800'
                                    : 'bg-gray-50/50 border-gray-200 dark:bg-gray-900/40 dark:border-gray-700/60'
                            ]"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-white text-indigo-700 shadow-sm border border-gray-200 dark:bg-gray-800 dark:text-indigo-400 dark:border-gray-700">
                                            {{ item.time }}
                                        </span>
                                        <span class="font-bold text-xs text-gray-900 dark:text-white px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700">
                                            Kelas {{ item.classroom }}
                                        </span>
                                        <span
                                            class="text-[10px] font-bold px-2 py-0.5 rounded uppercase"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300': item.status === 'Selesai',
                                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 animate-pulse': item.status === 'Berlangsung',
                                                'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300': item.status === 'Mendatang',
                                            }"
                                        >
                                            {{ item.status }}
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mt-1.5">
                                        {{ item.subject }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Lokasi: {{ item.room }} • Siswa: {{ item.students_count }} orang
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <Link
                                        :href="route('attendance.classroom.index', item.classroom_id ? { classroom_id: item.classroom_id, schedule_id: item.schedule_id } : {})"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                                        :class="item.status === 'Berlangsung'
                                            ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-md'
                                            : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'"
                                    >
                                        {{ item.status === 'Berlangsung' ? 'Input Absensi Sekarang' : 'Lihat Jurnal' }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan (1 Kolom): Status Node CBT Terpisah & Notulen Rapat -->
                <div class="space-y-6">
                    <!-- Status CBT Node Terpisah -->
                    <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-gray-800 dark:border-gray-700/80">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                Decoupled Node CBT
                            </h3>
                            <span class="text-[11px] font-mono text-emerald-600 font-semibold dark:text-emerald-400">14ms Latency</span>
                        </div>

                        <div class="rounded-xl bg-slate-900 text-white p-4 space-y-2">
                            <div class="text-xs font-bold text-indigo-300">{{ cbtOverview.connected_node }}</div>
                            <div class="text-sm font-bold text-white leading-tight">{{ cbtOverview.latest_exam_title }}</div>
                            <div class="pt-2 border-t border-slate-800 flex justify-between items-center text-xs">
                                <span class="text-slate-400">Token Ujian:</span>
                                <span class="font-mono font-bold text-amber-400 tracking-wider">{{ cbtOverview.token }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-400">Jawaban Terkirim:</span>
                                <span class="font-bold text-emerald-400">{{ cbtOverview.submitted_count }} / {{ cbtOverview.total_candidates }} Siswa</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <Link
                                :href="route('academic.cbt.index')"
                                class="block w-full py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-semibold text-xs transition text-center"
                            >
                                Kelola Bank Soal & Jadwal CBT →
                            </Link>
                        </div>
                    </div>

                    <!-- Modul Ajar & E-Learning Siswa -->
                    <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-200/80 dark:bg-gray-800 dark:border-gray-700/80">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Materi & E-Learning Siswa
                            </h3>
                            <span class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold">Tugas Terkumpul</span>
                        </div>

                        <div class="space-y-3">
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200/60 dark:border-gray-700/60">
                                <div class="text-xs font-bold text-gray-900 dark:text-white leading-snug">Modul Pemrograman Web & Mobile Devices</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Kelas X - RPL 1 • 36 Siswa Terdaftar</div>
                                <div class="mt-2 flex items-center justify-between pt-1.5 border-t border-gray-200/40 dark:border-gray-700/40 text-[10px]">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">12 Tugas Menunggu Penilaian</span>
                                    <Link :href="route('academic.learning.index')" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                        Buka LMS
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <Link
                                :href="route('academic.learning.index')"
                                class="block w-full py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-300 font-semibold text-xs transition text-center"
                            >
                                Kelola Seluruh Materi & Tugas LMS →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
