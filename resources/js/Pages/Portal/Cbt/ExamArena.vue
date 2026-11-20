<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    exam: Object,
    questions: Array,
    savedAnswers: Object,
    savedDoubts: Array,
    violationsCount: Number,
    student: Object,
});

// State Butir Soal
const currentIndex = ref(0);
const answers = ref({ ...(props.savedAnswers || {}) });
const doubts = ref([...(props.savedDoubts || [])]);
const isSyncing = ref(false);
const lastSavedTime = ref('');

// State Timer
const remainingSeconds = ref(props.exam.remaining_seconds || 3600);
let timerInterval = null;

// State Anti-Cheat
const isFullscreen = ref(false);
const violations = ref(props.violationsCount || 0);
const showViolationWarning = ref(false);
const violationMessage = ref('');
const isSubmitting = ref(false);
const showConfirmModal = ref(false);

const currentQuestion = computed(() => {
    return props.questions[currentIndex.value] || null;
});

const isCurrentDoubtful = computed(() => {
    if (!currentQuestion.value) return false;
    return doubts.value.includes(currentQuestion.value.id);
});

const currentAnswer = computed(() => {
    if (!currentQuestion.value) return '';
    return answers.value[currentQuestion.value.id] || '';
});

// Agregasi Status Jawaban
const answeredCount = computed(() => {
    return Object.keys(answers.value).filter((k) => answers.value[k] !== '' && answers.value[k] !== null).length;
});

const doubtfulCount = computed(() => {
    return doubts.value.length;
});

const unansweredCount = computed(() => {
    return Math.max(0, props.questions.length - answeredCount.value);
});

// Format Waktu Jam : Menit : Detik (Dibulatkan tanpa angka desimal)
const formattedTime = computed(() => {
    const totalSecs = Math.max(0, Math.floor(remainingSeconds.value));
    const hours = Math.floor(totalSecs / 3600);
    const minutes = Math.floor((totalSecs % 3600) / 60);
    const seconds = totalSecs % 60;

    const pad = (n) => String(n).padStart(2, '0');
    if (hours > 0) {
        return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
    }
    return `${pad(minutes)}:${pad(seconds)}`;
});

const formatQuestionText = (text) => {
    if (!text) return '';
    return String(text).replace(/https?:\/\/[^\/]+\/storage\//g, '/storage/');
};

// =========================================================================
// INTERAKSI BUTIR SOAL & AUTOSAVE
// =========================================================================

const isComplexQuestion = computed(() => {
    if (!currentQuestion.value) return false;
    const ans = currentQuestion.value.correct_answer || '';
    return ans.includes(',') || currentQuestion.value.question_type === 'multiple_choice_complex';
});

const isOptionSelected = (key) => {
    const val = currentAnswer.value;
    if (!val) return false;
    if (isComplexQuestion.value) {
        const parts = String(val).split(',').map(s => s.trim().toUpperCase());
        return parts.includes(key.toUpperCase());
    }
    return val === key;
};

const selectOption = (key) => {
    if (!currentQuestion.value) return;
    const qId = currentQuestion.value.id;

    if (isComplexQuestion.value) {
        let currentArr = answers.value[qId]
            ? String(answers.value[qId]).split(',').map(s => s.trim().toUpperCase()).filter(Boolean)
            : [];
        const index = currentArr.indexOf(key.toUpperCase());
        if (index > -1) {
            currentArr.splice(index, 1);
        } else {
            currentArr.push(key.toUpperCase());
        }
        currentArr.sort();
        const finalVal = currentArr.join(',');
        answers.value[qId] = finalVal;
        triggerAutosave(qId, finalVal);
    } else {
        answers.value[qId] = key;
        triggerAutosave(qId, key);
    }
};

const handleEssayInput = (e) => {
    if (!currentQuestion.value) return;
    const qId = currentQuestion.value.id;
    const val = e.target.value;
    answers.value[qId] = val;
    triggerAutosave(qId, val);
};

// Helpers untuk Soal Menjodohkan (Matching)
const getMatchingPairsList = (q) => {
    if (!q || !q.correct_answer) return [{ key: '1' }, { key: '2' }, { key: '3' }];
    const pairs = String(q.correct_answer).split(',').map(s => s.trim());
    const list = [];
    pairs.forEach(p => {
        const parts = p.split(/[\-:\>]/);
        if (parts[0]) {
            list.push({ key: parts[0].trim() });
        }
    });
    return list.length > 0 ? list : [{ key: '1' }, { key: '2' }, { key: '3' }];
};

const getMatchingTargetChoices = (q) => {
    return ['A', 'B', 'C', 'D', 'E'];
};

const getSelectedMatchingChoice = (qId, pairKey) => {
    const raw = answers.value[qId] || '';
    const parts = String(raw).split(',').map(s => s.trim());
    for (const p of parts) {
        const [k, v] = p.split(/[\-:\>]/).map(s => s?.trim());
        if (k === String(pairKey)) {
            return v || '';
        }
    }
    return '';
};

const updateMatchingPair = (qId, pairKey, choiceVal) => {
    const raw = answers.value[qId] || '';
    const map = {};
    String(raw).split(',').map(s => s.trim()).forEach(p => {
        const [k, v] = p.split(/[\-:\>]/).map(s => s?.trim());
        if (k && v) map[k] = v;
    });

    if (choiceVal) {
        map[pairKey] = choiceVal.toUpperCase();
    } else {
        delete map[pairKey];
    }

    const result = Object.entries(map)
        .sort((a, b) => a[0].localeCompare(b[0], undefined, { numeric: true }))
        .map(([k, v]) => `${k}-${v}`)
        .join(', ');

    answers.value[qId] = result;
    triggerAutosave(qId, result);
};

const toggleDoubt = () => {
    if (!currentQuestion.value) return;
    const qId = currentQuestion.value.id;
    const idx = doubts.value.indexOf(qId);
    if (idx > -1) {
        doubts.value.splice(idx, 1);
    } else {
        doubts.value.push(qId);
    }
    triggerAutosave(qId, answers.value[qId] || '');
};

const goToQuestion = (idx) => {
    if (idx >= 0 && idx < props.questions.length) {
        currentIndex.value = idx;
    }
};

const triggerAutosave = async (questionId, answer) => {
    isSyncing.value = true;
    try {
        // Simpan cadangan di LocalStorage perangkat
        localStorage.setItem(`cbt_ans_${props.exam.id}`, JSON.stringify(answers.value));
        localStorage.setItem(`cbt_doubts_${props.exam.id}`, JSON.stringify(doubts.value));

        // Kirim ke endpoint autosave server
        const response = await fetch(route('portal.cbt.autosave', props.exam.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                question_id: questionId,
                answer: answer,
                doubts: doubts.value,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            lastSavedTime.value = data.saved_at || new Date().toLocaleTimeString();
        }
    } catch (e) {
        // fallback silent
    } finally {
        isSyncing.value = false;
    }
};

// =========================================================================
// ANTI-CHEAT SECURITY SUITE (FULLSCREEN, TAB SWITCH, COPY-PASTE BLOCK)
// =========================================================================

const requestFullscreenMode = () => {
    const elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen().catch(() => {});
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!(document.fullscreenElement || document.webkitFullscreenElement);
    if (!isFullscreen.value && !isSubmitting.value) {
        recordCheatingViolation('Terdeteksi keluar dari mode layar penuh (Fullscreen)!');
    }
};

const handleVisibilityChange = () => {
    if (document.hidden && !isSubmitting.value) {
        recordCheatingViolation('Terdeteksi berpindah tab atau meminimalkan jendela ujian!');
    }
};

const handleWindowBlur = () => {
    if (!isSubmitting.value) {
        recordCheatingViolation('Terdeteksi beralih fokus ke aplikasi lain di luar browser ujian!');
    }
};

const recordCheatingViolation = async (reason) => {
    violations.value++;
    violationMessage.value = reason;
    showViolationWarning.value = true;

    try {
        const response = await fetch(route('portal.cbt.violation', props.exam.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        if (response.ok) {
            const data = await response.json();
            violations.value = data.violations_count;
        }
    } catch (e) {
        // silent
    }

    // Jika melebihi batas 3 kali, otomatis paksa submit ujian
    if (violations.value >= 3) {
        alert('PERINGATAN KERAS: Batas toleransi kecurangan (3 kali) telah habis! Ujian Anda otomatis dikunci dan dikirimkan ke server.');
        submitFinalExam();
    }
};

// Blokir Keyboard Shortcut Copy/Paste/Inspect & Klik Kanan
const preventKeyShortcuts = (e) => {
    // Blokir F12, Ctrl+C, Ctrl+V, Ctrl+U, Ctrl+S, Ctrl+P, Ctrl+Shift+I
    if (
        e.key === 'F12' ||
        (e.ctrlKey && ['c', 'v', 'u', 's', 'p', 'a'].includes(e.key.toLowerCase())) ||
        (e.ctrlKey && e.shiftKey && ['i', 'j', 'c'].includes(e.key.toLowerCase()))
    ) {
        e.preventDefault();
        return false;
    }
};

const preventContextMenu = (e) => {
    e.preventDefault();
    return false;
};

// =========================================================================
// SUBMIT UJIAN
// =========================================================================

const submitFinalExam = () => {
    isSubmitting.value = true;
    showConfirmModal.value = false;

    // Bersihkan cadangan localStorage
    localStorage.removeItem(`cbt_ans_${props.exam.id}`);
    localStorage.removeItem(`cbt_doubts_${props.exam.id}`);

    router.post(route('portal.cbt.finish', props.exam.id), {
        answers: answers.value,
    });
};

// Lifecycle
onMounted(() => {
    // Jalankan timer
    timerInterval = setInterval(() => {
        if (remainingSeconds.value > 0) {
            remainingSeconds.value--;
        } else {
            clearInterval(timerInterval);
            submitFinalExam();
        }
    }, 1000);

    // Pasang listener Anti-Cheat
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('blur', handleWindowBlur);
    document.addEventListener('keydown', preventKeyShortcuts);
    document.addEventListener('contextmenu', preventContextMenu);

    // Minta fullscreen di awal
    setTimeout(() => {
        requestFullscreenMode();
    }, 500);
});

onBeforeUnmount(() => {
    if (timerInterval) clearInterval(timerInterval);
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    window.removeEventListener('blur', handleWindowBlur);
    document.removeEventListener('keydown', preventKeyShortcuts);
    document.removeEventListener('contextmenu', preventContextMenu);
});
</script>

<template>
    <Head :title="`Ujian: ${exam.title}`" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans select-none overflow-x-hidden">
        <!-- ===== ARENA TOP BAR ===== -->
        <header class="sticky top-0 z-40 bg-slate-900 border-b border-slate-800 px-4 py-2.5 flex items-center justify-between shadow-lg">
            <!-- Left Info -->
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center font-black text-white text-xs">
                    CBT
                </div>
                <div>
                    <h1 class="text-xs sm:text-sm font-black text-white tracking-wide truncate max-w-xs sm:max-w-md">
                        {{ exam.title }}
                    </h1>
                    <p class="text-[10px] text-slate-400 font-medium">
                        {{ student.name }} • {{ student.classroom }} (NISN: {{ student.nisn }})
                    </p>
                </div>
            </div>

            <!-- Center Countdown Timer -->
            <div class="flex items-center gap-3">
                <div
                    :class="[
                        'flex items-center gap-2 px-3 sm:px-4 py-1.5 rounded-2xl border font-mono font-black text-xs sm:text-sm transition-all',
                        remainingSeconds < 300
                            ? 'bg-rose-500/20 text-rose-400 border-rose-500/40 animate-pulse'
                            : 'bg-slate-950 text-amber-300 border-slate-700'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ formattedTime }}</span>
                </div>

                <div v-if="isSyncing" class="hidden md:flex items-center gap-1.5 text-[10px] text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-ping"></span>
                    <span>Menyimpan...</span>
                </div>
            </div>

            <!-- Right: Selesaikan Ujian -->
            <div class="flex items-center gap-2">
                <button
                    @click="showConfirmModal = true"
                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/30 transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Selesai Ujian</span>
                </button>
            </div>
        </header>

        <!-- ===== ARENA BODY (SPLIT VIEW) ===== -->
        <div class="flex-1 max-w-7xl w-full mx-auto p-3 sm:p-5 grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- ===== LEFT COLUMN: SOAL AKTIF (8 COLS) ===== -->
            <div class="lg:col-span-8 flex flex-col justify-between rounded-3xl bg-slate-900 border border-slate-800 p-5 sm:p-6 shadow-xl relative min-h-[480px]">
                <div v-if="currentQuestion" class="space-y-5">
                    <!-- Question Header & Weight -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-xl bg-indigo-600 text-white font-black text-xs">
                                Soal No. {{ currentIndex + 1 }}
                            </span>
                            <span v-if="isComplexQuestion" class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                ☑️ Pilihan Ganda Kompleks (Bisa pilih &gt; 1)
                            </span>
                            <span class="text-xs text-slate-400 font-medium">
                                dari {{ questions.length }} soal
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-800 text-amber-400 border border-slate-700">
                                Bobot: {{ currentQuestion.score_weight }} Poin
                            </span>
                        </div>
                    </div>

                    <!-- Question Text -->
                    <div class="text-sm sm:text-base text-slate-100 font-medium leading-relaxed" v-html="formatQuestionText(currentQuestion.question_text)"></div>

                    <!-- Options for Multiple Choice -->
                    <div v-if="currentQuestion.question_type === 'multiple_choice' || currentQuestion.question_type === 'multiple_choice_complex'" class="space-y-3 pt-2">
                        <div
                            v-for="opt in currentQuestion.options"
                            :key="opt.key"
                            @click="selectOption(opt.key)"
                            :class="[
                                'flex items-center gap-3.5 p-3.5 rounded-2xl border cursor-pointer transition-all duration-150',
                                isOptionSelected(opt.key)
                                    ? 'bg-indigo-600/20 border-indigo-500 text-white shadow-md shadow-indigo-600/10 ring-1 ring-indigo-500/50'
                                    : 'bg-slate-950/60 border-slate-800/80 text-slate-300 hover:bg-slate-800/60 hover:border-slate-700'
                            ]"
                        >
                            <div
                                :class="[
                                    'w-7 h-7 rounded-xl flex items-center justify-center font-mono font-bold text-xs shrink-0 transition',
                                    isOptionSelected(opt.key)
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-slate-800 text-slate-400'
                                ]"
                            >
                                <span v-if="isComplexQuestion && isOptionSelected(opt.key)">✓</span>
                                <span v-else>{{ opt.key }}</span>
                            </div>
                            <span class="text-xs sm:text-sm font-medium leading-snug" v-html="opt.text"></span>
                        </div>
                    </div>

                    <!-- Essay Text Area -->
                    <div v-else-if="currentQuestion.question_type === 'essay'" class="pt-2">
                        <label class="block text-xs font-semibold text-slate-400 mb-2">Tuliskan jawaban lengkap Anda di bawah:</label>
                        <textarea
                            :value="currentAnswer"
                            @input="handleEssayInput"
                            rows="6"
                            placeholder="Ketik uraian jawaban di sini..."
                            class="w-full px-4 py-3 rounded-2xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-sans leading-relaxed"
                        ></textarea>
                    </div>

                    <!-- Short Answer (Jawaban Singkat) -->
                    <div v-else-if="currentQuestion.question_type === 'short_answer'" class="pt-2">
                        <label class="block text-xs font-semibold text-slate-400 mb-2">Tuliskan kata, angka, atau istilah jawaban singkat Anda di bawah:</label>
                        <input
                            type="text"
                            :value="currentAnswer"
                            @input="handleEssayInput"
                            placeholder="Ketik jawaban singkat di sini..."
                            class="w-full max-w-lg px-4 py-3 rounded-2xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium"
                        />
                    </div>

                    <!-- Matching (Menjodohkan) -->
                    <div v-else-if="currentQuestion.question_type === 'matching'" class="pt-2 space-y-3">
                        <label class="block text-xs font-semibold text-slate-400 mb-1">
                            Pilih pasangan yang sesuai untuk setiap butir pernyataan:
                        </label>
                        <div class="space-y-2.5">
                            <div
                                v-for="pair in getMatchingPairsList(currentQuestion)"
                                :key="pair.key"
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-xl bg-indigo-600/30 text-indigo-400 border border-indigo-500/40 font-mono font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ pair.key }}
                                    </span>
                                    <span class="text-xs sm:text-sm text-slate-200 font-medium">
                                        Pernyataan No. {{ pair.key }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-400">Pasangkan ke:</span>
                                    <select
                                        :value="getSelectedMatchingChoice(currentQuestion.id, pair.key)"
                                        @change="e => updateMatchingPair(currentQuestion.id, pair.key, e.target.value)"
                                        class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-bold focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                    >
                                        <option value="">-- Pilih Opsi --</option>
                                        <option v-for="c in getMatchingTargetChoices(currentQuestion)" :key="c" :value="c">
                                            Opsi {{ c }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- True / False -->
                    <div v-else-if="currentQuestion.question_type === 'true_false'" class="grid grid-cols-2 gap-4 pt-2">
                        <button
                            type="button"
                            @click="selectOption('TRUE')"
                            :class="[
                                'py-4 rounded-2xl font-bold text-sm border transition',
                                currentAnswer === 'TRUE'
                                    ? 'bg-emerald-600 text-white border-emerald-500 shadow-lg shadow-emerald-600/30'
                                    : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800'
                            ]"
                        >
                            BENAR (TRUE)
                        </button>
                        <button
                            type="button"
                            @click="selectOption('FALSE')"
                            :class="[
                                'py-4 rounded-2xl font-bold text-sm border transition',
                                currentAnswer === 'FALSE'
                                    ? 'bg-rose-600 text-white border-rose-500 shadow-lg shadow-rose-600/30'
                                    : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800'
                            ]"
                        >
                            SALAH (FALSE)
                        </button>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="mt-8 pt-4 border-t border-slate-800 flex items-center justify-between gap-2">
                    <button
                        :disabled="currentIndex === 0"
                        @click="goToQuestion(currentIndex - 1)"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-40 text-slate-300 text-xs font-bold transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Sebelumnya</span>
                    </button>

                    <button
                        @click="toggleDoubt"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 border',
                            isCurrentDoubtful
                                ? 'bg-amber-500 text-slate-950 border-amber-400 font-black shadow-md shadow-amber-500/20'
                                : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-amber-400 hover:border-amber-400/50'
                        ]"
                    >
                        <input
                            type="checkbox"
                            :checked="isCurrentDoubtful"
                            class="rounded text-amber-500 focus:ring-0 cursor-pointer pointer-events-none"
                        />
                        <span>Ragu-Ragu</span>
                    </button>

                    <button
                        :disabled="currentIndex === questions.length - 1"
                        @click="goToQuestion(currentIndex + 1)"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white text-xs font-bold transition flex items-center gap-1.5"
                    >
                        <span>Selanjutnya</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- ===== RIGHT COLUMN: LEMBAR NOMOR SOAL & PENGAWASAN (4 COLS) ===== -->
            <div class="lg:col-span-4 space-y-4">
                <!-- Lembar Nomor Soal Grid -->
                <div class="rounded-3xl bg-slate-900 border border-slate-800 p-5 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-300">
                            Navigasi Nomor Soal
                        </h3>
                        <span class="text-[11px] font-mono text-slate-400">
                            {{ answeredCount }} / {{ questions.length }} Dijawab
                        </span>
                    </div>

                    <!-- Nomor Grid -->
                    <div class="grid grid-cols-5 gap-2 max-h-[300px] overflow-y-auto pr-1">
                        <button
                            v-for="(q, idx) in questions"
                            :key="q.id"
                            @click="goToQuestion(idx)"
                            :class="[
                                'h-10 rounded-xl font-mono text-xs font-bold transition-all relative flex items-center justify-center border',
                                idx === currentIndex
                                    ? 'ring-2 ring-indigo-400 border-indigo-400 scale-105 z-10'
                                    : 'border-transparent',
                                doubts.includes(q.id)
                                    ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-sm shadow-amber-500/20'
                                    : (answers[q.id] && answers[q.id] !== '')
                                        ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm shadow-emerald-600/20'
                                        : 'bg-slate-800 text-slate-400 hover:bg-slate-700'
                            ]"
                        >
                            {{ idx + 1 }}
                        </button>
                    </div>

                    <!-- Legend Indikator -->
                    <div class="pt-3 border-t border-slate-800 space-y-1.5 text-[11px] text-slate-400">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded bg-emerald-600 inline-block"></span>
                                <span>Sudah Dijawab:</span>
                            </span>
                            <span class="font-bold text-white">{{ answeredCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded bg-amber-500 inline-block"></span>
                                <span>Ragu-Ragu:</span>
                            </span>
                            <span class="font-bold text-white">{{ doubtfulCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded bg-slate-800 inline-block border border-slate-700"></span>
                                <span>Belum Dijawab:</span>
                            </span>
                            <span class="font-bold text-white">{{ unansweredCount }}</span>
                        </div>
                    </div>
                </div>

                <!-- Anti-Cheat Status Monitor -->
                <div class="rounded-3xl bg-slate-900 border border-slate-800 p-4 shadow-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Anti-Cheat AI Proctor</span>
                        </span>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-[10px] font-mono font-bold',
                                violations === 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'
                            ]"
                        >
                            {{ violations }} / 3 Pelanggaran
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-normal">
                        Dilarang membuka tab baru, keluar dari layar penuh, atau menggunakan aplikasi lain. Seluruh interaksi dicatat oleh sistem.
                    </p>
                </div>
            </div>
        </div>

        <!-- ===== MODAL PERINGATAN KECURANGAN (OVERLAY ANTI-CHEAT) ===== -->
        <div v-if="showViolationWarning" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-rose-950/90 backdrop-blur-md">
            <div class="bg-slate-900 border-2 border-rose-500 rounded-3xl max-w-md w-full p-6 text-center space-y-4 shadow-2xl">
                <div class="w-16 h-16 rounded-2xl bg-rose-500/20 text-rose-500 mx-auto flex items-center justify-center animate-bounce">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <div class="space-y-1.5">
                    <h3 class="text-lg font-black text-white">PERINGATAN PELANGGARAN!</h3>
                    <p class="text-xs text-rose-400 font-bold uppercase tracking-wider">
                        Pelanggaran Ke-{{ violations }} dari Maksimal 3
                    </p>
                    <p class="text-xs text-slate-300 mt-2">
                        {{ violationMessage }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Jika Anda terdeteksi melakukan pelanggaran hingga 3 kali, sesi ujian akan langsung dihentikan dan dikunci otomatis.
                    </p>
                </div>

                <div class="pt-3">
                    <button
                        @click="showViolationWarning = false; requestFullscreenMode()"
                        class="w-full py-3 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-rose-600/30 transition"
                    >
                        Saya Mengerti, Kembali ke Ujian
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== MODAL KONFIRMASI SELESAIKAN UJIAN ===== -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-white">Konfirmasi Selesai Ujian</h3>
                    </div>
                    <button @click="showConfirmModal = false" class="text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-3 bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs">
                    <p class="text-slate-300 leading-relaxed">
                        Pastikan Anda telah memeriksa kembali seluruh jawaban sebelum mengirimkan lembar ujian:
                    </p>
                    <div class="grid grid-cols-3 gap-2 text-center pt-1">
                        <div class="p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                            <span class="text-lg font-black text-emerald-400 block">{{ answeredCount }}</span>
                            <span class="text-[10px] text-slate-400">Terjawab</span>
                        </div>
                        <div class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/20">
                            <span class="text-lg font-black text-amber-400 block">{{ doubtfulCount }}</span>
                            <span class="text-[10px] text-slate-400">Ragu-ragu</span>
                        </div>
                        <div class="p-2 rounded-xl bg-slate-800 border border-slate-700">
                            <span class="text-lg font-black text-slate-300 block">{{ unansweredCount }}</span>
                            <span class="text-[10px] text-slate-400">Kosong</span>
                        </div>
                    </div>

                    <p v-if="doubtfulCount > 0 || unansweredCount > 0" class="text-[11px] text-amber-400 mt-2 font-medium">
                        ⚠️ Perhatian: Anda masih memiliki {{ doubtfulCount }} soal ragu-ragu dan {{ unansweredCount }} soal belum dijawab!
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showConfirmModal = false"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700"
                    >
                        Kembali Periksa
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="submitFinalExam"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 transition disabled:opacity-50"
                    >
                        {{ isSubmitting ? 'Mengirimkan...' : 'Ya, Selesaikan Sekarang' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
