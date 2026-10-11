<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    classrooms: Array,
    selectedClassroomId: [Number, String],
    selectedClassroom: Object,
    schedules: Object,
    academicYear: String,
    isMockup: Boolean,
    subjects: {
        type: Array,
        default: () => [],
    },
    teachers: {
        type: Array,
        default: () => [],
    },
});

// Menus navigasi Command Center
const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Tagihan & SPP Massal', route: 'finance.bills.index', icon: 'credit-card' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'POS Kasir SPP', route: 'finance.pos', icon: 'credit-card' },
    { label: 'Cetak Massal KTS', route: 'kts.batch', icon: 'id-card' },
    { label: 'Presensi GPS & AI', route: 'attendance.gps.index', icon: 'location' },
    { label: 'Verifikasi E-Leave', route: 'attendance.leave.index', icon: 'clipboard-check' },
    { label: 'Dompet Digital Siswa', route: 'wallet.index', icon: 'wallet' },
    { label: 'E-Kantin & Stand', route: 'canteen.index', icon: 'shopping-bag' },
    { isHeader: true, label: 'Master Data Akademik' },
    { label: 'Data Siswa & Rombel', route: 'academic.students.index', icon: 'users' },
    { label: 'Data Guru & PTK', route: 'academic.teachers.index', icon: 'academic-cap' },
    { label: 'Rombel (Kelas)', route: 'academic.classrooms.index', icon: 'rectangle-stack' },
    { label: 'Mata Pelajaran', route: 'academic.subjects.index', icon: 'book-open' },
];

const daysConfig = [
    { key: 'senin', label: 'Senin', iconBg: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' },
    { key: 'selasa', label: 'Selasa', iconBg: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' },
    { key: 'rabu', label: 'Rabu', iconBg: 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300' },
    { key: 'kamis', label: 'Kamis', iconBg: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' },
    { key: 'jumat', label: 'Jumat', iconBg: 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300' },
    { key: 'sabtu', label: 'Sabtu', iconBg: 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300' },
];

// Preset Jam Pelajaran Standar Sekolah (Template Jam)
const presetTimeSlots = [
    { label: 'Jam 1', start: '07:15', end: '08:45', order: 1 },
    { label: 'Jam 2', start: '09:00', end: '10:30', order: 2 },
    { label: 'Jam 3', start: '10:45', end: '12:15', order: 3 },
    { label: 'Jam 4', start: '13:00', end: '14:30', order: 4 },
    { label: 'Jam 5', start: '14:30', end: '16:00', order: 5 },
];

const selectedDayTab = ref('senin');
const showModal = ref(false);

const form = useForm({
    classroom_id: props.selectedClassroomId,
    subject_id: '',
    teacher_user_id: '',
    day_of_week: 'senin',
    start_time: '07:15',
    end_time: '08:45',
    room: 'Ruang Kelas',
    order: 1,
});

watch(() => props.selectedClassroomId, (newVal) => {
    form.classroom_id = newVal;
});

const onClassChange = (e) => {
    const classId = e.target.value;
    router.get(route('academic.schedules'), { classroom_id: classId }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openModalForDay = (dayKey) => {
    form.day_of_week = dayKey;
    form.classroom_id = props.selectedClassroomId;
    
    // Hitung urutan jam ke- berikutnya secara otomatis
    const currentList = props.schedules[dayKey] || [];
    const nextOrder = currentList.length + 1;
    form.order = nextOrder;

    // Pilih preset jam yang sesuai jika tersedia
    const matchedPreset = presetTimeSlots.find(p => p.order === nextOrder) || presetTimeSlots[0];
    if (matchedPreset) {
        form.start_time = matchedPreset.start;
        form.end_time = matchedPreset.end;
    }

    showModal.value = true;
};

const applyPreset = (preset) => {
    form.start_time = preset.start;
    form.end_time = preset.end;
    form.order = preset.order;
};

const submitSchedule = () => {
    form.post(route('academic.schedules.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset('subject_id', 'teacher_user_id');
        },
    });
};

const deleteSchedule = (scheduleId, subjectName) => {
    if (confirm(`Hapus jadwal pelajaran "${subjectName}"?`)) {
        router.delete(route('academic.schedules.destroy', scheduleId), {
            preserveScroll: true,
        });
    }
};

const printTimetable = () => {
    window.print();
};
</script>

<template>
    <Head title="Jadwal Pelajaran & Mengajar Terpadu" />

    <AuthenticatedLayout :menus="adminMenus" section-title="Operasional Sekolah">
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto print:p-0">

            <!-- 1. Header & Filter Bar -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 shadow-2xl p-6 sm:p-8 text-white print:hidden">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                📅 Timetable Scheduler
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                TA {{ academicYear }}
                            </span>
                            <span v-if="isMockup" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                Mode Simulasi (Klik "+" untuk menginput data asli)
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                            Jadwal Pelajaran & Mengajar
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl">
                            Kelola jadwal pelajaran langsung di sini. Tambahkan slot jam baru dengan tombol plus (+) tanpa memuat ulang halaman.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <button
                            @click="openModalForDay(selectedDayTab)"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/25 transition transform active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            + Tambah Jadwal Baru
                        </button>

                        <button
                            @click="printTimetable"
                            class="inline-flex items-center gap-2 px-4 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-semibold text-xs border border-slate-700 transition"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Jadwal
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Class Selector Bar -->
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        Pilih Rombel / Kelas:
                    </label>
                    <select
                        :value="selectedClassroomId"
                        @change="onClassChange"
                        class="rounded-xl border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 py-2 px-3 shadow-sm"
                    >
                        <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">
                            Kelas {{ cls.name }} ({{ cls.department }})
                        </option>
                    </select>
                </div>

                <!-- Mobile Day Tabs -->
                <div class="flex sm:hidden overflow-x-auto gap-1 pb-1">
                    <button
                        v-for="d in daysConfig"
                        :key="d.key"
                        @click="selectedDayTab = d.key"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                        :class="selectedDayTab === d.key
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300'"
                    >
                        {{ d.label }}
                    </button>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <span class="font-bold text-gray-900 dark:text-white">{{ selectedClassroom ? selectedClassroom.name : 'Kelas Terpilih' }}</span>
                    <span>•</span>
                    <span>{{ selectedClassroom ? selectedClassroom.department : 'Jurusan' }}</span>
                </div>
            </div>

            <!-- Print Header -->
            <div class="hidden print:block text-center mb-6">
                <h2 class="text-xl font-bold text-black uppercase">Jadwal Pelajaran Mingguan</h2>
                <p class="text-sm text-gray-700">Kelas: {{ selectedClassroom ? selectedClassroom.name : '' }} • Tahun Ajaran {{ academicYear }}</p>
            </div>

            <!-- 3. Desktop Weekly Matrix (6 Kolom: Senin - Sabtu) -->
            <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 print:grid print:grid-cols-6">
                <div
                    v-for="day in daysConfig"
                    :key="day.key"
                    class="rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 shadow-sm flex flex-col overflow-hidden group/col"
                >
                    <!-- Day Header with Quick Plus Button -->
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700 bg-gray-50/70 dark:bg-slate-850 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 dark:text-white">
                                {{ day.label }}
                            </h3>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="day.iconBg">
                                {{ (schedules[day.key] || []).length }}
                            </span>
                        </div>

                        <!-- Plus Button in Header -->
                        <button
                            @click="openModalForDay(day.key)"
                            class="p-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900 text-indigo-600 dark:text-indigo-300 transition print:hidden"
                            title="Tambah jadwal di hari ini"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>

                    <!-- Lesson Cards List -->
                    <div class="p-3 space-y-2.5 flex-1 overflow-y-auto min-h-[300px] flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <div
                                v-for="(item, idx) in schedules[day.key] || []"
                                :key="idx"
                                class="relative group p-3 rounded-xl border border-gray-200/70 dark:border-slate-700/60 bg-gray-50/50 dark:bg-slate-900/50 hover:border-indigo-500/50 dark:hover:border-indigo-500/50 transition hover:shadow-sm"
                            >
                                <!-- Time Slot & Code -->
                                <div class="flex items-center justify-between gap-1 mb-1.5">
                                    <span class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 font-mono text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ item.time }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <span
                                            class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded"
                                            :class="{
                                                'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300': item.subject_type === 'kejuruan',
                                                'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300': item.subject_type === 'umum',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300': item.subject_type === 'mulok',
                                            }"
                                        >
                                            {{ item.subject_code }}
                                        </span>

                                        <!-- Delete Action Button (visible on hover) -->
                                        <button
                                            v-if="item.id && !isMockup"
                                            @click="deleteSchedule(item.id, item.subject)"
                                            class="opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-rose-100 text-rose-500 transition print:hidden"
                                            title="Hapus jadwal ini"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Subject Name -->
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white leading-snug">
                                    {{ item.subject }}
                                </h4>

                                <!-- Teacher -->
                                <p class="text-[11px] text-gray-600 dark:text-gray-300 mt-1 flex items-center gap-1.5">
                                    <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="truncate">{{ item.teacher }}</span>
                                </p>

                                <!-- Room / Location -->
                                <div class="mt-2 pt-2 border-t border-gray-200/50 dark:border-slate-700/40 text-[10px] text-gray-500 dark:text-gray-400 flex items-center justify-between">
                                    <span class="truncate">📍 {{ item.room }}</span>
                                    <span class="font-mono text-[9px] text-slate-400">Jam ke-{{ item.order }}</span>
                                </div>
                            </div>

                            <!-- Empty Day State -->
                            <div v-if="!schedules[day.key] || schedules[day.key].length === 0" class="py-6 text-center text-xs text-gray-400 dark:text-gray-500">
                                Belum ada jadwal
                            </div>
                        </div>

                        <!-- Plus Add Button at Bottom of Column -->
                        <button
                            @click="openModalForDay(day.key)"
                            class="w-full mt-3 py-2.5 px-3 rounded-xl border border-dashed border-gray-300 dark:border-slate-700 hover:border-indigo-500 dark:hover:border-indigo-500 text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-300 text-xs font-bold flex items-center justify-center gap-1.5 transition bg-white/40 dark:bg-slate-800/40 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 print:hidden"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah ({{ day.label }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Mobile Day Card View -->
            <div class="sm:hidden print:hidden space-y-4">
                <div class="rounded-2xl bg-white dark:bg-slate-800 border border-gray-200/80 dark:border-slate-700/80 p-4 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-black uppercase text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            Jadwal Hari {{ daysConfig.find(d => d.key === selectedDayTab)?.label }}
                        </h3>

                        <button
                            @click="openModalForDay(selectedDayTab)"
                            class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold flex items-center gap-1 shadow"
                        >
                            + Tambah
                        </button>
                    </div>

                    <div
                        v-for="(item, idx) in schedules[selectedDayTab] || []"
                        :key="idx"
                        class="p-4 rounded-xl border border-gray-200/70 dark:border-slate-700/60 bg-gray-50/50 dark:bg-slate-900/50 space-y-2 relative"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                                {{ item.time }}
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">
                                    {{ item.subject_type }}
                                </span>
                                <button
                                    v-if="item.id && !isMockup"
                                    @click="deleteSchedule(item.id, item.subject)"
                                    class="text-rose-500 p-1"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>

                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ item.subject }}</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-300">👨‍🏫 {{ item.teacher }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">📍 {{ item.room }} (Jam ke-{{ item.order }})</p>
                    </div>

                    <div v-if="!schedules[selectedDayTab] || schedules[selectedDayTab].length === 0" class="py-6 text-center text-xs text-gray-400">
                        Tidak ada kegiatan belajar mengajar pada hari ini.
                    </div>
                </div>
            </div>

            <!-- ===== 5. MODAL INTERAKTIF: INPUT JADWAL PELAJARAN ===== -->
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            >
                <div class="relative w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between bg-gray-50/70 dark:bg-slate-850">
                        <div>
                            <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-indigo-600 text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                </span>
                                Tambah Jadwal Pelajaran
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Kelas: <strong class="text-indigo-600 dark:text-indigo-400">{{ selectedClassroom ? selectedClassroom.name : '-' }}</strong>
                            </p>
                        </div>
                        <button
                            @click="showModal = false"
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form @submit.prevent="submitSchedule" class="p-6 space-y-4">
                        <!-- Hari Pelajaran -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Hari Pelajaran:
                            </label>
                            <div class="grid grid-cols-6 gap-1.5">
                                <button
                                    type="button"
                                    v-for="d in daysConfig"
                                    :key="d.key"
                                    @click="form.day_of_week = d.key"
                                    class="py-1.5 px-2 rounded-xl text-xs font-bold transition text-center"
                                    :class="form.day_of_week === d.key
                                        ? 'bg-indigo-600 text-white shadow'
                                        : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200'"
                                >
                                    {{ d.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Pilihan Mata Pelajaran -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Mata Pelajaran:
                            </label>
                            <select
                                v-model="form.subject_id"
                                required
                                class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500 py-2.5 px-3"
                            >
                                <option value="" disabled>Pilih Mata Pelajaran...</option>
                                <option v-for="sub in subjects" :key="sub.id" :value="sub.id">
                                    [{{ sub.code }}] {{ sub.name }} ({{ sub.type }})
                                </option>
                            </select>
                            <p v-if="subjects.length === 0" class="text-[11px] text-amber-500 mt-1">
                                Belum ada mata pelajaran terdaftar. Silakan buat di Database Master.
                            </p>
                        </div>

                        <!-- Pilihan Guru Pengajar -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Guru Pengajar:
                            </label>
                            <select
                                v-model="form.teacher_user_id"
                                required
                                class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500 py-2.5 px-3"
                            >
                                <option value="" disabled>Pilih Guru Pengajar...</option>
                                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                    {{ teacher.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Preset Jam Pelajaran Pintar (Template) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    Preset Slot Jam Pelajaran:
                                </label>
                                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">
                                    Klik salah satu untuk isi otomatis
                                </span>
                            </div>
                            <div class="grid grid-cols-5 gap-1.5">
                                <button
                                    type="button"
                                    v-for="p in presetTimeSlots"
                                    :key="p.order"
                                    @click="applyPreset(p)"
                                    class="p-1.5 rounded-xl border text-center transition"
                                    :class="form.order === p.order
                                        ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-500 text-emerald-700 dark:text-emerald-300 font-black'
                                        : 'bg-gray-50 dark:bg-slate-800 border-gray-200 dark:border-slate-700 text-gray-600 dark:text-gray-400 hover:border-gray-400'"
                                >
                                    <div class="text-[11px] font-bold">{{ p.label }}</div>
                                    <div class="text-[9px] font-mono mt-0.5">{{ p.start }}</div>
                                </button>
                            </div>
                        </div>

                        <!-- Jam Mulai & Selesai Custom -->
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Jam Ke-:
                                </label>
                                <input
                                    v-model.number="form.order"
                                    type="number"
                                    min="1"
                                    max="12"
                                    required
                                    class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-xs font-bold py-2 px-3"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Jam Mulai:
                                </label>
                                <input
                                    v-model="form.start_time"
                                    type="time"
                                    required
                                    class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-xs font-mono font-bold py-2 px-3"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Jam Selesai:
                                </label>
                                <input
                                    v-model="form.end_time"
                                    type="time"
                                    required
                                    class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-xs font-mono font-bold py-2 px-3"
                                />
                            </div>
                        </div>

                        <!-- Ruangan / Laboratorium -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Ruangan / Laboratorium:
                            </label>
                            <input
                                v-model="form.room"
                                type="text"
                                placeholder="Contoh: Lab Komputer 1, Ruang 101"
                                class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2 px-3"
                            />
                        </div>

                        <!-- Submit Action Buttons -->
                        <div class="pt-3 border-t border-gray-200 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 text-xs font-semibold transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/25 transition disabled:opacity-50"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Jadwal Pelajaran' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
