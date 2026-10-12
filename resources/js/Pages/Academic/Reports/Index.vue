<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    academicYears: Array,
    selectedYearId: Number,
    classrooms: Array,
    selectedClassroomId: Number,
    selectedClassroom: Object,
    subjects: Array,
    ledgerRows: Array,
    students: Array,
    stats: Object,
});

// Tab Aktif
const activeTab = ref('ledger'); // 'ledger' | 'students'

// State Filter
const currentYearId = ref(props.selectedYearId);
const currentClassroomId = ref(props.selectedClassroomId);

const applyFilter = () => {
    router.get(route('academic.reports.index'), {
        academic_year_id: currentYearId.value,
        classroom_id: currentClassroomId.value,
    }, { preserveState: true, preserveScroll: true });
};

// Modal Catatan Wali Kelas
const isRemarkModalOpen = ref(false);
const selectedStudentForRemark = ref(null);

const remarkForm = useForm({
    academic_year_id: props.selectedYearId,
    classroom_id: props.selectedClassroomId,
    student_profile_id: '',
    attendance_sick: 0,
    attendance_permission: 0,
    attendance_unexcused: 0,
    homeroom_notes: '',
    extracurriculars: [
        { name: 'Pramuka Wajib', grade: 'A', description: 'Aktif dan disiplin dalam setiap perkemahan.' },
    ],
    promotion_status: 'naik_kelas',
});

const openRemarkModal = (studentRow) => {
    selectedStudentForRemark.value = studentRow;
    remarkForm.academic_year_id = props.selectedYearId;
    remarkForm.classroom_id = props.selectedClassroomId;
    remarkForm.student_profile_id = studentRow.student_id;

    if (studentRow.remark) {
        remarkForm.attendance_sick = studentRow.remark.attendance_sick || 0;
        remarkForm.attendance_permission = studentRow.remark.attendance_permission || 0;
        remarkForm.attendance_unexcused = studentRow.remark.attendance_unexcused || 0;
        remarkForm.homeroom_notes = studentRow.remark.homeroom_notes || '';
        remarkForm.extracurriculars = studentRow.remark.extracurriculars && studentRow.remark.extracurriculars.length > 0
            ? JSON.parse(JSON.stringify(studentRow.remark.extracurriculars))
            : [{ name: 'Pramuka Wajib', grade: 'A', description: 'Aktif dan disiplin dalam kepramukaan.' }];
        remarkForm.promotion_status = studentRow.remark.promotion_status || 'naik_kelas';
    } else {
        remarkForm.attendance_sick = 0;
        remarkForm.attendance_permission = 0;
        remarkForm.attendance_unexcused = 0;
        remarkForm.homeroom_notes = 'Tunjukkan terus semangat belajarmu dan tingkatkan keterlibatan dalam diskusi kelas.';
        remarkForm.extracurriculars = [
            { name: 'Pramuka Wajib', grade: 'A', description: 'Sangat aktif dalam kegiatan pramuka.' },
        ];
        remarkForm.promotion_status = 'naik_kelas';
    }

    isRemarkModalOpen.value = true;
};

const addEkskul = () => {
    remarkForm.extracurriculars.push({ name: '', grade: 'A', description: '' });
};

const removeEkskul = (index) => {
    if (remarkForm.extracurriculars.length > 1) {
        remarkForm.extracurriculars.splice(index, 1);
    }
};

const submitRemark = () => {
    remarkForm.post(route('academic.reports.remarks.store'), {
        onSuccess: () => {
            isRemarkModalOpen.value = false;
        },
    });
};

// Modal Pratinjau & Cetak Dokumen Rapor Siswa
const isReportModalOpen = ref(false);
const previewStudent = ref(null);
const printMode = ref('single'); // 'single' | 'batch'

const openStudentReportPreview = (studentRow) => {
    previewStudent.value = studentRow;
    printMode.value = 'single';
    isReportModalOpen.value = true;
};

const openBatchReportPreview = () => {
    printMode.value = 'batch';
    isReportModalOpen.value = true;
};

const printDocument = () => {
    window.print();
};

// Tautan e-Raport PDF per siswa, mengikuti tahun ajaran yang sedang dipilih.
const reportPdfUrl = (row) =>
    route('academic.reports.pdf', {
        hashId: row.hash_id,
        academic_year_id: props.selectedYearId,
    });

const selectedYearName = computed(() => {
    const yr = props.academicYears.find(y => y.id === props.selectedYearId);
    return yr ? `${yr.name} - Semester ${yr.semester}` : '2026/2027';
});
</script>

<template>
    <Head title="Leger Nilai & Buku Rapor Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">📑</span>
                        Leger Nilai & Buku Rapor Siswa
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Rekapitulasi nilai kelas, pemeringkatan ranking paralel, cetak buku rapor resmi, dan catatan wali kelas.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="printDocument"
                        class="px-3.5 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 transition-colors shadow-xs flex items-center gap-1.5"
                    >
                        <span>🖨️</span> Cetak Leger Kelas
                    </button>
                    <button
                        @click="openBatchReportPreview"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-1.5"
                    >
                        <span>📦</span> Cetak Massal Rapor 1 Kelas
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-2">
                <span>✓</span> {{ $page.props.flash.success }}
            </div>

            <!-- Filter Controls Bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Pilih Tahun Ajaran -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Tahun Ajaran & Semester
                        </label>
                        <select
                            v-model="currentYearId"
                            @change="applyFilter"
                            class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        >
                            <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                                {{ yr.name }} - {{ yr.semester }} {{ yr.is_active ? '● (Aktif)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Pilih Rombel Kelas -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Rombongan Belajar (Kelas)
                        </label>
                        <select
                            v-model="currentClassroomId"
                            @change="applyFilter"
                            class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        >
                            <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- KPI Kelas Quick Indicator -->
                <div class="flex items-center gap-4 text-xs">
                    <div class="bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200/80">
                        <span class="text-slate-400 block text-[10px]">Total Siswa</span>
                        <span class="font-bold text-slate-800">{{ stats.total_students }} Siswa</span>
                    </div>
                    <div class="bg-indigo-50 px-3 py-1.5 rounded-xl border border-indigo-100">
                        <span class="text-indigo-400 block text-[10px]">Rata-rata Rombel</span>
                        <span class="font-extrabold text-indigo-700">{{ stats.class_average }}</span>
                    </div>
                    <div class="bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100">
                        <span class="text-emerald-500 block text-[10px]">Ketuntasan KKM</span>
                        <span class="font-extrabold text-emerald-700">{{ stats.pass_percentage }}%</span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-200">
                <button
                    @click="activeTab = 'ledger'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'ledger'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>📊</span> Matriks Leger Nilai Kelas
                </button>
                <button
                    @click="activeTab = 'students'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'students'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>👤</span> Buku Rapor & Catatan Wali Kelas ({{ ledgerRows.length }})
                </button>
            </div>

            <!-- TAB 1: Matriks Leger Nilai Kelas (Spreadsheet Matrix) -->
            <div v-if="activeTab === 'ledger'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            <span>📋</span> Leger Nilai Rombel: {{ selectedClassroom?.name || 'Pilih Kelas' }}
                        </h4>
                        <span class="text-xs text-slate-500">Tahun Ajaran: {{ selectedYearName }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-3 py-3 text-center border-r border-slate-200 w-12">Rank</th>
                                    <th class="px-4 py-3 border-r border-slate-200 min-w-[180px]">Nama Siswa & NISN</th>
                                    <th class="px-2 py-3 text-center border-r border-slate-200 w-10">L/P</th>
                                    
                                    <!-- Dynamic Subject Columns -->
                                    <th
                                        v-for="sub in subjects"
                                        :key="sub.id"
                                        class="px-3 py-3 text-center border-r border-slate-200 min-w-[70px]"
                                    >
                                        <div class="font-bold">{{ sub.code }}</div>
                                        <div class="text-[9px] text-slate-400 font-normal">KKM: {{ sub.kkm ?? 75 }}</div>
                                    </th>

                                    <th class="px-3 py-3 text-center border-r border-slate-200 bg-indigo-50/50 text-indigo-950 font-black">Total</th>
                                    <th class="px-3 py-3 text-center border-r border-slate-200 bg-indigo-50 text-indigo-950 font-black">Rata2</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="row in ledgerRows"
                                    :key="row.student_id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <!-- Rank Badge -->
                                    <td class="px-3 py-2.5 text-center font-black border-r border-slate-100">
                                        <span
                                            :class="[
                                                'inline-flex w-6 h-6 rounded-full items-center justify-center text-xs font-black',
                                                row.rank === 1 ? 'bg-amber-100 text-amber-900 border border-amber-300 shadow-xs' :
                                                row.rank === 2 ? 'bg-slate-200 text-slate-800' :
                                                row.rank === 3 ? 'bg-amber-50 text-amber-800' : 'text-slate-500'
                                            ]"
                                        >
                                            {{ row.rank }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2.5 border-r border-slate-100">
                                        <div class="font-bold text-slate-800 text-xs">{{ row.name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">NISN: {{ row.nisn }}</div>
                                    </td>

                                    <td class="px-2 py-2.5 text-center font-bold text-slate-600 border-r border-slate-100">
                                        {{ row.gender }}
                                    </td>

                                    <!-- Nilai per Mapel -->
                                    <td
                                        v-for="sub in subjects"
                                        :key="sub.id"
                                        class="px-2 py-2 text-center border-r border-slate-100 font-mono"
                                    >
                                        <span
                                            v-if="row.grades[sub.id]"
                                            :class="[
                                                'px-1.5 py-0.5 rounded-md text-xs font-bold block',
                                                row.grades[sub.id].is_passed ? 'text-slate-800' : 'text-rose-600 bg-rose-50 font-black'
                                            ]"
                                        >
                                            {{ row.grades[sub.id].overall_score }}
                                        </span>
                                        <span v-else class="text-slate-300 text-[10px]">-</span>
                                    </td>

                                    <td class="px-3 py-2.5 text-center font-mono font-bold text-indigo-900 bg-indigo-50/30 border-r border-slate-100">
                                        {{ row.total_score }}
                                    </td>

                                    <td class="px-3 py-2.5 text-center font-mono font-black text-indigo-700 bg-indigo-50/50 border-r border-slate-100">
                                        {{ row.average_score }}
                                    </td>

                                    <td class="px-4 py-2.5 text-right space-x-1.5">
                                        <button
                                            @click="openStudentReportPreview(row)"
                                            class="px-2 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
                                            title="Buka Rapor Siswa"
                                        >
                                            Rapor
                                        </button>
                                        <!-- e-Raport PDF digenerate server, bukan dialog cetak browser -->
                                        <a
                                            :href="reportPdfUrl(row)"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-block px-2 py-1 text-xs font-semibold text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100 transition-colors"
                                            title="Unduh e-Raport PDF"
                                        >
                                            PDF
                                        </a>
                                        <button
                                            @click="openRemarkModal(row)"
                                            class="px-2 py-1 text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors"
                                            title="Catatan Wali Kelas"
                                        >
                                            Catatan
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="ledgerRows.length === 0">
                                    <td :colspan="subjects.length + 5" class="text-center py-10 text-slate-400 text-xs">
                                        Tidak ada data siswa atau nilai pada rombel dan tahun ajaran ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Buku Rapor & Catatan Wali Kelas (Cards View) -->
            <div v-if="activeTab === 'students'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="row in ledgerRows"
                    :key="row.student_id"
                    class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between space-y-4 hover:shadow-md transition-shadow"
                >
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="font-bold text-slate-800 text-sm">{{ row.name }}</h4>
                                <span class="text-[11px] text-slate-400 font-mono">NISN: {{ row.nisn }}</span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800">
                                Rank #{{ row.rank }}
                            </span>
                        </div>

                        <!-- Score Highlights -->
                        <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
                            <div class="bg-slate-50 p-2 rounded-xl">
                                <span class="text-slate-400 block text-[10px]">Nilai Rata-rata</span>
                                <span class="font-black text-slate-800 text-sm">{{ row.average_score }}</span>
                            </div>
                            <div class="bg-slate-50 p-2 rounded-xl">
                                <span class="text-slate-400 block text-[10px]">Ketuntasan Mapel</span>
                                <span class="font-bold text-emerald-600">{{ row.passed_count }} / {{ row.subject_count }} Tuntas</span>
                            </div>
                        </div>

                        <!-- Attendance Summary -->
                        <div class="flex items-center gap-3 mt-3 text-xs border-t border-slate-100 pt-2 text-slate-500">
                            <span>Sakit: <strong>{{ row.remark?.attendance_sick ?? 0 }}</strong></span>
                            <span>Izin: <strong>{{ row.remark?.attendance_permission ?? 0 }}</strong></span>
                            <span>Alpa: <strong class="text-rose-600">{{ row.remark?.attendance_unexcused ?? 0 }}</strong></span>
                        </div>

                        <!-- Homeroom Notes Snippet -->
                        <div class="mt-2 text-xs text-slate-600 italic bg-slate-50/70 p-2.5 rounded-xl border border-slate-100 line-clamp-2">
                            "{{ row.remark?.homeroom_notes || 'Belum ada catatan wali kelas.' }}"
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 gap-2">
                        <button
                            @click="openRemarkModal(row)"
                            class="px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl text-xs font-semibold transition-colors"
                        >
                            ✏️ Catatan Wali
                        </button>
                        <button
                            @click="openStudentReportPreview(row)"
                            class="px-4 py-1.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-xl text-xs font-bold transition-colors shadow-xs flex items-center gap-1"
                        >
                            <span>🖨️</span> Cetak Rapor
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CATATAN WALI KELAS & EKSTRAKURIKULER -->
        <div v-if="isRemarkModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Catatan Rapor Siswa</span>
                        <h3 class="font-bold text-slate-800 text-base">{{ selectedStudentForRemark?.name }}</h3>
                    </div>
                    <button @click="isRemarkModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitRemark" class="space-y-4">
                    <!-- Rekap Kehadiran Semester -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Rekap Kehadiran Semester Ini (Hari)
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="text-[11px] text-slate-500 block mb-1">Sakit (S)</label>
                                <input
                                    v-model.number="remarkForm.attendance_sick"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-500 block mb-1">Izin (I)</label>
                                <input
                                    v-model.number="remarkForm.attendance_permission"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>
                            <div>
                                <label class="text-[11px] text-rose-600 block mb-1">Tanpa Keterangan (A)</label>
                                <input
                                    v-model.number="remarkForm.attendance_unexcused"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-1.5 border border-rose-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:outline-hidden"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Status Kenaikan Kelas / Kelulusan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Keputusan Kenaikan / Kelulusan
                        </label>
                        <select
                            v-model="remarkForm.promotion_status"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        >
                            <option value="naik_kelas">Naik ke Tingkat Kelas Berikutnya</option>
                            <option value="tinggal_kelas">Tinggal di Tingkat Kelas Yang Sama</option>
                            <option value="lulus">Lulus dari Satuan Pendidikan</option>
                            <option value="belum_lulus">Belum Lulus</option>
                            <option value="dalam_proses">Dalam Proses / Semester Ganjil</option>
                        </select>
                    </div>

                    <!-- Catatan Wali Kelas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Catatan Perkembangan Karakter Wali Kelas
                        </label>
                        <textarea
                            v-model="remarkForm.homeroom_notes"
                            rows="3"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            placeholder="Tuliskan motivasi, perkembangan sikap, dan catatan kompetensi siswa..."
                        ></textarea>
                    </div>

                    <!-- Ekstrakurikuler -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Kegiatan Ekstrakurikuler
                            </label>
                            <button
                                type="button"
                                @click="addEkskul"
                                class="text-xs text-indigo-600 font-bold hover:underline"
                            >
                                + Tambah Ekskul
                            </button>
                        </div>

                        <div v-for="(ekskul, idx) in remarkForm.extracurriculars" :key="idx" class="flex items-center gap-2">
                            <input
                                v-model="ekskul.name"
                                type="text"
                                placeholder="Nama Ekskul (e.g. Pramuka / Futsal)"
                                class="w-1/3 px-2.5 py-1.5 border border-slate-300 rounded-xl text-xs"
                            />
                            <select
                                v-model="ekskul.grade"
                                class="w-20 px-2 py-1.5 border border-slate-300 rounded-xl text-xs font-bold"
                            >
                                <option value="A">A (Sangat Baik)</option>
                                <option value="B">B (Baik)</option>
                                <option value="C">C (Cukup)</option>
                            </select>
                            <input
                                v-model="ekskul.description"
                                type="text"
                                placeholder="Keterangan keaktifan..."
                                class="flex-1 px-2.5 py-1.5 border border-slate-300 rounded-xl text-xs"
                            />
                            <button
                                type="button"
                                @click="removeEkskul(idx)"
                                class="text-rose-500 hover:text-rose-700 text-xs px-2"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="isRemarkModalOpen = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold">
                            Simpan Catatan Rapor
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL PRATINJAU & CETAK RESMI RAPOR SISWA -->
        <div v-if="isReportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-4xl w-full p-6 shadow-2xl space-y-6 max-h-[94vh] overflow-y-auto print:p-0 print:shadow-none print:max-w-none print:w-full">
                <!-- Toolbar Non-Print -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 print:hidden">
                    <h3 class="font-bold text-slate-800 text-base">
                        {{ printMode === 'batch' ? 'Pratinjau Cetak Massal Rapor 1 Kelas' : `Pratinjau Rapor: ${previewStudent?.name}` }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            @click="printDocument"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm flex items-center gap-1.5"
                        >
                            <span>🖨️</span> Cetak Sekarang (Print / PDF)
                        </button>
                        <button @click="isReportModalOpen = false" class="text-slate-400 hover:text-slate-600 px-2 text-lg">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- DOKUMEN CETAK RAPOR (Format Standar Nasional) -->
                <div
                    v-for="st in (printMode === 'batch' ? ledgerRows : [previewStudent])"
                    :key="st?.student_id"
                    class="space-y-6 bg-white p-8 border border-slate-200 rounded-2xl print:border-none print:p-0 print:break-after-page"
                >
                    <!-- KOP SURAT SEKOLAH RESMI -->
                    <div class="border-b-2 border-slate-900 pb-4 text-center space-y-1">
                        <div class="flex items-center justify-center gap-3">
                            <div class="w-12 h-12 bg-indigo-900 text-white rounded-xl flex items-center justify-center font-black text-xl">
                                S
                            </div>
                            <div>
                                <h1 class="text-base font-black uppercase tracking-wider text-slate-900">
                                    SMK INFORMATIKA TERPADU
                                </h1>
                                <p class="text-[11px] text-slate-600">
                                    NPSN: 20260913 • Akreditasi A (Unggul) • Program Kejuruan Teknologi Informasi
                                </p>
                                <p class="text-[10px] text-slate-500">
                                    Jl. Pendidikan No. 45, Jakarta Selatan • Telp: (021) 7890-1234 • info@smkinformatika.sch.id
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- JUDUL & BIODATA SISWA -->
                    <div class="text-center space-y-1">
                        <h2 class="text-sm font-black uppercase underline tracking-wide text-slate-900">
                            LAPORAN HASIL BELAJAR PESERTA DIDIK (RAPOR)
                        </h2>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-xs border border-slate-300 p-3 rounded-lg">
                        <div class="space-y-1">
                            <div>Nama Peserta Didik: <strong>{{ st?.name }}</strong></div>
                            <div>Nomor Induk / NISN: <strong>{{ st?.nisn }}</strong></div>
                            <div>Sekolah: <strong>SMK Informatika Terpadu</strong></div>
                        </div>
                        <div class="space-y-1 text-right sm:text-left">
                            <div>Kelas / Rombel: <strong>{{ selectedClassroom?.name }}</strong></div>
                            <div>Semester / Tahun: <strong>{{ selectedYearName }}</strong></div>
                            <div>Peringkat Kelas: <strong>#{{ st?.rank }} dari {{ ledgerRows.length }} Siswa</strong></div>
                        </div>
                    </div>

                    <!-- TABEL NILAI MATA PELAJARAN -->
                    <div class="space-y-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">A. Nilai Capaian Kompetensi</h3>
                        <table class="w-full text-left text-xs border border-slate-300 border-collapse">
                            <thead class="bg-slate-100 font-bold text-slate-800 uppercase text-[10px]">
                                <tr>
                                    <th class="border border-slate-300 px-2 py-2 text-center w-8">No</th>
                                    <th class="border border-slate-300 px-3 py-2">Mata Pelajaran</th>
                                    <th class="border border-slate-300 px-2 py-2 text-center w-12">KKM</th>
                                    <th class="border border-slate-300 px-2 py-2 text-center w-14">Nilai</th>
                                    <th class="border border-slate-300 px-2 py-2 text-center w-12">Predikat</th>
                                    <th class="border border-slate-300 px-3 py-2">Keterangan / Capaian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(sub, idx) in subjects" :key="sub.id">
                                    <td class="border border-slate-300 px-2 py-1.5 text-center">{{ idx + 1 }}</td>
                                    <td class="border border-slate-300 px-3 py-1.5 font-medium">{{ sub.name }}</td>
                                    <td class="border border-slate-300 px-2 py-1.5 text-center">{{ sub.kkm ?? 75 }}</td>
                                    <td class="border border-slate-300 px-2 py-1.5 text-center font-bold">
                                        {{ st?.grades[sub.id]?.overall_score ?? '-' }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 text-center font-bold">
                                        {{ st?.grades[sub.id]?.letter_grade ?? '-' }}
                                    </td>
                                    <td class="border border-slate-300 px-3 py-1.5 text-[11px] text-slate-600">
                                        {{ st?.grades[sub.id]?.is_passed ? 'Tuntas mencapai kompetensi standar' : 'Perlu pembinaan remedial' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- KEHADIRAN & EKSTRAKURIKULER -->
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <!-- Ketidakhadiran -->
                        <div class="space-y-1">
                            <h3 class="font-bold uppercase tracking-wider text-slate-900 text-[11px]">B. Ketidakhadiran</h3>
                            <table class="w-full text-xs border border-slate-300 border-collapse">
                                <tr>
                                    <td class="border border-slate-300 px-3 py-1">Sakit (S)</td>
                                    <td class="border border-slate-300 px-3 py-1 text-center font-bold">{{ st?.remark?.attendance_sick ?? 0 }} hari</td>
                                </tr>
                                <tr>
                                    <td class="border border-slate-300 px-3 py-1">Izin (I)</td>
                                    <td class="border border-slate-300 px-3 py-1 text-center font-bold">{{ st?.remark?.attendance_permission ?? 0 }} hari</td>
                                </tr>
                                <tr>
                                    <td class="border border-slate-300 px-3 py-1">Tanpa Keterangan (A)</td>
                                    <td class="border border-slate-300 px-3 py-1 text-center font-bold">{{ st?.remark?.attendance_unexcused ?? 0 }} hari</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Ekstrakurikuler -->
                        <div class="space-y-1">
                            <h3 class="font-bold uppercase tracking-wider text-slate-900 text-[11px]">C. Ekstrakurikuler</h3>
                            <table class="w-full text-xs border border-slate-300 border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-[10px]">
                                        <th class="border border-slate-300 px-2 py-1">Kegiatan</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(ex, eIdx) in (st?.remark?.extracurriculars || [{name: 'Pramuka Wajib', grade: 'A'}])" :key="eIdx">
                                        <td class="border border-slate-300 px-2 py-1">{{ ex.name }}</td>
                                        <td class="border border-slate-300 px-2 py-1 text-center font-bold">{{ ex.grade }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CATATAN WALI KELAS & KEPUTUSAN -->
                    <div class="space-y-2 text-xs border border-slate-300 p-3 rounded-lg">
                        <div>
                            <span class="font-bold uppercase tracking-wider block text-[10px] text-slate-700">Catatan Wali Kelas:</span>
                            <p class="italic text-slate-800 mt-0.5">
                                "{{ st?.remark?.homeroom_notes || 'Tingkatkan terus kedisiplinan dan prestasi belajarmu!' }}"
                            </p>
                        </div>
                        <div class="border-t border-slate-200 pt-2 font-bold text-slate-900">
                            Keputusan: 
                            <span class="text-indigo-700 underline uppercase">
                                {{ st?.remark?.promotion_status ? st.remark.promotion_status.replace('_', ' ') : 'Dalam Proses' }}
                            </span>
                        </div>
                    </div>

                    <!-- KOLOM TANDA TANGAN -->
                    <div class="grid grid-cols-3 gap-4 text-xs pt-4 text-center">
                        <div>
                            <div>Orang Tua / Wali Murid</div>
                            <div class="h-16"></div>
                            <div class="border-b border-slate-400 w-32 mx-auto"></div>
                            <div class="text-[10px] text-slate-500 mt-1">Tanda Tangan & Nama</div>
                        </div>

                        <div>
                            <div>Wali Kelas</div>
                            <div class="h-16"></div>
                            <div class="border-b border-slate-400 w-36 mx-auto font-bold">Dra. Hj. Siti Rohmah, M.Pd</div>
                            <div class="text-[10px] text-slate-500 mt-1">NIP. 19780412 200501 2 003</div>
                        </div>

                        <div>
                            <div>Kepala Sekolah</div>
                            <div class="h-16"></div>
                            <div class="border-b border-slate-400 w-40 mx-auto font-bold">Dr. H. Bambang Sudarmono, M.Pd</div>
                            <div class="text-[10px] text-slate-500 mt-1">NIP. 19680315 199303 1 004</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body {
        background: white !important;
    }
    nav, aside, header {
        display: none !important;
    }
}
</style>
