<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    exams: Array,
    banks: Array,
    submissions: Array,
    subjects: Array,
    classrooms: Array,
    students: Array,
    stats: Object,
});

// Tab Navigasi Aktif
const activeTab = ref('exams'); // 'exams' | 'banks' | 'submissions'

// Filter state
const selectedBankFilter = ref('all');
const selectedExamFilter = ref('all');

const formatQuestionText = (text) => {
    if (!text) return '';
    return String(text).replace(/https?:\/\/[^\/]+\/storage\//g, '/storage/');
};

// Modals
const isExamModalOpen = ref(false);
const isEditingExam = ref(false);
const editingExam = ref(null);

const isBankModalOpen = ref(false);
const isEditingBank = ref(false);
const editingBank = ref(null);

const isQuestionModalOpen = ref(false);
const selectedBankForQuestion = ref(null);
const isEditingQuestion = ref(false);
const editingQuestion = ref(null);

const isSyncModalOpen = ref(false);
const syncExam = ref(null);
const syncGradeType = ref('quiz_score');

const isSimulatorModalOpen = ref(false);
const simulatorExam = ref(null);
const simulatorStudentId = ref('');
const simulatorAnswers = ref({});

// Modal Impor Soal CSV
const isImportModalOpen = ref(false);
const selectedBankForImport = ref(null);
const importForm = useForm({
    file: null,
});

// Form Bank Soal
const bankForm = useForm({
    title: '',
    subject_id: '',
    passing_score: 75,
    description: '',
});

// Form Butir Soal
const questionForm = useForm({
    question_text: '',
    question_type: 'multiple_choice',
    options: [
        { key: 'A', text: '' },
        { key: 'B', text: '' },
        { key: 'C', text: '' },
        { key: 'D', text: '' },
    ],
    correct_answer: 'A',
    explanation: '',
    score_weight: 10,
});

// Form Sesi Ujian
const examForm = useForm({
    cbt_bank_id: '',
    classroom_id: '',
    title: '',
    duration_minutes: 60,
    start_time: '',
    end_time: '',
    randomize_questions: true,
    show_results: true,
    status: 'published',
});

// Helpers
const openCreateExamModal = () => {
    isEditingExam.value = false;
    editingExam.value = null;
    examForm.reset();
    if (props.banks.length > 0) {
        examForm.cbt_bank_id = props.banks[0].id;
    }
    examForm.duration_minutes = 60;
    examForm.status = 'published';
    isExamModalOpen.value = true;
};

const openEditExamModal = (exam) => {
    isEditingExam.value = true;
    editingExam.value = exam;
    examForm.cbt_bank_id = exam.cbt_bank_id;
    examForm.classroom_id = exam.classroom_id || '';
    examForm.title = exam.title;
    examForm.duration_minutes = exam.duration_minutes;
    examForm.start_time = exam.start_time ? exam.start_time.slice(0, 16) : '';
    examForm.end_time = exam.end_time ? exam.end_time.slice(0, 16) : '';
    examForm.randomize_questions = exam.randomize_questions;
    examForm.show_results = exam.show_results;
    examForm.status = exam.status;
    isExamModalOpen.value = true;
};

const submitExam = () => {
    if (isEditingExam.value && editingExam.value) {
        const id = editingExam.value.hashid || editingExam.value.id;
        examForm.put(route('academic.cbt.exams.update', id), {
            onSuccess: () => isExamModalOpen.value = false,
        });
    } else {
        examForm.post(route('academic.cbt.exams.store'), {
            onSuccess: () => isExamModalOpen.value = false,
        });
    }
};

const regenerateToken = (exam) => {
    const id = exam.hashid || exam.id;
    router.post(route('academic.cbt.exams.regenerate-token', id));
};

const deleteExam = (exam) => {
    if (confirm(`Yakin ingin menghapus sesi ujian '${exam.title}'?`)) {
        const id = exam.hashid || exam.id;
        router.delete(route('academic.cbt.exams.destroy', id));
    }
};

// Bank Soal Helpers
const openCreateBankModal = () => {
    isEditingBank.value = false;
    editingBank.value = null;
    bankForm.reset();
    if (props.subjects.length > 0) {
        bankForm.subject_id = props.subjects[0].id;
    }
    bankForm.passing_score = 75;
    isBankModalOpen.value = true;
};

const openEditBankModal = (bank) => {
    isEditingBank.value = true;
    editingBank.value = bank;
    bankForm.title = bank.title;
    bankForm.subject_id = bank.subject_id;
    bankForm.passing_score = bank.passing_score;
    bankForm.description = bank.description || '';
    isBankModalOpen.value = true;
};

const submitBank = () => {
    if (isEditingBank.value && editingBank.value) {
        const id = editingBank.value.hashid || editingBank.value.id;
        bankForm.put(route('academic.cbt.banks.update', id), {
            onSuccess: () => isBankModalOpen.value = false,
        });
    } else {
        bankForm.post(route('academic.cbt.banks.store'), {
            onSuccess: () => isBankModalOpen.value = false,
        });
    }
};

const deleteBank = (bank) => {
    if (confirm(`Yakin ingin menghapus bank soal '${bank.title}'? Seluruh butir soal di dalamnya akan terhapus.`)) {
        const id = bank.hashid || bank.id;
        router.delete(route('academic.cbt.banks.destroy', id));
    }
};

const openImportModal = (bank) => {
    selectedBankForImport.value = bank;
    importForm.reset();
    isImportModalOpen.value = true;
};

const submitImport = () => {
    if (!selectedBankForImport.value || !importForm.file) return;
    const bankId = selectedBankForImport.value.hashid || selectedBankForImport.value.id;
    importForm.post(route('academic.cbt.banks.import-questions', bankId), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
        },
    });
};

// Question Helpers
const openCreateQuestionModal = (bank) => {
    selectedBankForQuestion.value = bank;
    isEditingQuestion.value = false;
    editingQuestion.value = null;
    questionForm.reset();
    questionForm.question_type = 'multiple_choice';
    questionForm.options = [
        { key: 'A', text: '' },
        { key: 'B', text: '' },
        { key: 'C', text: '' },
        { key: 'D', text: '' },
    ];
    questionForm.correct_answer = 'A';
    questionForm.score_weight = 10;
    isQuestionModalOpen.value = true;
};

const submitQuestion = () => {
    if (!selectedBankForQuestion.value) return;
    const bankId = selectedBankForQuestion.value.hashid || selectedBankForQuestion.value.id;

    if (isEditingQuestion.value && editingQuestion.value) {
        const qId = editingQuestion.value.hashid || editingQuestion.value.id;
        questionForm.put(route('academic.cbt.questions.update', qId), {
            onSuccess: () => isQuestionModalOpen.value = false,
        });
    } else {
        questionForm.post(route('academic.cbt.questions.store', bankId), {
            onSuccess: () => isQuestionModalOpen.value = false,
        });
    }
};

const deleteQuestion = (q) => {
    if (confirm('Yakin ingin menghapus butir soal ini?')) {
        const id = q.hashid || q.id;
        router.delete(route('academic.cbt.questions.destroy', id));
    }
};

// Sync Grades
const openSyncModal = (exam) => {
    syncExam.value = exam;
    syncGradeType.value = 'quiz_score';
    isSyncModalOpen.value = true;
};

const submitSyncGrades = () => {
    if (!syncExam.value) return;
    const id = syncExam.value.hashid || syncExam.value.id;
    router.post(route('academic.cbt.exams.push-grades', id), {
        target_grade_type: syncGradeType.value,
    }, {
        onSuccess: () => isSyncModalOpen.value = false,
    });
};

// Simulator
const openSimulatorModal = (exam) => {
    simulatorExam.value = exam;
    simulatorStudentId.value = props.students.length > 0 ? props.students[0].id : '';
    simulatorAnswers.value = {};
    if (exam.bank && exam.bank.questions) {
        exam.bank.questions.forEach(q => {
            simulatorAnswers.value[q.id] = '';
        });
    }
    isSimulatorModalOpen.value = true;
};

const submitSimulator = () => {
    if (!simulatorExam.value || !simulatorStudentId.value) return;
    const id = simulatorExam.value.hashid || simulatorExam.value.id;

    router.post(route('academic.cbt.exams.submit-simulation', id), {
        student_profile_id: simulatorStudentId.value,
        answers: simulatorAnswers.value,
    }, {
        onSuccess: () => isSimulatorModalOpen.value = false,
    });
};

const filteredBanks = computed(() => {
    if (selectedBankFilter.value === 'all') return props.banks;
    return props.banks.filter(b => b.subject_id === Number(selectedBankFilter.value));
});

const filteredSubmissions = computed(() => {
    if (selectedExamFilter.value === 'all') return props.submissions;
    return props.submissions.filter(s => s.cbt_exam_id === Number(selectedExamFilter.value));
});

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert(`Token '${text}' disalin ke clipboard!`);
};
</script>

<template>
    <Head title="CBT Ujian Online & Bank Soal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">🖥️</span>
                        CBT Ujian Online & Bank Soal
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Sistem ujian berbasis komputer, pembuatan butir soal interaktif, token ujian dinamis, dan integrasi nilai ke E-Rapor.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="openCreateBankModal"
                        class="px-3.5 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 transition-colors shadow-xs"
                    >
                        + Bank Soal Baru
                    </button>
                    <button
                        @click="openCreateExamModal"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-1.5"
                    >
                        <span>+</span> Terbitkan Sesi Ujian
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-2">
                <span>✓</span> {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.errors?.error" class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-sm flex items-center gap-2">
                <span>⚠️</span> {{ $page.props.errors.error }}
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="text-slate-400 text-xs font-bold uppercase tracking-wider">Total Ujian</div>
                    <div class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_exams }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Sesi Ujian</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="text-emerald-600 text-xs font-bold uppercase tracking-wider">Ujian Aktif</div>
                    <div class="text-2xl font-black text-emerald-600 mt-1">{{ stats.active_exams }}</div>
                    <div class="text-[10px] text-emerald-600 mt-0.5">Siap Dikerjakan</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="text-indigo-600 text-xs font-bold uppercase tracking-wider">Bank Soal</div>
                    <div class="text-2xl font-black text-indigo-700 mt-1">{{ stats.total_banks }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Katalog Mapel</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="text-purple-600 text-xs font-bold uppercase tracking-wider">Butir Soal</div>
                    <div class="text-2xl font-black text-purple-700 mt-1">{{ stats.total_questions }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Pertanyaan CBT</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="text-blue-600 text-xs font-bold uppercase tracking-wider">Jawaban Masuk</div>
                    <div class="text-2xl font-black text-blue-700 mt-1">{{ stats.total_submissions }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Lembar Tersimpan</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="text-amber-600 text-xs font-bold uppercase tracking-wider">Kelulusan KKM</div>
                    <div class="text-2xl font-black text-amber-600 mt-1">{{ stats.pass_rate }}%</div>
                    <div class="text-[10px] text-amber-600 mt-0.5">Tuntas Belajar</div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-200">
                <button
                    @click="activeTab = 'exams'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'exams'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>📅</span> Sesi Ujian & Token ({{ exams.length }})
                </button>
                <button
                    @click="activeTab = 'banks'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'banks'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>📚</span> Bank Soal & Butir Soal ({{ banks.length }})
                </button>
                <button
                    @click="activeTab = 'submissions'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'submissions'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>📊</span> Hasil & Nilai Siswa ({{ submissions.length }})
                </button>
            </div>

            <!-- TAB 1: Sesi Ujian & Token -->
            <div v-if="activeTab === 'exams'" class="space-y-4">
                <div v-if="exams.length === 0" class="text-center py-12 bg-white rounded-2xl border border-slate-200">
                    <span class="text-4xl block">📝</span>
                    <h3 class="text-base font-bold text-slate-700 mt-2">Belum Ada Sesi Ujian</h3>
                    <p class="text-xs text-slate-400 mt-1">Buat sesi ujian baru dari bank soal yang tersedia untuk memulai CBT.</p>
                    <button @click="openCreateExamModal" class="mt-4 px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl">
                        + Buat Sesi Ujian
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="exam in exams"
                        :key="exam.id"
                        class="bg-white rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-shadow p-5 flex flex-col justify-between space-y-4"
                    >
                        <!-- Card Header -->
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ exam.bank?.subject?.name || 'Mata Pelajaran' }}
                                </span>
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                        exam.status === 'published' ? 'bg-emerald-100 text-emerald-800' :
                                        exam.status === 'ongoing' ? 'bg-blue-100 text-blue-800 animate-pulse' :
                                        exam.status === 'completed' ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-800'
                                    ]"
                                >
                                    {{ exam.status }}
                                </span>
                            </div>

                            <h4 class="font-bold text-slate-800 text-base mt-2 leading-snug">{{ exam.title }}</h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Sasaran: <strong>{{ exam.classroom?.name || 'Semua Kelas' }}</strong>
                            </p>
                        </div>

                        <!-- Token Box -->
                        <div class="bg-slate-900 text-white p-3 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">TOKEN UJIAN CBT</span>
                                <span class="text-xl font-mono font-black text-amber-400 tracking-widest">{{ exam.token }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button
                                    @click="copyToClipboard(exam.token)"
                                    title="Salin Token"
                                    class="p-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-xs transition-colors"
                                >
                                    📋
                                </button>
                                <button
                                    @click="regenerateToken(exam)"
                                    title="Generate Token Baru"
                                    class="p-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-xs transition-colors"
                                >
                                    🔄
                                </button>
                            </div>
                        </div>

                        <!-- Durasi & Info -->
                        <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                            <span>⏱️ {{ exam.duration_minutes }} Menit</span>
                            <span>📝 {{ exam.bank?.total_questions || 0 }} Soal</span>
                            <span>👥 {{ exam.submissions_count }} Peserta</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-1.5">
                            <button
                                @click="openSimulatorModal(exam)"
                                class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold transition-colors flex items-center gap-1"
                                title="Uji Simulasi Ujian Siswa"
                            >
                                <span>🎮</span> Tes CBT
                            </button>

                            <button
                                @click="openSyncModal(exam)"
                                class="px-2.5 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition-colors flex items-center gap-1"
                                title="Sinkronkan Nilai ke Buku Nilai Siswa"
                            >
                                <span>📤</span> Sync Rapor
                            </button>

                            <div class="flex items-center gap-1">
                                <button
                                    @click="openEditExamModal(exam)"
                                    class="p-1.5 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-100 text-xs"
                                    title="Edit Sesi"
                                >
                                    ✏️
                                </button>
                                <button
                                    @click="deleteExam(exam)"
                                    class="p-1.5 text-rose-500 hover:text-rose-700 rounded-lg hover:bg-rose-50 text-xs"
                                    title="Hapus Sesi"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Bank Soal & Butir Soal -->
            <div v-if="activeTab === 'banks'" class="space-y-6">
                <!-- Filter Bar -->
                <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-600">Filter Mata Pelajaran:</span>
                        <select
                            v-model="selectedBankFilter"
                            class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        >
                            <option value="all">Semua Mata Pelajaran</option>
                            <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                        </select>
                    </div>
                    <button
                        @click="openCreateBankModal"
                        class="px-3.5 py-1.5 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors shadow-xs"
                    >
                        + Tambah Bank Soal
                    </button>
                </div>

                <!-- Bank Soal List -->
                <div class="space-y-4">
                    <div
                        v-for="bank in filteredBanks"
                        :key="bank.id"
                        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4"
                    >
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">
                                        {{ bank.subject?.name }}
                                    </span>
                                    <span class="text-xs text-slate-400">KKM: <strong>{{ bank.passing_score }}</strong></span>
                                </div>
                                <h3 class="font-bold text-slate-800 text-base mt-1">{{ bank.title }}</h3>
                                <p class="text-xs text-slate-400">{{ bank.description || 'Tidak ada catatan deskripsi' }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    :href="route('academic.cbt.banks.export-template', bank.hashid || bank.id)"
                                    download
                                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1 transition-colors"
                                    title="Unduh Format CSV Soal"
                                >
                                    <span>📥</span> Template CSV
                                </a>
                                <button
                                    @click="openImportModal(bank)"
                                    class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-semibold flex items-center gap-1 transition-colors"
                                    title="Impor Soal Massal dari CSV"
                                >
                                    <span>📤</span> Impor CSV
                                </button>
                                <button
                                    @click="openCreateQuestionModal(bank)"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-1"
                                >
                                    <span>+</span> Tambah Soal
                                </button>
                                <button
                                    @click="openEditBankModal(bank)"
                                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deleteBank(bank)"
                                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-semibold"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <!-- Butir Soal List in Bank -->
                        <div v-if="bank.questions && bank.questions.length > 0" class="space-y-3">
                            <div
                                v-for="(q, qIdx) in bank.questions"
                                :key="q.id"
                                class="p-4 bg-slate-50/70 border border-slate-200/80 rounded-xl space-y-2 text-xs"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-start gap-2">
                                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[11px] shrink-0">
                                            {{ qIdx + 1 }}
                                        </span>
                                        <div>
                                            <div class="font-medium text-slate-800 leading-relaxed" v-html="formatQuestionText(q.question_text)"></div>
                                            <span class="text-[10px] text-slate-400">Tipe: {{ q.question_type }} | Bobot: {{ q.score_weight }} poin</span>
                                        </div>
                                    </div>

                                    <button
                                        @click="deleteQuestion(q)"
                                        class="text-rose-500 hover:text-rose-700 text-xs px-2 py-1 rounded-md hover:bg-rose-50"
                                        title="Hapus Butir Soal"
                                    >
                                        Hapus
                                    </button>
                                </div>

                                <!-- Opsi Pilihan Ganda -->
                                <div v-if="q.options && q.options.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2 pl-8 pt-1">
                                    <div
                                        v-for="opt in q.options"
                                        :key="opt.key"
                                        :class="[
                                            'p-2 rounded-lg border text-xs flex items-center gap-2',
                                            (q.correct_answer || '').split(',').map(s => s.trim().toUpperCase()).includes(opt.key)
                                                ? 'bg-emerald-50 border-emerald-300 text-emerald-900 font-bold'
                                                : 'bg-white border-slate-200 text-slate-600'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black',
                                                (q.correct_answer || '').split(',').map(s => s.trim().toUpperCase()).includes(opt.key) ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700'
                                            ]"
                                        >
                                            {{ opt.key }}
                                        </span>
                                        <span v-html="opt.text"></span>
                                        <span v-if="(q.correct_answer || '').split(',').map(s => s.trim().toUpperCase()).includes(opt.key)" class="ml-auto text-[10px] text-emerald-700 font-bold">✓ Kunci</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-4 text-xs text-slate-400 bg-slate-50 rounded-xl">
                            Belum ada butir soal pada bank soal ini. Klik <strong>+ Tambah Soal</strong> untuk menambahkan.
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Hasil & Nilai Siswa -->
            <div v-if="activeTab === 'submissions'" class="space-y-4">
                <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-600">Filter Sesi Ujian:</span>
                        <select
                            v-model="selectedExamFilter"
                            class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        >
                            <option value="all">Semua Sesi Ujian</option>
                            <option v-for="ex in exams" :key="ex.id" :value="ex.id">{{ ex.title }}</option>
                        </select>
                    </div>
                    <span class="text-xs text-slate-500">{{ filteredSubmissions.length }} hasil ujian tercatat</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-3">Nama Siswa & Kelas</th>
                                    <th class="px-6 py-3">Sesi Ujian & Mapel</th>
                                    <th class="px-6 py-3">Waktu Submit</th>
                                    <th class="px-6 py-3 text-center">Skor Nilai</th>
                                    <th class="px-6 py-3 text-center">Status KKM</th>
                                    <th class="px-6 py-3 text-center">Pelanggaran</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="sub in filteredSubmissions" :key="sub.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-6 py-3.5">
                                        <div class="font-bold text-slate-800 text-sm">{{ sub.student_profile?.full_name || sub.student_profile?.user?.name || 'Siswa' }}</div>
                                        <div class="text-[11px] text-slate-400">NISN: {{ sub.student_profile?.nisn }} | Kelas: {{ sub.student_profile?.classroom?.name || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <div class="font-semibold text-slate-800">{{ sub.exam?.title }}</div>
                                        <div class="text-[11px] text-indigo-600 font-medium">{{ sub.exam?.bank?.subject?.name }}</div>
                                    </td>
                                    <td class="px-6 py-3.5 text-slate-500 font-mono text-[11px]">
                                        {{ sub.submitted_at ? new Date(sub.submitted_at).toLocaleString('id-ID') : '-' }}
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span class="font-mono text-base font-black text-slate-900">{{ sub.total_score }}</span>
                                        <span class="text-[10px] text-slate-400 block">/ 100</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span
                                            :class="[
                                                 'px-3 py-1 rounded-full text-xs font-bold inline-block',
                                                 sub.is_passed ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                             ]"
                                         >
                                             {{ sub.is_passed ? '✓ TUNTAS' : '✗ REMEDIAL' }}
                                         </span>
                                     </td>
                                     <td class="px-6 py-3.5 text-center">
                                         <span
                                             v-if="(sub.violations_count || 0) > 0"
                                             class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1"
                                         >
                                             ⚠️ {{ sub.violations_count }}x
                                         </span>
                                         <span v-else class="text-emerald-600 text-xs font-medium">
                                             ✓ Aman
                                         </span>
                                     </td>
                                     <td class="px-6 py-3.5 text-right">
                                         <Link
                                             :href="route('academic.cbt.submissions.show', sub.hashid || sub.id)"
                                             class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition-colors shadow-2xs"
                                         >
                                             <span>📝</span> Koreksi / Review
                                         </Link>
                                     </td>
                                 </tr>
                                 <tr v-if="filteredSubmissions.length === 0">
                                     <td colspan="7" class="text-center py-8 text-slate-400 text-xs">
                                         Belum ada nilai siswa yang tercatat.
                                     </td>
                                 </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL BUAT / EDIT SESI UJIAN -->
        <div v-if="isExamModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">
                        {{ isEditingExam ? 'Edit Sesi Ujian CBT' : 'Terbitkan Sesi Ujian CBT Baru' }}
                    </h3>
                    <button @click="isExamModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitExam" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Bank Soal</label>
                        <select
                            v-model="examForm.cbt_bank_id"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            required
                        >
                            <option v-for="b in banks" :key="b.id" :value="b.id">
                                {{ b.title }} ({{ b.subject?.name }} - {{ b.total_questions }} Soal)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Sesi Ujian</label>
                        <input
                            v-model="examForm.title"
                            type="text"
                            placeholder="Contoh: UTS Pemrograman Web Semester Ganjil"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Target Kelas (Opsional)</label>
                            <select
                                v-model="examForm.classroom_id"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            >
                                <option value="">Semua Kelas</option>
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Durasi (Menit)</label>
                            <input
                                v-model.number="examForm.duration_minutes"
                                type="number"
                                min="5"
                                max="360"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Sesi</label>
                            <select
                                v-model="examForm.status"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            >
                                <option value="published">Published (Siap Dikerjakan)</option>
                                <option value="ongoing">Ongoing (Sedang Berlangsung)</option>
                                <option value="draft">Draft</option>
                                <option value="completed">Completed (Selesai)</option>
                            </select>
                        </div>
                        <div class="flex items-center pt-5">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                                <input v-model="examForm.randomize_questions" type="checkbox" class="accent-indigo-600 rounded-sm" />
                                Acak Urutan Soal
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="isExamModalOpen = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold">
                            {{ isEditingExam ? 'Simpan Perubahan' : 'Terbitkan Ujian' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL BUAT / EDIT BANK SOAL -->
        <div v-if="isBankModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">
                        {{ isEditingBank ? 'Edit Bank Soal' : 'Buat Bank Soal Baru' }}
                    </h3>
                    <button @click="isBankModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitBank" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mata Pelajaran</label>
                        <select
                            v-model="bankForm.subject_id"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            required
                        >
                            <option v-if="subjects.length === 0" value="" disabled>-- Anda belum memiliki jadwal mengajar mata pelajaran aktif --</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }} ({{ s.code }})</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Bank Soal</label>
                        <input
                            v-model="bankForm.title"
                            type="text"
                            placeholder="Contoh: Bank Soal Pemrograman Dasar Kelas X"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ambang Batas KKM (0 - 100)</label>
                        <input
                            v-model.number="bankForm.passing_score"
                            type="number"
                            min="0"
                            max="100"
                            step="0.1"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi / Catatan</label>
                        <textarea
                            v-model="bankForm.description"
                            rows="2"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            placeholder="Keterangan cakupan materi atau kisi-kisi..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="isBankModalOpen = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold">
                            {{ isEditingBank ? 'Simpan Perubahan' : 'Buat Bank Soal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL TAMBAH BUTIR SOAL -->
        <div v-if="isQuestionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">
                        Tambah Butir Soal: {{ selectedBankForQuestion?.title }}
                    </h3>
                    <button @click="isQuestionModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitQuestion" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pertanyaan / Soal</label>
                        <textarea
                            v-model="questionForm.question_text"
                            rows="3"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            placeholder="Tuliskan pertanyaan di sini..."
                            required
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Bobot Nilai Poin</label>
                            <input
                                v-model.number="questionForm.score_weight"
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kunci Jawaban Benar</label>
                            <select
                                v-model="questionForm.correct_answer"
                                class="w-full px-3 py-2 border border-emerald-300 bg-emerald-50 text-emerald-900 font-bold rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
                                required
                            >
                                <option value="A">Opsi A</option>
                                <option value="B">Opsi B</option>
                                <option value="C">Opsi C</option>
                                <option value="D">Opsi D</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pilihan Ganda A - D -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700">Pilihan Jawaban (Multiple Choice)</label>
                        <div v-for="opt in questionForm.options" :key="opt.key" class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-xs font-black shrink-0">
                                {{ opt.key }}
                            </span>
                            <input
                                v-model="opt.text"
                                type="text"
                                :placeholder="`Teks pilihan ${opt.key}...`"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                required
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="isQuestionModalOpen = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold">
                            Simpan Butir Soal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL SINKRONISASI NILAI CBT KE RAPOR -->
        <div v-if="isSyncModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">
                        Sinkronkan Nilai ke Buku Nilai Siswa
                    </h3>
                    <button @click="isSyncModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-3">
                    <p class="text-xs text-slate-600">
                        Sistem akan memasukkan nilai perolehan peserta ujian <strong>{{ syncExam?.title }}</strong> ke Buku Nilai Siswa secara otomatis.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Target Komponen Nilai:</label>
                        <select
                            v-model="syncGradeType"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        >
                            <option value="quiz_score">Ulangan Harian / Kuis (UH - Bobot 20%)</option>
                            <option value="midterm_score">Ujian Tengah Semester (UTS - Bobot 30%)</option>
                            <option value="final_score">Ujian Akhir Semester (UAS - Bobot 30%)</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="isSyncModalOpen = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitSyncGrades"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold"
                    >
                        Proses Sinkronisasi
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL SIMULATOR UJIAN ONLINE SISWA -->
        <div v-if="isSimulatorModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">
                            SIMULASI TAMPILAN SISWA
                        </span>
                        <h3 class="font-black text-slate-800 text-base mt-1">{{ simulatorExam?.title }}</h3>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Sisa Waktu</span>
                        <span class="text-base font-mono font-black text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-lg border border-rose-200">
                            {{ simulatorExam?.duration_minutes }}:00
                        </span>
                    </div>
                </div>

                <!-- Pilihan Siswa Uji Coba -->
                <div class="bg-indigo-50/50 p-3 rounded-2xl border border-indigo-100 flex items-center justify-between gap-3">
                    <span class="text-xs font-bold text-indigo-900">Siswa Penguji:</span>
                    <select
                        v-model="simulatorStudentId"
                        class="px-3 py-1.5 border border-indigo-200 bg-white rounded-xl text-xs font-medium text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                    >
                        <option v-for="st in students" :key="st.id" :value="st.id">
                            {{ st.full_name || st.user?.name }} (NISN: {{ st.nisn }} - {{ st.classroom?.name || 'Rombel' }})
                        </option>
                    </select>
                </div>

                <!-- Lembar Soal Ujian -->
                <div class="space-y-4 pt-2">
                    <div
                        v-for="(q, idx) in simulatorExam?.bank?.questions"
                        :key="q.id"
                        class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3 text-xs"
                    >
                        <div class="flex items-start gap-2">
                            <span class="w-6 h-6 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-xs shrink-0">
                                {{ idx + 1 }}
                            </span>
                            <div class="font-bold text-slate-800 text-sm leading-relaxed" v-html="formatQuestionText(q.question_text)"></div>
                        </div>

                        <div v-if="q.options" class="space-y-2 pl-8">
                            <label
                                v-for="opt in q.options"
                                :key="opt.key"
                                :class="[
                                    'flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                    simulatorAnswers[q.id] === opt.key
                                        ? 'bg-indigo-50 border-indigo-500 text-indigo-900 font-bold shadow-xs'
                                        : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                                ]"
                            >
                                <input
                                    type="radio"
                                    :name="`q_${q.id}`"
                                    :value="opt.key"
                                    v-model="simulatorAnswers[q.id]"
                                    class="accent-indigo-600"
                                />
                                <span class="font-black text-slate-600">{{ opt.key }}.</span>
                                <span>{{ opt.text }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <button type="button" @click="isSimulatorModalOpen = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitSimulator"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md flex items-center gap-1.5"
                    >
                        <span>✓</span> Selesaikan & Kirim Ujian
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL IMPOR SOAL WORD (.DOCX) & CSV -->
        <div v-if="isImportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">
                            Impor Soal: {{ selectedBankForImport?.title }}
                        </h3>
                        <p class="text-[11px] text-slate-500">Mendukung berkas Microsoft Word (.docx) lengkap dengan gambar & opsi ganda kompleks</p>
                    </div>
                    <button @click="isImportModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="bg-indigo-50/80 border border-indigo-100 p-3.5 rounded-2xl text-xs text-indigo-900 space-y-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-bold flex items-center gap-1.5">
                            📄 Unduh Template Siap Pakai:
                        </span>
                        <a
                            :href="route('academic.cbt.template-docx')"
                            target="_blank"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] shadow-sm transition"
                        >
                            <span>📥</span> Template Word (.docx)
                        </a>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        <strong>Aturan Pengetikan Word:</strong> Tulis nomor soal (1., 2.), opsi (A., B., C., D.), dan kunci (<em>Kunci: B</em> atau untuk kompleks <em>Kunci: A, C</em>). Gambar disisipkan langsung di Word via <em>Insert &gt; Picture</em>.
                    </p>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Berkas Word (.docx) atau CSV</label>
                        <input
                            type="file"
                            accept=".docx,.doc,.csv,text/csv,text/plain"
                            required
                            @change="(e) => importForm.file = e.target.files[0]"
                            class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                        <div v-if="importForm.errors.file" class="text-rose-500 text-xs mt-1">{{ importForm.errors.file }}</div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isImportModalOpen = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="importForm.processing || !importForm.file"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-md flex items-center gap-1.5"
                        >
                            <span>📤</span> {{ importForm.processing ? 'Mengunggah & Memproses...' : 'Unggah & Impor Soal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
