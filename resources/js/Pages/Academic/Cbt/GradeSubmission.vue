<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    submission: Object,
    questions: Array,
});

const menus = [
    { label: 'Bank Soal & Ujian CBT', route: 'academic.cbt.index', icon: 'desktop' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Dashboard Operasional', route: 'dashboard', icon: 'home' },
];

// Inisialisasi skor essay dari submission sebelumnya jika ada
const initialEssayScores = {};
props.questions.forEach((q) => {
    if (q.question_type === 'essay') {
        initialEssayScores[q.id] = (props.submission.essay_scores && props.submission.essay_scores[q.id]) ?? 0;
    }
});

const form = useForm({
    essay_scores: initialEssayScores,
    teacher_notes: props.submission.teacher_notes || '',
});

const submitGrading = () => {
    form.post(route('academic.cbt.submissions.grade-essay', props.submission.id));
};
</script>

<template>
    <Head :title="`Koreksi Ujian: ${submission.student_profile?.user?.name}`" />

    <AuthenticatedLayout :menus="menus" section-title="Manajemen CBT & Asesmen">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('academic.cbt.index')"
                        class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white">
                            Lembar Koreksi Ujian Siswa
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ submission.exam?.title }} • Siswa: <span class="font-bold text-slate-800 dark:text-slate-200">{{ submission.student_profile?.user?.name }}</span> (NISN: {{ submission.student_profile?.nisn }})
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="p-6 max-w-5xl mx-auto space-y-6 text-xs">
            <!-- ===== METRIC CARD ===== -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Status Lembar</span>
                    <span class="text-base font-black text-slate-900 dark:text-white uppercase mt-1 block">
                        {{ submission.status }}
                    </span>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Skor Saat Ini</span>
                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5 block">
                        {{ submission.total_score }}
                    </span>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Status Ketuntasan</span>
                    <span
                        :class="[
                            'inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase mt-1.5',
                            submission.is_passed ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                        ]"
                    >
                        {{ submission.is_passed ? 'TUNTAS (KKM: ' + (submission.exam?.bank?.passing_score || 75) + ')' : 'REMEDIAL' }}
                    </span>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Pelanggaran Anti-Cheat</span>
                    <span
                        :class="[
                            'text-base font-black mt-1 block',
                            (submission.violations_count || 0) === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                        ]"
                    >
                        {{ submission.violations_count || 0 }} Kali
                    </span>
                </div>
            </div>

            <!-- ===== QUESTION GRADING FORM ===== -->
            <form @submit.prevent="submitGrading" class="space-y-5">
                <div class="space-y-4">
                    <div
                        v-for="(q, idx) in questions"
                        :key="q.id"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 shadow-sm space-y-3"
                    >
                        <!-- Question Header -->
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700/50">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-bold text-xs">
                                    Soal #{{ idx + 1 }}
                                </span>
                                <span class="text-[11px] font-bold uppercase text-slate-400">
                                    Tipe: {{ q.question_type }}
                                </span>
                            </div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">
                                Bobot Maks: {{ q.score_weight }} Poin
                            </span>
                        </div>

                        <!-- Question Text -->
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 whitespace-pre-line">
                            {{ q.question_text }}
                        </p>

                        <!-- If Multiple Choice -->
                        <div v-if="q.question_type === 'multiple_choice'" class="space-y-2 pt-1">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500 dark:text-slate-400">Jawaban Siswa:</span>
                                    <span class="font-mono font-bold text-slate-900 dark:text-white">
                                        {{ submission.answers && submission.answers[q.id] ? submission.answers[q.id] : '(Tidak Dijawab)' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500 dark:text-slate-400">Kunci Jawaban Benar:</span>
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ q.correct_answer }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- If Essay -->
                        <div v-else-if="q.question_type === 'essay'" class="space-y-3 pt-1">
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Jawaban Siswa:</span>
                                <p class="text-xs text-slate-800 dark:text-slate-200 whitespace-pre-line leading-relaxed">
                                    {{ submission.answers && submission.answers[q.id] ? submission.answers[q.id] : '(Siswa tidak mengisi jawaban)' }}
                                </p>
                            </div>

                            <!-- Input Nilai Essay -->
                            <div class="flex items-center gap-4 bg-indigo-50/50 dark:bg-indigo-950/20 p-3 rounded-xl border border-indigo-200 dark:border-indigo-800/40">
                                <label class="font-bold text-slate-700 dark:text-slate-200 whitespace-nowrap">
                                    Beri Nilai Essay (0 s/d {{ q.score_weight }}):
                                </label>
                                <input
                                    v-model.number="form.essay_scores[q.id]"
                                    type="number"
                                    min="0"
                                    :max="q.score_weight"
                                    step="0.5"
                                    class="w-24 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white font-bold text-center focus:ring-2 focus:ring-indigo-500"
                                />
                                <span class="text-[11px] text-slate-400">Poin diperoleh</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teacher Notes -->
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 shadow-sm space-y-2">
                    <label class="block font-bold text-slate-800 dark:text-white">
                        Catatan & Evaluasi Guru Penguji:
                    </label>
                    <textarea
                        v-model="form.teacher_notes"
                        rows="3"
                        placeholder="Tuliskan evaluasi pemahaman siswa atau arahan remedial..."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    ></textarea>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end gap-3 pt-2">
                    <Link
                        :href="route('academic.cbt.index')"
                        class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan & Finalisasi Penilaian' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
