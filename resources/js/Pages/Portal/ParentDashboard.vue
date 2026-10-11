<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    parent: Object,
    children: Array,
});

const selectedChildIndex = ref(0);

const activeChild = computed(() => {
    return props.children && props.children.length > 0
        ? props.children[selectedChildIndex.value]
        : null;
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 pb-20">
        <Head title="Portal Orang Tua / Wali Murid - Monitoring Terpadu" />

        <!-- Top Header Bar -->
        <header class="sticky top-0 z-30 backdrop-blur-md bg-slate-900/90 border-b border-slate-800 px-4 py-3">
            <div class="max-w-lg mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-rose-500 to-amber-500 flex items-center justify-center font-bold text-white shadow-lg">
                        {{ parent.name.charAt(0) }}
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-white leading-tight">{{ parent.name }}</h1>
                        <p class="text-xs text-rose-400 font-medium">Portal Wali Murid Terpadu</p>
                    </div>
                </div>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition text-xs flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Keluar
                </Link>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-lg mx-auto px-4 py-4 space-y-4">
            <!-- 1. Child Switcher (Jika memiliki lebih dari 1 anak) -->
            <div v-if="children.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="(child, idx) in children"
                    :key="child.id"
                    type="button"
                    @click="selectedChildIndex = idx"
                    :class="[
                        'flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all border',
                        selectedChildIndex === idx
                            ? 'bg-rose-600 border-rose-500 text-white shadow-lg shadow-rose-900/30'
                            : 'bg-slate-900 border-slate-800 text-slate-400 hover:bg-slate-800'
                    ]"
                >
                    <span class="w-2 h-2 rounded-full" :class="selectedChildIndex === idx ? 'bg-white' : 'bg-rose-400'"></span>
                    {{ child.name }} ({{ child.classroom }})
                </button>
            </div>

            <template v-if="activeChild">
                <!-- 2. Real-Time IoT Attendance Alert Banner -->
                <div class="rounded-2xl bg-gradient-to-r from-emerald-950/80 via-slate-900 to-slate-900 p-4 border border-emerald-500/40 relative overflow-hidden">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/20 text-emerald-400 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    LIVE PRESENSI IOT
                                </span>
                                <span class="text-[11px] text-slate-400 font-mono">{{ activeChild.attendance_today.time }}</span>
                            </div>
                            <h3 class="text-sm font-bold text-white mt-1">
                                {{ activeChild.name }} telah hadir di sekolah
                            </h3>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Terverifikasi via RFID di {{ activeChild.attendance_today.gate }} (UID: {{ activeChild.rfid_uid }}).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3. Child Profile Card -->
                <div class="rounded-2xl bg-slate-900/80 backdrop-blur-md p-4 border border-slate-800">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-rose-400">Data Siswa</span>
                            <h2 class="text-base font-bold text-white mt-0.5">{{ activeChild.name }}</h2>
                            <p class="text-xs text-slate-400">NISN: {{ activeChild.nisn }} • Kelas {{ activeChild.classroom }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-400 block">Wali Kelas:</span>
                            <span class="text-xs font-semibold text-slate-200">{{ activeChild.homeroom_teacher }}</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                        <a
                            :href="'https://wa.me/6281234567801?text=Halo%20Bpk/Ibu%20Wali%20Kelas%20dari%20' + activeChild.name"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 text-xs font-semibold transition border border-emerald-500/30"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z" />
                            </svg>
                            Hubungi Wali Kelas
                        </a>

                        <span class="text-xs text-slate-400">Tahun Ajaran 2024/2025</span>
                    </div>
                </div>

                <!-- 4. SPP & Keuangan Sekolah -->
                <div class="rounded-2xl bg-slate-900/80 backdrop-blur-md p-4 border border-slate-800">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            SPP & Keuangan Siswa
                        </h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            {{ activeChild.bills.spp_status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                            <span class="text-[10px] text-slate-400 block uppercase">Tagihan SPP Bulanan</span>
                            <span class="text-base font-bold text-white">Rp {{ activeChild.bills.spp_amount.toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                            <span class="text-[10px] text-slate-400 block uppercase">Saldo Tabungan Siswa</span>
                            <span class="text-base font-bold text-amber-400">Rp {{ activeChild.bills.savings_balance.toLocaleString('id-ID') }}</span>
                        </div>
                    </div>

                    <div class="mt-3 text-right">
                        <button class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shadow-md">
                            Rincian Pembayaran & Invoice
                        </button>
                    </div>
                </div>

                <!-- 5. Bimbingan Konseling (BK) & Catatan Karakter -->
                <div class="rounded-2xl bg-slate-900/80 backdrop-blur-md p-4 border border-slate-800">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Laporan Karakter & Konseling Siswa (BK)
                    </h3>

                    <div class="space-y-2">
                        <div
                            v-for="(note, nIdx) in activeChild.bk_notes"
                            :key="nIdx"
                            class="p-3 rounded-xl bg-slate-950/60 border border-purple-500/20"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-400">{{ note.category }}</span>
                                <span class="text-[10px] font-mono text-slate-400">{{ note.date }}</span>
                            </div>
                            <p class="text-xs text-slate-200 mt-1">{{ note.note }}</p>
                            <div class="mt-2 text-[11px] font-bold text-indigo-400">{{ note.points }}</div>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</template>
