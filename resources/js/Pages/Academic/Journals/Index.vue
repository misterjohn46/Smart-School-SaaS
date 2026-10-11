<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    journals: Object,
    todaySchedules: Array,
    classrooms: Array,
    subjects: Array,
    teachers: Array,
    academicYears: Array,
    activeYear: Object,
    isTeacherOnly: Boolean,
    filters: Object,
    stats: Object,
});

const page = usePage();

// Navigasi Terpadu Administrator & Guru
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

// Reactive Filter Form
const filterForm = ref({
    teacher_user_id: props.filters?.teacher_user_id || '',
    classroom_id: props.filters?.classroom_id || '',
    subject_id: props.filters?.subject_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    search: props.filters?.search || '',
});

const applyFilters = () => {
    router.get(route('academic.journals.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.value = {
        teacher_user_id: '',
        classroom_id: '',
        subject_id: '',
        date_from: '',
        date_to: '',
        search: '',
    };
    applyFilters();
};

// Modal State: Create / Edit
const showModal = ref(false);
const isEditing = ref(false);
const editingJournalId = ref(null);

// Modal State: Detail View
const showDetailModal = ref(false);
const detailItem = ref(null);

// Common teaching methods options for quick chips
const methodSuggestions = [
    'Ceramah Interaktif',
    'Diskusi Kelompok',
    'Praktikum / Eksperimen',
    'Problem Based Learning (PBL)',
    'Project Based Learning (PjBL)',
    'Tanya Jawab & Drill Soal',
    'Penugasan Portofolio',
    'Demonstrasi & Presentasi',
];

const todayDateString = new Date().toISOString().split('T')[0];

const form = useForm({
    academic_year_id: props.activeYear?.id || (props.academicYears[0]?.id ?? ''),
    classroom_id: '',
    subject_id: '',
    teacher_user_id: props.filters?.teacher_user_id || page.props.auth?.user?.id || '',
    lesson_schedule_id: null,
    date: todayDateString,
    time_slot: '',
    topic_title: '',
    teaching_method: 'Ceramah Interaktif',
    learning_activity: '',
    class_notes: '',
    attendance_present: 32,
    attendance_sick: 0,
    attendance_permission: 0,
    attendance_absent: 0,
    status: 'terlaksana',
    substitute_teacher_name: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingJournalId.value = null;
    form.reset();
    form.clearErrors();
    form.academic_year_id = props.activeYear?.id || (props.academicYears[0]?.id ?? '');
    form.date = todayDateString;
    form.teaching_method = 'Ceramah Interaktif';
    form.status = 'terlaksana';
    form.attendance_present = 32;
    form.attendance_sick = 0;
    form.attendance_permission = 0;
    form.attendance_absent = 0;
    showModal.value = true;
};

// Quick Fill dari slot jadwal hari ini
const fillFromSchedule = (sch) => {
    isEditing.value = false;
    editingJournalId.value = null;
    form.reset();
    form.clearErrors();
    form.academic_year_id = props.activeYear?.id || (props.academicYears[0]?.id ?? '');
    form.classroom_id = sch.classroom_id;
    form.subject_id = sch.subject_id;
    form.lesson_schedule_id = sch.schedule_id;
    form.date = todayDateString;
    form.time_slot = sch.time_slot || '';
    form.teaching_method = 'Ceramah Interaktif';
    form.status = 'terlaksana';
    form.attendance_present = 32;
    form.attendance_sick = 0;
    form.attendance_permission = 0;
    form.attendance_absent = 0;
    showModal.value = true;
};

const openEditModal = (journal) => {
    isEditing.value = true;
    editingJournalId.value = journal.id;
    form.clearErrors();
    form.academic_year_id = journal.academic_year_id;
    form.classroom_id = journal.classroom_id;
    form.subject_id = journal.subject_id;
    form.teacher_user_id = props.isTeacherOnly ? page.props.auth?.user?.id : (journal.teacher_user_id || page.props.auth?.user?.id);
    form.lesson_schedule_id = journal.lesson_schedule_id || null;
    form.date = journal.date;
    form.time_slot = journal.time_slot === '-' ? '' : journal.time_slot;
    form.topic_title = journal.topic_title;
    form.teaching_method = journal.teaching_method;
    form.learning_activity = journal.learning_activity;
    form.class_notes = journal.class_notes === '-' ? '' : journal.class_notes;
    form.attendance_present = journal.attendance_present;
    form.attendance_sick = journal.attendance_sick;
    form.attendance_permission = journal.attendance_permission;
    form.attendance_absent = journal.attendance_absent;
    form.status = journal.status;
    form.substitute_teacher_name = journal.substitute_teacher_name || '';
    showModal.value = true;
};

const openDetailModal = (journal) => {
    detailItem.value = journal;
    showDetailModal.value = true;
};

const selectMethod = (m) => {
    form.teaching_method = m;
};

const submitJournal = () => {
    if (isEditing.value) {
        form.put(route('academic.journals.update', editingJournalId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('academic.journals.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteJournal = (journal) => {
    if (confirm(`Yakin ingin menghapus jurnal mengajar materi: "${journal.topic_title}"?`)) {
        router.delete(route('academic.journals.destroy', journal.id), {
            preserveScroll: true,
        });
    }
};

const verifyJournal = (journal) => {
    if (confirm(`Verifikasi supervisi jurnal ini atas nama guru ${journal.teacher_name}?`)) {
        router.post(route('academic.journals.verify', journal.id), {}, {
            preserveScroll: true,
        });
    }
};

const printJournalSheet = () => {
    window.print();
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'terlaksana':
            return { label: 'Terlaksana', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' };
        case 'daring':
            return { label: 'Daring / Online', class: 'bg-sky-500/10 text-sky-400 border-sky-500/20' };
        case 'tugas_mandiri':
            return { label: 'Tugas Mandiri', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20' };
        case 'pengganti':
            return { label: 'Guru Pengganti', class: 'bg-purple-500/10 text-purple-400 border-purple-500/20' };
        default:
            return { label: status, class: 'bg-slate-700 text-slate-300 border-slate-600' };
    }
};
</script>

<template>
    <Head title="Jurnal Mengajar Guru & Agenda Kelas - Portal Sekolah" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <!-- Sidebar Terpadu -->
        <Sidebar :menus="adminMenus" sectionTitle="Operasional Sekolah" />

        <!-- Konten Utama -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
            <!-- Header Atas (Hidden saat Print) -->
            <header class="no-print h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white leading-tight">Jurnal Mengajar & Agenda Kelas</h1>
                        <p class="text-xs text-slate-400">Dokumentasi KBM harian, materi pokok, aktivitas belajar & supervisi akademik</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="printJournalSheet"
                        class="px-3.5 py-1.5 rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium flex items-center gap-2 transition"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Jurnal</span>
                    </button>

                    <button
                        @click="openCreateModal"
                        class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/20 flex items-center gap-2 transition"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tulis Jurnal</span>
                    </button>
                </div>
            </header>

            <!-- Status Flash Notification -->
            <div v-if="$page.props.flash?.status" class="no-print mx-6 mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ $page.props.flash.status }}</span>
                </div>
            </div>

            <!-- Header Cetak Khusus Print -->
            <div class="only-print p-6 border-b border-black text-black">
                <div class="text-center pb-4">
                    <h2 class="text-xl font-bold uppercase tracking-wider">JURNAL MENGAJAR GURU & AGENDA KELAS</h2>
                    <p class="text-sm font-semibold">TAHUN AJARAN {{ activeYear?.name || '-' }}</p>
                    <p class="text-xs text-gray-600">Dokumen Portofolio KBM & Bukti Fisik Supervisi Akademik</p>
                </div>
                <div class="grid grid-cols-2 text-xs py-2 border-t border-b border-gray-400 mt-2">
                    <div>
                        <p><span class="font-semibold">Guru Pengajar:</span> {{ filters?.teacher_user_id ? (teachers.find(t => t.id === filters.teacher_user_id)?.name || 'Semua Guru') : 'Semua Guru' }}</p>
                        <p><span class="font-semibold">Kelas / Rombel:</span> {{ filters?.classroom_id ? (classrooms.find(c => c.id === filters.classroom_id)?.name || 'Semua Kelas') : 'Semua Kelas' }}</p>
                    </div>
                    <div class="text-right">
                        <p><span class="font-semibold">Mata Pelajaran:</span> {{ filters?.subject_id ? (subjects.find(s => s.id === filters.subject_id)?.name || 'Semua Mapel') : 'Semua Mapel' }}</p>
                        <p><span class="font-semibold">Tanggal Cetak:</span> {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                    </div>
                </div>
            </div>

            <main class="p-6 space-y-6">
                <!-- Statistik Card Ringkasan -->
                <div class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Total Jurnal Tercatat</p>
                            <h3 class="text-xl font-bold text-white">{{ stats?.total_journals || 0 }}</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Jurnal Bulan Ini</p>
                            <h3 class="text-xl font-bold text-emerald-400">{{ stats?.this_month || 0 }}</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Supervisi Diverifikasi</p>
                            <h3 class="text-xl font-bold text-blue-400">{{ stats?.verified_count || 0 }}</h3>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center gap-3">
                        <div class="p-3 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Kondisi Khusus (Daring/Pengganti)</p>
                            <h3 class="text-xl font-bold text-amber-400">{{ stats?.special_status || 0 }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Banner Quick Slot Jadwal Mengajar Hari Ini -->
                <div v-if="todaySchedules && todaySchedules.length > 0" class="no-print p-5 rounded-2xl bg-gradient-to-r from-indigo-950/40 via-slate-900/70 to-slate-900/40 border border-indigo-500/20">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                            <h3 class="text-sm font-bold text-white tracking-wide">Jadwal Mengajar Anda Hari Ini</h3>
                            <span class="text-xs text-slate-400 font-normal">({{ todaySchedules.length }} sesi pelajaran)</span>
                        </div>
                        <span class="text-xs text-indigo-300 font-medium">Klik tombol untuk mengisi jurnal secara instan</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div
                            v-for="sch in todaySchedules"
                            :key="sch.schedule_id"
                            class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 hover:border-indigo-500/40 transition flex items-center justify-between"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                        {{ sch.classroom_name }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-200">{{ sch.subject_name }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                    <span>🕒 {{ sch.time_slot || 'Jam KBM' }}</span>
                                    <span v-if="sch.room">📍 {{ sch.room }}</span>
                                </div>
                            </div>

                            <div>
                                <span
                                    v-if="sch.already_filled"
                                    class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Terisi</span>
                                </span>
                                <button
                                    v-else
                                    @click="fillFromSchedule(sch)"
                                    class="px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition flex items-center gap-1"
                                >
                                    <span>+ Isi Jurnal</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter & Search Bar -->
                <div class="no-print p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                        <!-- Filter Guru (Khusus Admin) -->
                        <div v-if="!isTeacherOnly">
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Guru Pengajar</label>
                            <select
                                v-model="filterForm.teacher_user_id"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Semua Guru</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>

                        <!-- Filter Kelas -->
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Kelas</label>
                            <select
                                v-model="filterForm.classroom_id"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Semua Kelas</option>
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <!-- Filter Mapel -->
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Mata Pelajaran</label>
                            <select
                                v-model="filterForm.subject_id"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Semua Mapel</option>
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>

                        <!-- Filter Dari Tanggal -->
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Dari Tanggal</label>
                            <input
                                type="date"
                                v-model="filterForm.date_from"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <!-- Filter Sampai Tanggal -->
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Sampai Tanggal</label>
                            <input
                                type="date"
                                v-model="filterForm.date_to"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <!-- Pencarian Topik / Aktivitas -->
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Cari Materi / Catatan</label>
                            <input
                                type="text"
                                v-model="filterForm.search"
                                placeholder="Kata kunci..."
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button
                            @click="resetFilters"
                            class="px-3 py-1 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-400 hover:text-slate-200 text-xs font-medium transition"
                        >
                            Reset
                        </button>
                        <button
                            @click="applyFilters"
                            class="px-4 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Terapkan Filter</span>
                        </button>
                    </div>
                </div>

                <!-- Tabel Data Jurnal Mengajar -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden print:border-black print:bg-white print:text-black">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300 print:text-black">
                            <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400 print:bg-gray-100 print:text-black print:border-black">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Tgl & Jam</th>
                                    <th class="px-4 py-3 font-semibold">Kelas & Mapel</th>
                                    <th v-if="!isTeacherOnly" class="px-4 py-3 font-semibold">Guru Pengajar</th>
                                    <th class="px-4 py-3 font-semibold">Materi Pokok & Aktivitas</th>
                                    <th class="px-4 py-3 font-semibold text-center">Presensi Siswa</th>
                                    <th class="px-4 py-3 font-semibold text-center">Status</th>
                                    <th class="px-4 py-3 font-semibold text-center">Supervisi</th>
                                    <th class="no-print px-4 py-3 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 print:divide-black">
                                <tr
                                    v-for="j in journals.data"
                                    :key="j.id"
                                    class="hover:bg-slate-800/40 transition print:hover:bg-transparent"
                                >
                                    <!-- Tanggal & Jam -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-semibold text-white print:text-black">{{ j.date_formatted }}</div>
                                        <div class="text-[11px] text-slate-400 print:text-gray-600">🕒 {{ j.time_slot }}</div>
                                    </td>

                                    <!-- Kelas & Mapel -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 border border-slate-700 text-indigo-300 print:border-black print:text-black">
                                                {{ j.classroom_name }}
                                            </span>
                                        </div>
                                        <div class="font-medium text-slate-200 mt-1 print:text-black">{{ j.subject_name }}</div>
                                    </td>

                                    <!-- Guru Pengajar (Admin view) -->
                                    <td v-if="!isTeacherOnly" class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-medium text-slate-200 print:text-black">{{ j.teacher_name }}</div>
                                        <div v-if="j.status === 'pengganti'" class="text-[11px] text-purple-400">
                                            Pengganti: {{ j.substitute_teacher_name || '-' }}
                                        </div>
                                    </td>

                                    <!-- Materi Pokok & Aktivitas -->
                                    <td class="px-4 py-3 max-w-xs sm:max-w-md">
                                        <div class="font-semibold text-white print:text-black">{{ j.topic_title }}</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 print:border-gray-400 print:text-black">
                                                {{ j.teaching_method }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 line-clamp-2 mt-1 print:text-gray-700">{{ j.learning_activity }}</p>
                                        <p v-if="j.class_notes && j.class_notes !== '-'" class="text-[10px] text-amber-400/90 mt-1 italic print:text-gray-600">
                                            Catatan: {{ j.class_notes }}
                                        </p>
                                    </td>

                                    <!-- Presensi Siswa -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" title="Hadir">
                                                H: {{ j.attendance_present }}
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20" title="Sakit">
                                                S: {{ j.attendance_sick }}
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20" title="Izin">
                                                I: {{ j.attendance_permission }}
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20" title="Alpa / Tanpa Keterangan">
                                                A: {{ j.attendance_absent }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status Pelaksanaan -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span
                                            :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border', getStatusBadge(j.status).class]"
                                        >
                                            {{ getStatusBadge(j.status).label }}
                                        </span>
                                    </td>

                                    <!-- Supervisi Digital -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div v-if="j.is_verified">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center gap-1">
                                                <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Verified</span>
                                            </span>
                                            <p class="text-[9px] text-slate-400 mt-0.5">{{ j.verified_by_name }}</p>
                                        </div>
                                        <div v-else>
                                            <button
                                                v-if="!isTeacherOnly"
                                                @click="verifyJournal(j)"
                                                class="no-print px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white border border-blue-500/30 transition"
                                            >
                                                Verifikasi
                                            </button>
                                            <span v-else class="text-[10px] text-slate-500 italic">Belum</span>
                                        </div>
                                    </td>

                                    <!-- Aksi (Hidden saat print) -->
                                    <td class="no-print px-4 py-3 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="openDetailModal(j)"
                                                class="p-1 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                                title="Lihat Detail"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>

                                            <button
                                                @click="openEditModal(j)"
                                                class="p-1 rounded text-amber-400 hover:text-amber-300 hover:bg-amber-400/10 transition"
                                                title="Edit Jurnal"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <button
                                                @click="deleteJournal(j)"
                                                class="p-1 rounded text-rose-400 hover:text-rose-300 hover:bg-rose-400/10 transition"
                                                title="Hapus Jurnal"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!journals.data || journals.data.length === 0">
                                    <td :colspan="isTeacherOnly ? 7 : 8" class="text-center py-10 text-slate-500">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-sm">Belum ada data jurnal mengajar yang sesuai filter.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <div v-if="journals.links && journals.links.length > 3" class="no-print p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <div>
                            Menampilkan {{ journals.from || 0 }} - {{ journals.to || 0 }} dari {{ journals.total || 0 }} catatan
                        </div>
                        <div class="flex items-center gap-1">
                            <Link
                                v-for="(link, i) in journals.links"
                                :key="i"
                                :href="link.url || '#'"
                                :class="[
                                    'px-2.5 py-1 rounded text-xs transition',
                                    link.active ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-slate-800 text-slate-300',
                                    !link.url ? 'opacity-40 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>

                <!-- Tanda Tangan Cetak Khusus Print -->
                <div class="only-print mt-10 pt-4 grid grid-cols-2 text-center text-xs text-black">
                    <div>
                        <p>Mengetahui,</p>
                        <p class="font-bold">Kepala Sekolah / Waka Kurikulum</p>
                        <div class="h-20"></div>
                        <p class="font-bold underline">( .................................................. )</p>
                        <p class="text-[10px] text-gray-600">NIP. ..........................................</p>
                    </div>
                    <div>
                        <p>Dibuat Oleh,</p>
                        <p class="font-bold">Guru Mata Pelajaran</p>
                        <div class="h-20"></div>
                        <p class="font-bold underline">( .................................................. )</p>
                        <p class="text-[10px] text-gray-600">NIP. ..........................................</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL TAMBAH / EDIT JURNAL MENGAJAR -->
    <!-- ======================================================== -->
    <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
    >
        <div class="relative w-full max-w-2xl rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 text-slate-100 my-8">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">
                            {{ isEditing ? 'Edit Catatan Jurnal Mengajar' : 'Catat Jurnal Mengajar Harian' }}
                        </h3>
                        <p class="text-xs text-slate-400">Masukkan aktivitas pembelajaran kelas secara lengkap & akurat</p>
                    </div>
                </div>
                <button
                    @click="showModal = false"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitJournal" class="space-y-4 mt-4">
                <!-- Baris 1: Tanggal & Jam Pelajaran -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tanggal KBM *</label>
                        <input
                            type="date"
                            v-model="form.date"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        />
                        <div v-if="form.errors.date" class="text-rose-400 text-[11px] mt-1">{{ form.errors.date }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Jam Ke / Waktu KBM</label>
                        <input
                            type="text"
                            v-model="form.time_slot"
                            placeholder="Contoh: Jam Ke 1 - 2 (07:15 - 08:45)"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        />
                        <div v-if="form.errors.time_slot" class="text-rose-400 text-[11px] mt-1">{{ form.errors.time_slot }}</div>
                    </div>
                </div>

                <!-- Baris 2: Kelas & Mapel -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Kelas (Rombel) *</label>
                        <select
                            v-model="form.classroom_id"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="" disabled>Pilih Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <div v-if="form.errors.classroom_id" class="text-rose-400 text-[11px] mt-1">{{ form.errors.classroom_id }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Mata Pelajaran *</label>
                        <select
                            v-model="form.subject_id"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="" disabled>Pilih Mata Pelajaran</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }} ({{ s.code }})</option>
                        </select>
                        <div v-if="form.errors.subject_id" class="text-rose-400 text-[11px] mt-1">{{ form.errors.subject_id }}</div>
                    </div>
                </div>

                <!-- Baris 3: Guru Pengajar (Jika Admin) & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-if="!isTeacherOnly">
                        <label class="block text-xs font-medium text-slate-300 mb-1">Guru Pengajar</label>
                        <select
                            v-model="form.teacher_user_id"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <div v-if="form.errors.teacher_user_id" class="text-rose-400 text-[11px] mt-1">{{ form.errors.teacher_user_id }}</div>
                    </div>

                    <div :class="isTeacherOnly ? 'sm:col-span-2' : ''">
                        <label class="block text-xs font-medium text-slate-300 mb-1">Status Pelaksanaan KBM *</label>
                        <select
                            v-model="form.status"
                            required
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="terlaksana">Terlaksana (Tatap Muka Langsung)</option>
                            <option value="daring">Pembelajaran Jarak Jauh (Daring / LMS)</option>
                            <option value="tugas_mandiri">Penugasan Mandiri Terstruktur</option>
                            <option value="pengganti">Diisi Guru Pengganti / Piket</option>
                        </select>
                        <div v-if="form.errors.status" class="text-rose-400 text-[11px] mt-1">{{ form.errors.status }}</div>
                    </div>
                </div>

                <!-- Guru Pengganti Input (Jika status == 'pengganti') -->
                <div v-if="form.status === 'pengganti'">
                    <label class="block text-xs font-medium text-purple-300 mb-1">Nama Guru Pengganti / Guru Piket</label>
                    <input
                        type="text"
                        v-model="form.substitute_teacher_name"
                        placeholder="Nama guru yang menggantikan..."
                        class="w-full bg-slate-800 border border-purple-500/40 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-purple-400"
                    />
                </div>

                <!-- Materi Pokok / Topik -->
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Materi Pokok / Capaian Pembelajaran *</label>
                    <input
                        type="text"
                        v-model="form.topic_title"
                        required
                        placeholder="Contoh: Bab 3 - Persamaan Linier Dua Variabel & Metode Eliminasi"
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    />
                    <div v-if="form.errors.topic_title" class="text-rose-400 text-[11px] mt-1">{{ form.errors.topic_title }}</div>
                </div>

                <!-- Metode Pembelajaran -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-medium text-slate-300">Metode & Model Pembelajaran *</label>
                        <span class="text-[10px] text-slate-400">Pilih rekomendasi atau ketik sendiri</span>
                    </div>
                    <input
                        type="text"
                        v-model="form.teaching_method"
                        required
                        placeholder="Contoh: Diskusi Kelompok & Praktikum"
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 mb-1.5"
                    />
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="m in methodSuggestions"
                            :key="m"
                            type="button"
                            @click="selectMethod(m)"
                            class="px-2 py-0.5 rounded text-[10px] bg-slate-800 hover:bg-indigo-600/30 text-slate-300 hover:text-indigo-200 border border-slate-700 transition"
                        >
                            + {{ m }}
                        </button>
                    </div>
                    <div v-if="form.errors.teaching_method" class="text-rose-400 text-[11px] mt-1">{{ form.errors.teaching_method }}</div>
                </div>

                <!-- Uraian Aktivitas Belajar -->
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Uraian Aktivitas Belajar & Skenario KBM *</label>
                    <textarea
                        v-model="form.learning_activity"
                        required
                        rows="3"
                        placeholder="Deskripsikan pembukaan, inti pelajaran, interaksi siswa, penugasan, dan penutupan KBM..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    ></textarea>
                    <div v-if="form.errors.learning_activity" class="text-rose-400 text-[11px] mt-1">{{ form.errors.learning_activity }}</div>
                </div>

                <!-- Catatan Kelas & Evaluasi Siswa -->
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Catatan Hambatan / Tindak Lanjut / Catatan Khusus</label>
                    <textarea
                        v-model="form.class_notes"
                        rows="2"
                        placeholder="Catatan kendala, siswa yang perlu remedial, tugas rumah..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                    ></textarea>
                </div>

                <!-- Ringkasan Presensi Siswa -->
                <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Ringkasan Kehadiran Siswa Saat Jam Ini</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-center">
                        <div class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                            <span class="text-[11px] text-emerald-400 font-semibold block">Hadir (H)</span>
                            <input
                                type="number"
                                min="0"
                                v-model="form.attendance_present"
                                class="w-full bg-transparent text-center font-bold text-sm text-emerald-300 focus:outline-none"
                            />
                        </div>
                        <div class="p-2 rounded-lg bg-blue-500/10 border border-blue-500/20">
                            <span class="text-[11px] text-blue-400 font-semibold block">Sakit (S)</span>
                            <input
                                type="number"
                                min="0"
                                v-model="form.attendance_sick"
                                class="w-full bg-transparent text-center font-bold text-sm text-blue-300 focus:outline-none"
                            />
                        </div>
                        <div class="p-2 rounded-lg bg-amber-500/10 border border-amber-500/20">
                            <span class="text-[11px] text-amber-400 font-semibold block">Izin (I)</span>
                            <input
                                type="number"
                                min="0"
                                v-model="form.attendance_permission"
                                class="w-full bg-transparent text-center font-bold text-sm text-amber-300 focus:outline-none"
                            />
                        </div>
                        <div class="p-2 rounded-lg bg-rose-500/10 border border-rose-500/20">
                            <span class="text-[11px] text-rose-400 font-semibold block">Alpa (A)</span>
                            <input
                                type="number"
                                min="0"
                                v-model="form.attendance_absent"
                                class="w-full bg-transparent text-center font-bold text-sm text-rose-300 focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button
                        type="button"
                        @click="showModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/20 transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Jurnal' : 'Simpan Jurnal') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL DETAIL JURNAL MENGAJAR -->
    <!-- ======================================================== -->
    <div
        v-if="showDetailModal && detailItem"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
    >
        <div class="relative w-full max-w-xl rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 text-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div>
                    <span class="text-xs text-indigo-400 font-semibold uppercase tracking-wider">Detail Jurnal Mengajar</span>
                    <h3 class="text-base font-bold text-white mt-0.5">{{ detailItem.topic_title }}</h3>
                </div>
                <button
                    @click="showDetailModal = false"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 text-xs mt-4">
                <div class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-slate-950/50 border border-slate-800">
                    <div>
                        <span class="text-slate-400 block">Tanggal & Waktu</span>
                        <span class="font-semibold text-white">{{ detailItem.date_formatted }} ({{ detailItem.time_slot }})</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Kelas & Rombel</span>
                        <span class="font-semibold text-white">{{ detailItem.classroom_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Mata Pelajaran</span>
                        <span class="font-semibold text-white">{{ detailItem.subject_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Guru Pengajar</span>
                        <span class="font-semibold text-white">{{ detailItem.teacher_name }}</span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 font-medium block mb-1">Metode Pembelajaran</span>
                    <span class="px-2 py-0.5 rounded text-xs bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 font-medium">
                        {{ detailItem.teaching_method }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-400 font-medium block mb-1">Uraian Aktivitas Pembelajaran</span>
                    <p class="p-3 rounded-lg bg-slate-950/60 border border-slate-800 text-slate-200 leading-relaxed whitespace-pre-line">
                        {{ detailItem.learning_activity }}
                    </p>
                </div>

                <div v-if="detailItem.class_notes && detailItem.class_notes !== '-'">
                    <span class="text-slate-400 font-medium block mb-1">Catatan Kelas / Tindak Lanjut</span>
                    <p class="p-3 rounded-lg bg-amber-500/5 border border-amber-500/20 text-amber-200/90 italic whitespace-pre-line">
                        {{ detailItem.class_notes }}
                    </p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-slate-400 block mb-1">Ringkasan Presensi Siswa</span>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-400 font-bold">Hadir: {{ detailItem.attendance_present }}</span>
                            <span class="text-blue-400 font-bold">Sakit: {{ detailItem.attendance_sick }}</span>
                            <span class="text-amber-400 font-bold">Izin: {{ detailItem.attendance_permission }}</span>
                            <span class="text-rose-400 font-bold">Alpa: {{ detailItem.attendance_absent }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block mb-1">Status KBM</span>
                        <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold border', getStatusBadge(detailItem.status).class]">
                            {{ getStatusBadge(detailItem.status).label }}
                        </span>
                    </div>
                </div>

                <!-- Status Supervisi -->
                <div class="p-3 rounded-xl border" :class="detailItem.is_verified ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-slate-950/50 border-slate-800'">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-slate-400 block font-medium">Status Supervisi Akademik</span>
                            <span v-if="detailItem.is_verified" class="text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Diverifikasi oleh {{ detailItem.verified_by_name }} ({{ detailItem.verified_at }})
                            </span>
                            <span v-else class="text-slate-500 italic mt-0.5 block">
                                Belum diverifikasi supervisi
                            </span>
                        </div>
                        <button
                            v-if="!detailItem.is_verified && !isTeacherOnly"
                            @click="verifyJournal(detailItem); showDetailModal = false;"
                            class="px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold transition"
                        >
                            Verifikasi Sekarang
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    @click="showDetailModal = false"
                    class="px-4 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    .only-print {
        display: block !important;
    }
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
}
@media screen {
    .only-print {
        display: none !important;
    }
}
</style>
