<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    exams: Array,
    student: Object,
});

const isTokenModalOpen = ref(false);
const selectedExam = ref(null);

const tokenForm = useForm({
    token: '',
});

const openTokenModal = (exam) => {
    selectedExam.value = exam;
    tokenForm.token = '';
    tokenForm.clearErrors();
    isTokenModalOpen.value = true;
};

const submitToken = () => {
    if (!selectedExam.value) return;

    tokenForm.post(route('portal.cbt.start', selectedExam.value.id), {
        onSuccess: () => {
            isTokenModalOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="CBT Ujian Mandiri Siswa" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
        <!-- ===== TOP HEADER ===== -->
        <header class="sticky top-0 z-30 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link
                    :href="route('portal.student')"
                    class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                    title="Kembali ke Dashboard"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-sm font-black text-white flex items-center gap-2">
                        <span>CBT EXAM PORTAL</span>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">ONLINE</span>
                    </h1>
                    <p class="text-[11px] text-slate-400">
                        {{ student.name }} • NISN: {{ student.nisn }} • Kelas: {{ student.classroom }}
                    </p>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="text-xs text-slate-400 font-mono">Server Online</span>
            </div>
        </header>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="flex-1 max-w-4xl w-full mx-auto p-4 sm:p-6 space-y-6">
            <!-- Hero Card -->
            <div class="rounded-3xl bg-gradient-to-tr from-indigo-950 via-slate-900 to-slate-900 border border-indigo-500/30 p-6 shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500 text-white">
                        Computer Based Test (CBT)
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-white mt-3">
                        Ujian & Asesmen Terpadu
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1.5 max-w-xl">
                        Sistem ujian digital mandiri dengan enkripsi butir soal, kalkulasi skor otomatis, dan pengawasan anti-cheat berstandar nasional.
                    </p>
                </div>
                <!-- Background Glow -->
                <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
            </div>

            <!-- List of Exams -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-1">
                    Jadwal Ujian Tersedia
                </h3>

                <div v-if="exams.length === 0" class="text-center py-12 rounded-2xl bg-slate-900/40 border border-slate-800 p-6 text-slate-400 text-xs">
                    Belum ada jadwal ujian CBT aktif untuk kelas Anda saat ini.
                </div>

                <div
                    v-for="exam in exams"
                    :key="exam.id"
                    class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-5 hover:border-slate-700 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-indigo-400 border border-indigo-500/20">
                                {{ exam.subject }}
                            </span>
                            <span
                                v-if="exam.student_status === 'completed'"
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
                            >
                                Selesai
                            </span>
                            <span
                                v-else-if="exam.student_status === 'in_progress'"
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse"
                            >
                                Sedang Dikerjakan
                            </span>
                            <span
                                v-else
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-400"
                            >
                                Siap Dikerjakan
                            </span>
                        </div>

                        <h4 class="text-base font-bold text-white">{{ exam.title }}</h4>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ exam.duration_minutes }} Menit
                            </span>
                            <span>•</span>
                            <span>{{ exam.total_questions }} Butir Soal</span>
                            <span>•</span>
                            <span>KKM: {{ exam.passing_score }}</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex items-center sm:self-center">
                        <!-- Selesai -> Lihat Hasil -->
                        <Link
                            v-if="exam.student_status === 'completed'"
                            :href="route('portal.cbt.result', exam.id)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 font-bold text-xs flex items-center gap-2 border border-slate-700 transition"
                        >
                            <span>Lihat Hasil Nilai ({{ exam.score }})</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>

                        <!-- In Progress -> Lanjutkan -->
                        <Link
                            v-else-if="exam.student_status === 'in_progress'"
                            :href="route('portal.cbt.arena', exam.id)"
                            class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition"
                        >
                            <span>Lanjutkan Ujian</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>

                        <!-- Ready -> Masukkan Token -->
                        <button
                            v-else
                            @click="openTokenModal(exam)"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs flex items-center gap-2 shadow-lg shadow-indigo-600/30 transition"
                        >
                            <span>Mulai Ujian</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- ===== MODAL TOKEN UJIAN ===== -->
        <div v-if="isTokenModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-white">Masukkan Token Ujian</h3>
                    </div>
                    <button @click="isTokenModalOpen = false" class="text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="text-xs text-slate-300">
                    <p class="font-bold text-white mb-1">{{ selectedExam?.title }}</p>
                    <p class="text-slate-400">Mintalah 6 digit token aktivasi kepada pengawas ujian di ruangan kelas Anda.</p>
                </div>

                <form @submit.prevent="submitToken" class="space-y-4">
                    <div>
                        <input
                            v-model="tokenForm.token"
                            required
                            maxlength="10"
                            placeholder="Contoh: AB12CD"
                            class="w-full text-center tracking-widest text-lg font-mono font-black uppercase py-3 rounded-2xl bg-slate-950 border border-slate-700 text-amber-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            autofocus
                        />
                        <span v-if="tokenForm.errors.token" class="text-rose-400 text-xs mt-1 block">
                            {{ tokenForm.errors.token }}
                        </span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="isTokenModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="tokenForm.processing || !tokenForm.token"
                            class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold disabled:opacity-50 transition"
                        >
                            {{ tokenForm.processing ? 'Memverifikasi...' : 'Mulai Sekarang' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
