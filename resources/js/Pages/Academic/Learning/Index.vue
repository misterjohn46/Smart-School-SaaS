<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    canManage: {
        type: Boolean,
        default: false,
    },
    isTeacher: {
        type: Boolean,
        default: false,
    },
    teachingAssignments: {
        type: Object,
        default: () => ({}),
    },
    studentProfile: Object,
    materials: {
        type: Array,
        default: () => [],
    },
    announcements: {
        type: Array,
        default: () => [],
    },
    classrooms: {
        type: Array,
        default: () => [],
    },
    subjects: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_materials: 0,
            total_assignments: 0,
            total_submissions: 0,
            total_announcements: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', classroom_id: '', subject_id: '', type: '' }),
    },
});

const activeTab = ref('materials');

const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
    { label: 'Materi & Tugas (LMS)', route: 'academic.learning.index', icon: 'book-open' },
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

// ==================== FILTERS ====================
const search = ref(props.filters?.search || '');
const classroomId = ref(props.filters?.classroom_id || '');
const subjectId = ref(props.filters?.subject_id || '');
const typeFilter = ref(props.filters?.type || '');

let filterTimeout = null;
const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(
            route('academic.learning.index'),
            {
                search: search.value || undefined,
                classroom_id: classroomId.value || undefined,
                subject_id: subjectId.value || undefined,
                type: typeFilter.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    }, 300);
};

watch([search, classroomId, subjectId, typeFilter], () => {
    applyFilters();
});

// ==================== MODAL MATERI / TUGAS (GURU / ADMIN) ====================
const isMaterialModalOpen = ref(false);
const editingMaterial = ref(null);
const materialForm = useForm({
    subject_id: props.subjects?.[0]?.id || '',
    classroom_id: props.classrooms?.[0]?.id || '',
    type: 'material',
    title: '',
    description: '',
    reference_url: '',
    due_date: '',
    max_score: 100,
    is_published: true,
});

const availableSubjectsForModal = computed(() => {
    if (!props.isTeacher || !props.teachingAssignments || Object.keys(props.teachingAssignments).length === 0) {
        return props.subjects;
    }
    const currentClassId = Number(materialForm.classroom_id);
    if (!currentClassId) return props.subjects;

    const allowedIds = props.teachingAssignments[currentClassId] || [];
    if (allowedIds.length === 0) return props.subjects;

    return props.subjects.filter((s) => allowedIds.includes(s.id));
});

watch(() => materialForm.classroom_id, (newClassId) => {
    if (props.isTeacher && props.teachingAssignments) {
        const allowed = props.teachingAssignments[Number(newClassId)] || [];
        if (allowed.length > 0 && !allowed.includes(Number(materialForm.subject_id))) {
            materialForm.subject_id = allowed[0];
        }
    }
});

const openCreateMaterialModal = (defaultType = 'material') => {
    editingMaterial.value = null;
    materialForm.reset();
    materialForm.type = defaultType;

    const firstClass = props.classrooms?.[0];
    materialForm.classroom_id = firstClass?.id || '';

    if (firstClass && props.teachingAssignments && props.teachingAssignments[firstClass.id]?.length > 0) {
        materialForm.subject_id = props.teachingAssignments[firstClass.id][0];
    } else {
        materialForm.subject_id = props.subjects?.[0]?.id || '';
    }

    materialForm.max_score = 100;
    materialForm.is_published = true;
    isMaterialModalOpen.value = true;
};

const openEditMaterialModal = (item) => {
    editingMaterial.value = item;
    materialForm.subject_id = item.subject_id;
    materialForm.classroom_id = item.classroom_id;
    materialForm.type = item.type;
    materialForm.title = item.title;
    materialForm.description = item.description || '';
    materialForm.reference_url = item.reference_url || '';
    materialForm.due_date = item.due_date || '';
    materialForm.max_score = item.max_score || 100;
    materialForm.is_published = Boolean(item.is_published);
    isMaterialModalOpen.value = true;
};

const submitMaterial = () => {
    if (editingMaterial.value) {
        materialForm.put(route('academic.learning.materials.update', editingMaterial.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isMaterialModalOpen.value = false;
            },
        });
    } else {
        materialForm.post(route('academic.learning.materials.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isMaterialModalOpen.value = false;
            },
        });
    }
};

const deleteMaterial = (item) => {
    if (confirm(`Apakah Anda yakin ingin menghapus "${item.title}"?`)) {
        router.delete(route('academic.learning.materials.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

// ==================== MODAL PENGUMPULAN TUGAS (SISWA) ====================
const isSubmitModalOpen = ref(false);
const activeAssignment = ref(null);
const submissionForm = useForm({
    answer_text: '',
    attachment_url: '',
});

const openSubmitModal = (item) => {
    activeAssignment.value = item;
    submissionForm.answer_text = item.my_submission?.answer_text || '';
    submissionForm.attachment_url = item.my_submission?.attachment_url || '';
    isSubmitModalOpen.value = true;
};

const submitStudentAssignment = () => {
    if (!activeAssignment.value) return;
    submissionForm.post(route('academic.learning.materials.submit', activeAssignment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitModalOpen.value = false;
        },
    });
};

// ==================== MODAL PENILAIAN TUGAS SISWA (GURU) ====================
const isGradingModalOpen = ref(false);
const selectedMaterialForGrading = ref(null);
const activeSubmissionToGrade = ref(null);
const gradeForm = useForm({
    score: 85,
    teacher_feedback: '',
    sync_to_gradebook: true,
});

const openSubmissionsListModal = (item) => {
    selectedMaterialForGrading.value = item;
    activeSubmissionToGrade.value = null;
    isGradingModalOpen.value = true;
};

const selectSubmissionForGrading = (sub) => {
    activeSubmissionToGrade.value = sub;
    gradeForm.score = sub.score !== null ? sub.score : 85;
    gradeForm.teacher_feedback = sub.teacher_feedback || '';
    gradeForm.sync_to_gradebook = true;
};

const submitGrade = () => {
    if (!activeSubmissionToGrade.value) return;
    gradeForm.post(route('academic.learning.submissions.grade', activeSubmissionToGrade.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            activeSubmissionToGrade.value = null;
            isGradingModalOpen.value = false;
        },
    });
};

// ==================== MODAL PENGUMUMAN SEKOLAH ====================
const isAnnouncementModalOpen = ref(false);
const editingAnnouncement = ref(null);
const announcementForm = useForm({
    title: '',
    content: '',
    category: 'akademik',
    target_audience: 'semua',
    is_pinned: false,
    is_published: true,
});

const openCreateAnnouncementModal = () => {
    editingAnnouncement.value = null;
    announcementForm.reset();
    announcementForm.category = 'akademik';
    announcementForm.target_audience = 'semua';
    announcementForm.is_pinned = false;
    announcementForm.is_published = true;
    isAnnouncementModalOpen.value = true;
};

const openEditAnnouncementModal = (ann) => {
    editingAnnouncement.value = ann;
    announcementForm.title = ann.title;
    announcementForm.content = ann.content;
    announcementForm.category = ann.category;
    announcementForm.target_audience = ann.target_audience;
    announcementForm.is_pinned = Boolean(ann.is_pinned);
    announcementForm.is_published = Boolean(ann.is_published);
    isAnnouncementModalOpen.value = true;
};

const submitAnnouncement = () => {
    if (editingAnnouncement.value) {
        announcementForm.put(route('academic.learning.announcements.update', editingAnnouncement.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isAnnouncementModalOpen.value = false;
            },
        });
    } else {
        announcementForm.post(route('academic.learning.announcements.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isAnnouncementModalOpen.value = false;
            },
        });
    }
};

const deleteAnnouncement = (ann) => {
    if (confirm(`Apakah Anda yakin ingin menghapus pengumuman "${ann.title}"?`)) {
        router.delete(route('academic.learning.announcements.destroy', ann.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="E-Learning (Materi & Tugas) & Pengumuman Sekolah" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <Sidebar v-if="canManage" :menus="adminMenus" sectionTitle="Operasional Sekolah" />

        <div :class="['flex-1 flex flex-col min-w-0', canManage ? 'lg:pl-64' : '']">
            <!-- Header Bar -->
            <header class="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        v-if="!canManage"
                        :href="route('portal.student')"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-base sm:text-lg text-white leading-tight">
                            E-Learning (Materi & Tugas Harian) & Pengumuman
                        </h1>
                        <p class="text-xs text-slate-400 hidden sm:block">
                            Distribusi modul ajar digital, pengumpulan tugas siswa terintegrasi buku nilai, dan informasi resmi sekolah
                        </p>
                    </div>
                </div>

                <div v-if="canManage" class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openCreateMaterialModal('material')"
                        class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow transition"
                    >
                        + Materi / Tugas
                    </button>
                    <button
                        type="button"
                        @click="openCreateAnnouncementModal"
                        class="px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow transition"
                    >
                        + Pengumuman
                    </button>
                </div>
            </header>

            <main class="p-6 space-y-6 flex-1 max-w-6xl mx-auto w-full">
                <!-- Flash Banner -->
                <div v-if="$page.props.flash?.status" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 flex items-center gap-2 text-sm">
                    <span>✅</span>
                    <span>{{ $page.props.flash.status }}</span>
                </div>
                <div v-if="$page.props.errors?.error" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 flex items-center gap-2 text-sm">
                    <span>⚠️</span>
                    <span>{{ $page.props.errors.error }}</span>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Modul & Materi Ajar</span>
                        <div class="text-2xl font-black text-white mt-2">{{ stats.total_materials }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">Bahan bacaan & referensi</div>
                    </div>
                    <div class="bg-slate-900/60 border border-indigo-500/20 rounded-2xl p-5">
                        <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Tugas Harian Kelas</span>
                        <div class="text-2xl font-black text-indigo-400 mt-2">{{ stats.total_assignments }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">Penugasan terstruktur</div>
                    </div>
                    <div class="bg-slate-900/60 border border-emerald-500/20 rounded-2xl p-5">
                        <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Jawaban Tugas Masuk</span>
                        <div class="text-2xl font-black text-emerald-400 mt-2">{{ stats.total_submissions }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">Siap dinilai & sinkron rapor</div>
                    </div>
                    <div class="bg-slate-900/60 border border-amber-500/20 rounded-2xl p-5">
                        <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Pengumuman Resmi</span>
                        <div class="text-2xl font-black text-amber-400 mt-2">{{ stats.total_announcements }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">Informasi warga sekolah</div>
                    </div>
                </div>

                <!-- Tab Switcher -->
                <div class="flex items-center gap-2 border-b border-slate-800 pb-3">
                    <button
                        type="button"
                        @click="activeTab = 'materials'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition',
                            activeTab === 'materials'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                        ]"
                    >
                        📚 Materi & Tugas Harian ({{ materials.length }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'announcements'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition',
                            activeTab === 'announcements'
                                ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/25'
                                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                        ]"
                    >
                        📢 Papan Pengumuman Sekolah ({{ announcements.length }})
                    </button>
                </div>

                <!-- ==================== TAB 1: MATERI & TUGAS HARIAN ==================== -->
                <div v-if="activeTab === 'materials'" class="space-y-5">
                    <!-- Filter Bar -->
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-wrap items-center gap-3">
                        <div class="relative flex-1 min-w-[200px]">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari judul materi atau instruksi tugas..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 pl-9"
                            />
                            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <select
                            v-if="canManage"
                            v-model="classroomId"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300"
                        >
                            <option value="">Semua Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>

                        <select
                            v-model="subjectId"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300"
                        >
                            <option value="">Semua Mata Pelajaran</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>

                        <select
                            v-model="typeFilter"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="material">📘 Materi Bacaan</option>
                            <option value="assignment">📝 Tugas Harian</option>
                        </select>
                    </div>

                    <!-- Daftar Kartu Materi & Tugas -->
                    <div v-if="materials.length === 0" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                        Belum ada materi pembelajaran atau tugas harian yang diterbitkan.
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="item in materials"
                            :key="item.id"
                            class="bg-slate-900/70 border border-slate-800 hover:border-indigo-500/40 rounded-2xl p-5 flex flex-col justify-between gap-4 shadow-lg transition"
                        >
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="[
                                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                                item.type === 'assignment'
                                                    ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                                                    : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
                                            ]"
                                        >
                                            {{ item.type === 'assignment' ? '📝 Tugas Harian' : '📘 Materi Modul' }}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                            {{ item.classroom_name }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-slate-500">{{ item.created_at_formatted }}</span>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold text-indigo-400">{{ item.subject_name }} • Oleh: {{ item.teacher_name }}</div>
                                    <h3 class="text-base font-bold text-white mt-0.5">{{ item.title }}</h3>
                                </div>

                                <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line">
                                    {{ item.description || 'Silakan pelajari modul dan kerjakan sesuai instruksi guru pengampu.' }}
                                </p>

                                <div v-if="item.reference_url" class="pt-1">
                                    <a
                                        :href="item.reference_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-400 hover:text-sky-300 underline"
                                    >
                                        🔗 Buka Lampiran / Tautan Modul Pembelajaran
                                    </a>
                                </div>

                                <div v-if="item.type === 'assignment'" class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="text-slate-400">Batas Pengumpulan: </span>
                                        <span :class="item.is_overdue ? 'text-rose-400 font-bold' : 'text-amber-300 font-semibold'">
                                            {{ item.due_date_formatted || 'Fleksibel' }}
                                        </span>
                                    </div>
                                    <span class="font-mono text-slate-400">Skor Maks: {{ item.max_score }}</span>
                                </div>

                                <!-- Status Pengumpulan Siswa (Jika Login Sebagai Siswa) -->
                                <div v-if="studentProfile && item.type === 'assignment' && item.my_submission" class="p-3 rounded-xl bg-emerald-950/30 border border-emerald-500/30 text-xs space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-emerald-300">✓ Sudah Dikumpulkan ({{ item.my_submission.submitted_at_formatted }})</span>
                                        <span v-if="item.my_submission.score !== null" class="px-2 py-0.5 rounded bg-emerald-500 text-slate-950 font-black font-mono">
                                            Nilai: {{ item.my_submission.score }}
                                        </span>
                                        <span v-else class="text-amber-300 text-[11px]">Menunggu Penilaian Guru</span>
                                    </div>
                                    <p v-if="item.my_submission.teacher_feedback" class="text-slate-300 italic">
                                        Catatan Guru: "{{ item.my_submission.teacher_feedback }}"
                                    </p>
                                </div>
                            </div>

                            <!-- Card Footer Actions -->
                            <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
                                <template v-if="canManage">
                                    <button
                                        v-if="item.type === 'assignment'"
                                        type="button"
                                        @click="openSubmissionsListModal(item)"
                                        class="px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-bold transition"
                                    >
                                        📥 Jawaban Siswa ({{ item.submissions_count }})
                                    </button>
                                    <span v-else class="text-xs text-slate-500">Materi Terpublikasi</span>

                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditMaterialModal(item)"
                                            class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteMaterial(item)"
                                            class="px-2.5 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-semibold"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </template>

                                <template v-else-if="studentProfile && item.type === 'assignment'">
                                    <button
                                        type="button"
                                        @click="openSubmitModal(item)"
                                        class="w-full py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow transition"
                                    >
                                        {{ item.my_submission ? '✏️ Perbarui Jawaban Tugas' : '📤 Kumpulkan Jawaban Tugas' }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB 2: PAPAN PENGUMUMAN SEKOLAH ==================== -->
                <div v-if="activeTab === 'announcements'" class="space-y-4">
                    <div v-if="announcements.length === 0" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                        Belum ada pengumuman resmi sekolah yang dipublikasikan.
                    </div>

                    <div
                        v-for="ann in announcements"
                        :key="ann.id"
                        :class="[
                            'rounded-2xl p-5 border shadow-lg space-y-3 transition',
                            ann.is_pinned
                                ? 'bg-gradient-to-r from-slate-900 via-indigo-950/30 to-slate-900 border-amber-500/40'
                                : 'bg-slate-900/70 border-slate-800'
                        ]"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span v-if="ann.is_pinned" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-500 text-slate-950">
                                    📌 Disematkan
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                    {{ ann.category }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-slate-800 text-slate-300 border border-slate-700">
                                    Target: {{ ann.target_audience.replace('_', ' ') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <span>Oleh: <strong class="text-slate-200">{{ ann.author_name }}</strong> • {{ ann.created_at_formatted }}</span>
                                <template v-if="canManage">
                                    <button
                                        type="button"
                                        @click="openEditAnnouncementModal(ann)"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteAnnouncement(ann)"
                                        class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-semibold"
                                    >
                                        Hapus
                                    </button>
                                </template>
                            </div>
                        </div>

                        <h3 class="text-base font-bold text-white">{{ ann.title }}</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed whitespace-pre-line">{{ ann.content }}</p>
                    </div>
                </div>
            </main>
        </div>

        <!-- ==================== MODAL FORM MATERI / TUGAS ==================== -->
        <div v-if="isMaterialModalOpen" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white">
                        {{ editingMaterial ? 'Edit Materi / Tugas Kelas' : 'Terbitkan Materi / Tugas Kelas Baru' }}
                    </h3>
                    <button type="button" @click="isMaterialModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitMaterial" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="materialForm.type = 'material'"
                            :class="['py-2 rounded-xl text-xs font-bold border transition', materialForm.type === 'material' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-950 border-slate-800 text-slate-400']"
                        >
                            📘 Materi Pembelajaran
                        </button>
                        <button
                            type="button"
                            @click="materialForm.type = 'assignment'"
                            :class="['py-2 rounded-xl text-xs font-bold border transition', materialForm.type === 'assignment' ? 'bg-amber-600 border-amber-500 text-white' : 'bg-slate-950 border-slate-800 text-slate-400']"
                        >
                            📝 Tugas Harian Kelas
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Rombel Kelas *</label>
                            <select v-model="materialForm.classroom_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                                <option v-if="classrooms.length === 0" value="" disabled>-- Tidak ada rombel kelas yang diajar --</option>
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Mata Pelajaran *</label>
                            <select v-model="materialForm.subject_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                                <option v-if="availableSubjectsForModal.length === 0" value="" disabled>-- Tidak ada mata pelajaran yang diajar di kelas ini --</option>
                                <option v-for="s in availableSubjectsForModal" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Judul Materi / Tugas *</label>
                        <input
                            v-model="materialForm.title"
                            type="text"
                            placeholder="Contoh: Modul Bab 3 - Arsitektur REST API & Latihan Praktikum"
                            required
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Uraian Materi / Instruksi Pengerjaan</label>
                        <textarea
                            v-model="materialForm.description"
                            rows="3"
                            placeholder="Tuliskan ringkasan materi atau langkah-langkah pengerjaan tugas..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Tautan Modul / Video / Google Drive (Opsional)</label>
                        <input
                            v-model="materialForm.reference_url"
                            type="text"
                            placeholder="https://drive.google.com/..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        />
                    </div>

                    <div v-if="materialForm.type === 'assignment'" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Batas Waktu Pengumpulan</label>
                            <input
                                v-model="materialForm.due_date"
                                type="datetime-local"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Skor Maksimal</label>
                            <input
                                v-model.number="materialForm.max_score"
                                type="number"
                                min="10"
                                max="100"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="isMaterialModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400">Batal</button>
                        <button type="submit" :disabled="materialForm.processing" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">
                            {{ materialForm.processing ? 'Menyimpan...' : 'Simpan & Publikasikan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL KUMPULKAN TUGAS (SISWA) ==================== -->
        <div v-if="isSubmitModalOpen && activeAssignment" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white">Kumpulkan Tugas: {{ activeAssignment.title }}</h3>
                    <button type="button" @click="isSubmitModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitStudentAssignment" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Jawaban Tertulis / Ringkasan Pengerjaan *</label>
                        <textarea
                            v-model="submissionForm.answer_text"
                            rows="4"
                            required
                            placeholder="Tuliskan jawaban Anda di sini..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Tautan Berkas Tugas / Repository / Google Drive (Opsional)</label>
                        <input
                            v-model="submissionForm.attachment_url"
                            type="text"
                            placeholder="https://..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="isSubmitModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400">Batal</button>
                        <button type="submit" :disabled="submissionForm.processing" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold">
                            {{ submissionForm.processing ? 'Mengirim...' : 'Kirim Jawaban Tugas' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL DAFTAR & PENILAIAN JAWABAN SISWA (GURU) ==================== -->
        <div v-if="isGradingModalOpen && selectedMaterialForGrading" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl p-6 space-y-4 shadow-2xl max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-white">Jawaban Siswa: {{ selectedMaterialForGrading.title }}</h3>
                        <p class="text-xs text-slate-400">{{ selectedMaterialForGrading.classroom_name }} • {{ selectedMaterialForGrading.subject_name }}</p>
                    </div>
                    <button type="button" @click="isGradingModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <div v-if="!selectedMaterialForGrading.submissions || selectedMaterialForGrading.submissions.length === 0" class="py-10 text-center text-xs text-slate-500">
                    Belum ada siswa yang mengumpulkan jawaban untuk tugas ini.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="sub in selectedMaterialForGrading.submissions"
                        :key="sub.id"
                        class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2 text-xs"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-bold text-white">{{ sub.student_name }}</span>
                                <span class="text-slate-400 ml-2">(NISN: {{ sub.student_nisn }})</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-slate-500">{{ sub.submitted_at_formatted }}</span>
                                <span v-if="sub.score !== null" class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold font-mono">
                                    Nilai: {{ sub.score }}
                                </span>
                                <button
                                    type="button"
                                    @click="selectSubmissionForGrading(sub)"
                                    class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold"
                                >
                                    {{ sub.score !== null ? 'Ubah Nilai' : 'Beri Nilai' }}
                                </button>
                            </div>
                        </div>

                        <p class="text-slate-300 bg-slate-900 p-3 rounded-lg border border-slate-800/80 whitespace-pre-line">{{ sub.answer_text }}</p>

                        <div v-if="sub.attachment_url">
                            <a :href="sub.attachment_url" target="_blank" class="text-sky-400 hover:underline font-semibold">
                                📎 Buka Lampiran Tugas Siswa
                            </a>
                        </div>

                        <!-- Form Input Nilai Inline -->
                        <form v-if="activeSubmissionToGrade && activeSubmissionToGrade.id === sub.id" @submit.prevent="submitGrade" class="pt-3 mt-2 border-t border-slate-800 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Nilai (0 - 100) *</label>
                                    <input
                                        v-model.number="gradeForm.score"
                                        type="number"
                                        min="0"
                                        max="100"
                                        required
                                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono"
                                    />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Umpan Balik / Komentar Guru</label>
                                    <input
                                        v-model="gradeForm.teacher_feedback"
                                        type="text"
                                        placeholder=" Kerja bagus, pertahankan..."
                                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                                    <input type="checkbox" v-model="gradeForm.sync_to_gradebook" class="rounded bg-slate-900 border-slate-700 text-emerald-600" />
                                    <span>Sinkronkan otomatis ke Nilai Harian di Buku Nilai (Gradebook)</span>
                                </label>
                                <button type="submit" :disabled="gradeForm.processing" class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold">
                                    Simpan Nilai
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MODAL PENGUMUMAN SEKOLAH ==================== -->
        <div v-if="isAnnouncementModalOpen" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white">
                        {{ editingAnnouncement ? 'Edit Pengumuman Sekolah' : 'Publikasikan Pengumuman Sekolah Baru' }}
                    </h3>
                    <button type="button" @click="isAnnouncementModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitAnnouncement" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Judul Pengumuman *</label>
                        <input
                            v-model="announcementForm.title"
                            type="text"
                            required
                            placeholder="Contoh: Jadwal Pelaksanaan Ujian Tengah Semester Ganjil"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Kategori *</label>
                            <select v-model="announcementForm.category" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                                <option value="akademik">Akademik</option>
                                <option value="keuangan">Keuangan & SPP</option>
                                <option value="kegiatan">Kegiatan & Ekskul</option>
                                <option value="penting">Informasi Penting</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Target Pembaca *</label>
                            <select v-model="announcementForm.target_audience" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                                <option value="semua">Seluruh Warga Sekolah</option>
                                <option value="siswa">Khusus Siswa</option>
                                <option value="guru">Khusus Dewan Guru</option>
                                <option value="orang_tua">Khusus Orang Tua / Wali</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Isi Pengumuman *</label>
                        <textarea
                            v-model="announcementForm.content"
                            rows="4"
                            required
                            placeholder="Tuliskan isi pengumuman lengkap..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"
                        ></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" v-model="announcementForm.is_pinned" class="rounded bg-slate-950 border-slate-700 text-amber-500" />
                        <span>📌 Sematkan di bagian paling atas papan pengumuman</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="isAnnouncementModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400">Batal</button>
                        <button type="submit" :disabled="announcementForm.processing" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold">
                            {{ announcementForm.processing ? 'Mempublikasikan...' : 'Publikasikan Pengumuman' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
