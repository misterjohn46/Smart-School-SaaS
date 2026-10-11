<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    gradeItems: Array,
    classrooms: Array,
    subjects: Array,
    academicYears: Array,
    currentSubject: Object,
    currentClassroom: Object,
    activeYear: Object,
    filters: Object,
    stats: Object,
});

const page = usePage();

// Navigasi Terpadu Administrator Sekolah
const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Tagihan & SPP Massal', route: 'finance.bills.index', icon: 'credit-card' },
    { label: 'POS Kasir SPP', route: 'finance.pos', icon: 'credit-card' },
    { label: 'Jadwal Pelajaran', route: 'academic.schedules', icon: 'calendar' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'Cetak Massal KTS', route: 'kts.batch', icon: 'id-card' },
    { label: 'Presensi GPS & AI', route: 'attendance.gps.index', icon: 'location' },
    { label: 'Verifikasi E-Leave', route: 'attendance.leave.index', icon: 'clipboard-check' },
    { label: 'Dompet Digital Siswa', route: 'wallet.index', icon: 'wallet' },
    { label: 'E-Kantin & Stand', route: 'canteen.index', icon: 'shopping-bag' },
    { isHeader: true, label: 'Master Data Akademik' },
    { label: 'Tahun Ajaran & Semester', route: 'academic.academic-years.index', icon: 'calendar' },
    { label: 'Program Keahlian (Jurusan)', route: 'academic.departments.index', icon: 'academic-cap' },
    { label: 'Data Siswa & Rombel', route: 'academic.students.index', icon: 'users' },
    { label: 'Data Guru & PTK', route: 'academic.teachers.index', icon: 'academic-cap' },
    { label: 'Rombel (Kelas)', route: 'academic.classrooms.index', icon: 'rectangle-stack' },
    { label: 'Mata Pelajaran', route: 'academic.subjects.index', icon: 'book-open' },
];

const selectedClassroom = ref(props.filters.classroom_id || props.classrooms[0]?.id || '');
const selectedSubject = ref(props.filters.subject_id || props.subjects[0]?.id || '');
const selectedYear = ref(props.filters.academic_year_id || props.activeYear?.id || '');

const kkm = computed(() => Number(props.currentSubject?.kkm) || 75);

// Reactive local state of grades for instant spreadsheet calculations
const localGrades = reactive(
    (props.gradeItems || []).map(item => ({
        student_id: item.student_id,
        name: item.name,
        nisn: item.nisn,
        gender: item.gender,
        assignment_score: item.assignment_score || 0,
        quiz_score: item.quiz_score || 0,
        midterm_score: item.midterm_score || 0,
        final_score: item.final_score || 0,
        notes: item.notes || '',
    }))
);

// Helper hitung nilai akhir real-time
const calculateOverall = (row) => {
    const a = Number(row.assignment_score) || 0;
    const q = Number(row.quiz_score) || 0;
    const m = Number(row.midterm_score) || 0;
    const f = Number(row.final_score) || 0;
    const overall = (a * 0.20) + (q * 0.20) + (m * 0.30) + (f * 0.30);
    return Math.round(overall * 100) / 100;
};

// Helper tentukan predikat huruf
const calculateLetter = (overall) => {
    if (overall <= 0) return '-';
    if (overall >= 90) return 'A';
    if (overall >= 80) return 'B';
    if (overall >= kkm.value) return 'C';
    return 'D';
};

// Helper status ketuntasan KKM
const isPassed = (overall) => {
    if (overall <= 0) return false;
    return overall >= kkm.value;
};

// Summary metrics computed dynamically from local grades
const liveStats = computed(() => {
    const scoredRows = localGrades.map(r => calculateOverall(r)).filter(s => s > 0);
    const total = localGrades.length;
    const avg = scoredRows.length > 0 ? (scoredRows.reduce((a, b) => a + b, 0) / scoredRows.length).toFixed(1) : 0;
    const passed = scoredRows.filter(s => s >= kkm.value).length;
    const remedial = scoredRows.filter(s => s < kkm.value).length;
    const max = scoredRows.length > 0 ? Math.max(...scoredRows) : 0;
    const min = scoredRows.length > 0 ? Math.min(...scoredRows) : 0;

    return {
        total,
        avg,
        passed,
        remedial,
        max,
        min,
    };
});

// Pergantian Filter Kelas / Mapel / Tahun
const changeFilter = () => {
    router.get(
        route('academic.grades.index'),
        {
            classroom_id: selectedClassroom.value,
            subject_id: selectedSubject.value,
            academic_year_id: selectedYear.value,
        },
        {
            preserveState: false,
            preserveScroll: true,
        }
    );
};

// Form submission for batch saving
const form = useForm({
    academic_year_id: selectedYear.value,
    classroom_id: selectedClassroom.value,
    subject_id: selectedSubject.value,
    grades: [],
});

const saveAllGrades = () => {
    form.academic_year_id = selectedYear.value;
    form.classroom_id = selectedClassroom.value;
    form.subject_id = selectedSubject.value;
    form.grades = localGrades.map(row => ({
        student_id: row.student_id,
        assignment_score: Number(row.assignment_score) || 0,
        quiz_score: Number(row.quiz_score) || 0,
        midterm_score: Number(row.midterm_score) || 0,
        final_score: Number(row.final_score) || 0,
        notes: row.notes || '',
    }));

    form.post(route('academic.grades.batch'), {
        preserveScroll: true,
    });
};

const printGrades = () => {
    window.print();
};
</script>

<template>
    <Head title="Buku Nilai Guru & Penilaian Siswa - Portal Sekolah" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <!-- Sidebar Terpadu (Sembunyi saat print) -->
        <div class="print:hidden">
            <Sidebar :menus="adminMenus" sectionTitle="Operasional Sekolah" />
        </div>

        <!-- Konten Utama -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0 print:pl-0">
            <!-- Header Atas (Sembunyi saat print) -->
            <header class="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between print:hidden">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Buku Nilai Guru & Penilaian Siswa</h1>
                        <p class="text-xs text-slate-400">Lembar penilaian interaktif, kalkulasi otomatis predikat, dan validasi KKM</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="currentSubject" class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        KKM: {{ currentSubject.kkm }}
                    </span>
                    <button
                        @click="printGrades"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors border border-slate-700 inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Rekap</span>
                    </button>
                    <button
                        @click="saveAllGrades"
                        :disabled="form.processing || localGrades.length === 0"
                        class="text-xs font-bold px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-all shadow-md shadow-indigo-600/20 disabled:opacity-50 inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Semua Nilai' }}</span>
                    </button>
                </div>
            </header>

            <!-- Main Workspace -->
            <main class="p-6 space-y-6 flex-1 print:p-2 print:text-black">
                <!-- Kop Surat Cetak (Hanya Muncul Saat Print) -->
                <div class="hidden print:block text-center border-b-2 border-black pb-4 mb-4">
                    <h2 class="text-xl font-bold uppercase">Rekapitulasi Penilaian Hasil Belajar Siswa</h2>
                    <h3 class="text-base font-semibold">SMK INFORMATIKA TERPADU</h3>
                    <p class="text-xs">Kelas: {{ currentClassroom?.name }} | Mata Pelajaran: {{ currentSubject?.name }} ({{ currentSubject?.code }}) | Ambang KKM: {{ currentSubject?.kkm }}</p>
                </div>

                <!-- Flash Notification -->
                <div v-if="$page.props.flash?.status" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 flex items-center justify-between text-sm animate-fade-in print:hidden">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $page.props.flash.status }}</span>
                    </div>
                </div>

                <!-- KPI Metric Cards (Sembunyi saat print) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rata-Rata Kelas</span>
                            <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-white">{{ liveStats.avg }}</span>
                            <span class="text-xs text-slate-400">/ 100 Poin</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tuntas KKM</span>
                            <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-emerald-400">{{ liveStats.passed }}</span>
                            <span class="text-xs text-slate-400">/ {{ liveStats.total }} Siswa</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Perlu Remedial</span>
                            <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-black text-rose-400">{{ liveStats.remedial }}</span>
                            <span class="text-xs text-slate-400">Siswa &lt; KKM ({{ kkm }})</span>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rentang Skor</span>
                            <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-white">{{ liveStats.max }}</span>
                            <span class="text-xs text-slate-400">Maks / Min: {{ liveStats.min }}</span>
                        </div>
                    </div>
                </div>

                <!-- Kontrol Seleksi Rombel & Mapel (Sembunyi saat print) -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm print:hidden">
                    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <!-- Pilih Rombel -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Rombel Kelas</label>
                            <select
                                v-model="selectedClassroom"
                                @change="changeFilter"
                                class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-all font-semibold"
                            >
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Pilih Mapel -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Mata Pelajaran</label>
                            <select
                                v-model="selectedSubject"
                                @change="changeFilter"
                                class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-all font-semibold"
                            >
                                <option v-for="s in subjects" :key="s.id" :value="s.id">
                                    {{ s.name }} (KKM: {{ s.kkm }})
                                </option>
                            </select>
                        </div>

                        <!-- Pilih Tahun Ajaran -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Tahun Ajaran</label>
                            <select
                                v-model="selectedYear"
                                @change="changeFilter"
                                class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-all"
                            >
                                <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                                    {{ yr.name }} ({{ yr.semester }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Bobot Penilaian Info -->
                    <div class="text-xs text-slate-400 bg-slate-950 px-3 py-2 rounded-xl border border-slate-800">
                        <span class="font-bold text-slate-200">Bobot Nilai:</span>
                        Tugas: 20% • UH: 20% • UTS: 30% • UAS: 30%
                    </div>
                </div>

                <!-- Spreadsheet Grid Nilai Siswa -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-sm print:bg-white print:border-black">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-300 print:text-black print:border-collapse">
                            <thead class="bg-slate-950/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800 print:bg-gray-100 print:text-black">
                                <tr>
                                    <th class="px-4 py-3 w-12 text-center">No</th>
                                    <th class="px-6 py-3">Nama Lengkap & NISN</th>
                                    <th class="px-3 py-3 w-24 text-center">Tugas (20%)</th>
                                    <th class="px-3 py-3 w-24 text-center">Kuis/UH (20%)</th>
                                    <th class="px-3 py-3 w-24 text-center">UTS (30%)</th>
                                    <th class="px-3 py-3 w-24 text-center">UAS (30%)</th>
                                    <th class="px-4 py-3 w-24 text-center">Nilai Akhir</th>
                                    <th class="px-3 py-3 w-16 text-center">Predikat</th>
                                    <th class="px-4 py-3 w-28 text-center">Status</th>
                                    <th class="px-4 py-3 print:hidden">Catatan Guru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 print:divide-gray-300">
                                <tr v-for="(row, idx) in localGrades" :key="row.student_id" class="hover:bg-slate-800/30 transition-colors">
                                    <!-- No -->
                                    <td class="px-4 py-3 text-center text-xs text-slate-500 print:text-black font-medium">
                                        {{ idx + 1 }}
                                    </td>

                                    <!-- Nama & NISN -->
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="font-bold text-white print:text-black">{{ row.name }}</div>
                                        <div class="text-xs text-slate-400 print:text-gray-600">NISN: {{ row.nisn }}</div>
                                    </td>

                                    <!-- Tugas -->
                                    <td class="px-3 py-3 text-center">
                                        <input
                                            v-model.number="row.assignment_score"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="w-16 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-center text-sm text-white focus:outline-none focus:border-indigo-500 print:border-none print:text-black print:bg-transparent"
                                        />
                                    </td>

                                    <!-- Kuis / UH -->
                                    <td class="px-3 py-3 text-center">
                                        <input
                                            v-model.number="row.quiz_score"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="w-16 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-center text-sm text-white focus:outline-none focus:border-indigo-500 print:border-none print:text-black print:bg-transparent"
                                        />
                                    </td>

                                    <!-- UTS -->
                                    <td class="px-3 py-3 text-center">
                                        <input
                                            v-model.number="row.midterm_score"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="w-16 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-center text-sm text-white focus:outline-none focus:border-indigo-500 print:border-none print:text-black print:bg-transparent"
                                        />
                                    </td>

                                    <!-- UAS -->
                                    <td class="px-3 py-3 text-center">
                                        <input
                                            v-model.number="row.final_score"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="w-16 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-center text-sm text-white focus:outline-none focus:border-indigo-500 print:border-none print:text-black print:bg-transparent"
                                        />
                                    </td>

                                    <!-- Nilai Akhir (Kalkulasi Otomatis) -->
                                    <td class="px-4 py-3 text-center font-bold text-base whitespace-nowrap">
                                        <span :class="[
                                            calculateOverall(row) >= kkm ? 'text-emerald-400 print:text-black' : 'text-rose-400 print:text-black'
                                        ]">
                                            {{ calculateOverall(row) }}
                                        </span>
                                    </td>

                                    <!-- Predikat -->
                                    <td class="px-3 py-3 text-center font-bold">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-800 text-xs text-indigo-300 font-black print:bg-transparent print:text-black">
                                            {{ calculateLetter(calculateOverall(row)) }}
                                        </span>
                                    </td>

                                    <!-- Status Ketuntasan KKM -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span
                                            v-if="isPassed(calculateOverall(row))"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 print:border-none print:text-black"
                                        >
                                            Tuntas
                                        </span>
                                        <span
                                            v-else-if="calculateOverall(row) > 0"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 print:border-none print:text-black"
                                        >
                                            Remedial
                                        </span>
                                        <span
                                            v-else
                                            class="text-xs text-slate-500"
                                        >
                                            Belum Ada Nilai
                                        </span>
                                    </td>

                                    <!-- Catatan Guru -->
                                    <td class="px-4 py-3 print:hidden">
                                        <input
                                            v-model="row.notes"
                                            type="text"
                                            placeholder="Saran / catatan..."
                                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                        />
                                    </td>
                                </tr>

                                <tr v-if="localGrades.length === 0">
                                    <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="font-medium text-slate-400">Tidak ada siswa yang terdaftar di kelas ini</p>
                                        <p class="text-xs mt-1">Pilih kelas lain atau tambahkan siswa melalui menu Master Data Siswa</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Table dengan Tombol Simpan Cepat -->
                    <div v-if="localGrades.length > 0" class="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between print:hidden">
                        <div class="text-xs text-slate-400">
                            Total <span class="font-bold text-white">{{ localGrades.length }}</span> siswa dalam lembar penilaian. Pastikan untuk menekan tombol Simpan sebelum berpindah halaman.
                        </div>
                        <button
                            @click="saveAllGrades"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/20 transition-all disabled:opacity-50 inline-flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Semua Nilai Siswa' }}</span>
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
}
</style>
