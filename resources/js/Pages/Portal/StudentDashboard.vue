<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    student: Object,
    attendance: Object,
    schedule: Array,
    wallet: Object,
    cbtExam: Object,
});

const activeNav = ref('home');
</script>

<template>
    <div class="min-h-screen bg-slate-900 text-slate-100 pb-24">
        <Head title="Portal Siswa - Kartu Pelajar Digital & LMS" />

        <!-- Top App Bar -->
        <header class="sticky top-0 z-30 backdrop-blur-md bg-slate-900/80 border-b border-slate-800/80 px-4 py-3">
            <div class="max-w-md mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-amber-500 to-indigo-600 flex items-center justify-center font-bold text-white shadow-lg">
                        {{ student.name.charAt(0) }}
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-white leading-tight">{{ student.name }}</h1>
                        <p class="text-xs text-slate-400">{{ student.classroom }} • NISN: {{ student.nisn }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white transition text-xs flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Keluar
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="max-w-md mx-auto px-4 py-4 space-y-4">
            <!-- 1. Digital Student ID Card (Glassmorphism) -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-800 to-slate-900 p-5 shadow-2xl border border-indigo-400/20">
                <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-indigo-500/20 rounded-full blur-2xl"></div>
                <div class="absolute top-0 right-0 p-4 opacity-15 text-6xl font-black">
                    ID
                </div>

                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/20 text-white backdrop-blur-sm">
                            Kartu Pelajar Digital
                        </span>
                        <h2 class="text-lg font-black text-white mt-1">SMK INFORMATIKA TERPADU</h2>
                    </div>
                    <div class="text-right">
                        <span class="text-[11px] font-mono text-indigo-200">RFID SYNC</span>
                        <div class="flex items-center gap-1 text-[10px] text-emerald-300 font-semibold justify-end">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            {{ attendance.rfid_uid }}
                        </div>
                    </div>
                </div>

                <div class="space-y-1 mb-4">
                    <div class="text-xl font-bold text-white tracking-wide">{{ student.name }}</div>
                    <div class="text-xs text-indigo-200">Jurusan: {{ student.department }}</div>
                    <div class="text-xs text-indigo-200">Wali Kelas: {{ student.homeroom_teacher }}</div>
                </div>

                <div class="pt-3 border-t border-indigo-400/20 flex justify-between items-center text-xs">
                    <div>
                        <div class="text-[10px] text-indigo-300 uppercase">NISN Siswa</div>
                        <div class="font-mono font-bold text-white text-sm tracking-wider">{{ student.nisn }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg text-right font-mono text-xs text-indigo-100">
                        STATUS: AKTIF
                    </div>
                </div>
            </div>

            <!-- 2. Attendance & E-Wallet Quick Widgets -->
            <div class="grid grid-cols-2 gap-3">
                <!-- Status Kehadiran IoT -->
                <div class="rounded-2xl bg-slate-800/60 backdrop-blur-md p-4 border border-slate-700/60 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-400 font-medium">Presensi Hari Ini</span>
                            <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="text-base font-bold text-emerald-400">{{ attendance.status }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Tap In: {{ attendance.check_in_time }}</div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-slate-700/40 text-[10px] text-slate-500 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        IoT RFID Standalone
                    </div>
                </div>

                <!-- E-Kantin Digital Balance -->
                <div class="rounded-2xl bg-slate-800/60 backdrop-blur-md p-4 border border-slate-700/60 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-400 font-medium">Dompet E-Kantin</span>
                            <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                        </div>
                        <div class="text-base font-bold text-amber-400">Rp {{ wallet.balance.toLocaleString('id-ID') }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Limit/hari: Rp {{ wallet.daily_limit.toLocaleString('id-ID') }}</div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-slate-700/40 flex justify-between items-center text-[10px]">
                        <span class="text-slate-400">PIN 6-Digit</span>
                        <span class="text-amber-400 font-semibold cursor-pointer">Bayar QR</span>
                    </div>
                </div>
            </div>

            <!-- 3. CBT Ujian Aktif Banner (Semi-Offline ANBK & Exambro APK) -->
            <div v-if="cbtExam.is_active" class="rounded-2xl bg-gradient-to-r from-rose-900/80 via-purple-900/70 to-slate-800 p-4 border border-rose-500/30 relative overflow-hidden">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 rounded-xl bg-rose-500/20 text-rose-400 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500 text-white animate-pulse">
                                CBT LIVE
                            </span>
                            <span class="text-[11px] text-rose-300 font-medium">{{ cbtExam.mode }}</span>
                        </div>
                        <h3 class="text-sm font-bold text-white mt-1">{{ cbtExam.title }}</h3>
                        <p class="text-xs text-slate-300 mt-0.5">Mapel: {{ cbtExam.subject }} • Durasi: {{ cbtExam.duration_minutes }} Menit</p>

                        <div class="mt-3 flex items-center justify-between pt-2 border-t border-rose-500/20">
                            <div class="text-xs font-mono font-bold text-amber-300">
                                TOKEN: {{ cbtExam.token }}
                            </div>
                            <button class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition shadow-lg flex items-center gap-1.5">
                                <span>Buka Ujian</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Today's Timetable Schedule -->
            <div class="rounded-2xl bg-slate-800/60 backdrop-blur-md p-4 border border-slate-700/60">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Jadwal Belajar Hari Ini
                    </h3>
                    <span class="text-xs text-slate-400">Senin, 15 Sep 2026</span>
                </div>

                <div class="space-y-2.5">
                    <div
                        v-for="(item, idx) in schedule"
                        :key="idx"
                        class="p-3 rounded-xl bg-slate-900/60 border border-slate-700/40 flex items-center justify-between"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-indigo-300">{{ item.time }}</span>
                                <span
                                    class="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                                    :class="{
                                        'bg-emerald-500/20 text-emerald-400': item.status === 'Selesai',
                                        'bg-amber-500/20 text-amber-400': item.status === 'Berlangsung',
                                        'bg-slate-700 text-slate-300': item.status === 'Mendatang'
                                    }"
                                >
                                    {{ item.status }}
                                </span>
                            </div>
                            <div class="text-xs font-bold text-white mt-1">{{ item.subject }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ item.teacher }} • {{ item.room }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Bottom Navigation Bar (Mobile Native Look) -->
        <nav class="fixed bottom-0 left-0 right-0 z-40 bg-slate-900/95 backdrop-blur-lg border-t border-slate-800 px-4 py-2">
            <div class="max-w-md mx-auto flex justify-around items-center">
                <button
                    type="button"
                    @click="activeNav = 'home'"
                    :class="['flex flex-col items-center py-1 px-3 rounded-lg transition', activeNav === 'home' ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-200']"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="text-[10px] font-medium mt-1">Beranda</span>
                </button>

                <button
                    type="button"
                    @click="activeNav = 'schedule'"
                    :class="['flex flex-col items-center py-1 px-3 rounded-lg transition', activeNav === 'schedule' ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-200']"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-[10px] font-medium mt-1">Jadwal</span>
                </button>

                <button
                    type="button"
                    @click="activeNav = 'cbt'"
                    :class="['flex flex-col items-center py-1 px-3 rounded-lg transition', activeNav === 'cbt' ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-200']"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span class="text-[10px] font-medium mt-1">CBT Ujian</span>
                </button>

                <button
                    type="button"
                    @click="activeNav = 'kantin'"
                    :class="['flex flex-col items-center py-1 px-3 rounded-lg transition', activeNav === 'kantin' ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-200']"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span class="text-[10px] font-medium mt-1">E-Kantin</span>
                </button>
            </div>
        </nav>
    </div>
</template>
