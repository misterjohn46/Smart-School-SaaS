<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    exam: Object,
    submission: Object,
    student: Object,
});
</script>

<template>
    <Head :title="`Hasil Ujian: ${exam.title}`" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans p-4 sm:p-6 justify-center items-center">
        <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 text-center relative overflow-hidden">
            <!-- Glow background -->
            <div
                :class="[
                    'absolute -top-20 left-1/2 -translate-x-1/2 w-48 h-48 rounded-full blur-3xl pointer-events-none',
                    submission.is_passed ? 'bg-emerald-500/10' : 'bg-rose-500/10'
                ]"
            ></div>

            <!-- Icon Header -->
            <div class="relative z-10">
                <div
                    :class="[
                        'w-20 h-20 rounded-3xl mx-auto flex items-center justify-center shadow-lg',
                        submission.is_passed
                            ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 shadow-emerald-500/20'
                            : 'bg-rose-500/20 text-rose-400 border border-rose-500/30 shadow-rose-500/20'
                    ]"
                >
                    <svg v-if="submission.is_passed" class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <span
                    :class="[
                        'inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider mt-4 border',
                        submission.is_passed
                            ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                            : 'bg-rose-500/20 text-rose-300 border-rose-500/30'
                    ]"
                >
                    {{ submission.is_passed ? 'TUNTAS (LULUS)' : 'BELUM TUNTAS (REMEDIAL)' }}
                </span>

                <h2 class="text-xl font-black text-white mt-2">{{ exam.title }}</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ exam.subject }} • KKM: {{ exam.passing_score }}</p>
            </div>

            <!-- Score Display -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-1 relative z-10">
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Skor Diperoleh</span>
                <span class="text-5xl font-black text-white tracking-tight">{{ submission.total_score }}</span>
                <span class="text-xs text-slate-400 block mt-1">Skala Nilai 0 - 100</span>
            </div>

            <!-- Detailed Breakdown -->
            <div class="grid grid-cols-2 gap-3 text-left text-xs relative z-10">
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                    <span class="text-[10px] uppercase text-slate-400 font-bold block">Soal Dijawab</span>
                    <span class="font-bold text-white text-sm mt-0.5 block">
                        {{ submission.answered_count }} / {{ submission.total_questions }} Soal
                    </span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                    <span class="text-[10px] uppercase text-slate-400 font-bold block">Anti-Cheat Monitor</span>
                    <span
                        :class="[
                            'font-bold text-xs mt-0.5 block',
                            submission.violations_count === 0 ? 'text-emerald-400' : 'text-rose-400'
                        ]"
                    >
                        {{ submission.violations_count }} Pelanggaran
                    </span>
                </div>
            </div>

            <!-- Student Meta & Timestamp -->
            <div class="text-[11px] text-slate-400 space-y-1 pt-2 border-t border-slate-800 relative z-10">
                <p><span class="font-semibold text-slate-300">{{ student.name }}</span> (NISN: {{ student.nisn }})</p>
                <p>Waktu Selesai: {{ submission.submitted_at || '-' }}</p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 space-y-2 relative z-10">
                <Link
                    :href="route('portal.cbt.index')"
                    class="w-full py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs uppercase tracking-wider block transition shadow-lg shadow-indigo-600/30"
                >
                    Kembali ke Daftar Ujian
                </Link>
                <Link
                    :href="route('portal.student')"
                    class="w-full py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs block transition"
                >
                    Menuju Dashboard Siswa
                </Link>
            </div>
        </div>
    </div>
</template>
