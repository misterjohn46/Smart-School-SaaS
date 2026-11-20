<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';

const props = defineProps({
    leaves: {
        type: Array,
        default: () => [],
    },
    canReview: {
        type: Boolean,
        default: false,
    },
    allLeaves: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, pending: 0, approved: 0, rejected: 0 }),
    },
    filters: {
        type: Object,
        default: () => ({ status: '', type: '', search: '' }),
    },
});

const activeTab = ref(props.canReview ? 'review' : 'my_leaves');
const showForm = ref(false);

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

const form = useForm({
    type: 'izin',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    reason: '',
});

const submitLeave = () => {
    form.post(route('attendance.leave.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

// Filter Antrean Verifikasi
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const typeFilter = ref(props.filters?.type || '');

let filterTimeout = null;
const applyFilters = () => {
    if (!props.canReview) return;
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(
            route('attendance.leave.index'),
            {
                search: search.value || undefined,
                status: statusFilter.value || undefined,
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

watch([search, statusFilter, typeFilter], () => {
    applyFilters();
});

const reviewLeave = (leaveItem, decision) => {
    const label = decision === 'approved' ? 'MENYETUJUI' : 'MENOLAK';
    if (!confirm(`Apakah Anda yakin ingin ${label} permohonan ${leaveItem.type.replace('_', ' ')} dari ${leaveItem.applicant_name}?`)) {
        return;
    }

    router.post(
        route('attendance.leave.review', leaveItem.id),
        { status: decision },
        { preserveScroll: true }
    );
};
</script>

<template>
    <Head title="Pengajuan & Verifikasi Izin / Sakit (E-Leave)" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex">
        <!-- Sidebar jika Staf / Guru / Admin -->
        <Sidebar v-if="canReview" :menus="adminMenus" sectionTitle="Operasional Sekolah" />

        <div :class="['flex-1 flex flex-col min-w-0', canReview ? 'lg:pl-64' : '']">
            <!-- Top Header Bar -->
            <header class="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        v-if="!canReview"
                        :href="route('dashboard')"
                        class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white transition text-xs flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Dashboard
                    </Link>
                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-base sm:text-lg text-white leading-tight">
                            {{ canReview ? 'Manajemen & Verifikasi E-Leave (Izin / Sakit)' : 'Pengajuan Izin & Sakit (E-Leave)' }}
                        </h1>
                        <p class="text-xs text-slate-400 hidden sm:block">
                            {{ canReview ? 'Persetujuan surat izin siswa/guru dengan sinkronisasi otomatis ke buku presensi harian' : 'Formulir permohonan ketidakhadiran resmi terintegrasi buku presensi' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="showForm = !showForm; if (showForm) activeTab = 'my_leaves';"
                        class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-xs text-white shadow-md transition inline-flex items-center gap-1.5"
                    >
                        <span>{{ showForm ? '✕ Tutup Form' : '+ Ajukan Izin Baru' }}</span>
                    </button>
                </div>
            </header>

            <main :class="['p-6 space-y-6 flex-1', !canReview ? 'max-w-3xl mx-auto w-full' : '']">
                <!-- Flash Banner -->
                <div v-if="$page.props.flash?.status" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $page.props.flash.status }}</span>
                </div>

                <!-- Tab Switcher (Hanya muncul untuk Reviewer) -->
                <div v-if="canReview" class="flex items-center gap-2 border-b border-slate-800 pb-3">
                    <button
                        type="button"
                        @click="activeTab = 'review'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                            activeTab === 'review'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                        ]"
                    >
                        <span>📋 Antrean Verifikasi Sekolah</span>
                        <span v-if="stats.pending > 0" class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/40">
                            {{ stats.pending }} Pending
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'my_leaves'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition',
                            activeTab === 'my_leaves'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                        ]"
                    >
                        📝 Pengajuan Mandiri Saya ({{ leaves.length }})
                    </button>
                </div>

                <!-- ==================== TAB 1: ANTREAN VERIFIKASI (STAF/GURU/ADMIN) ==================== -->
                <div v-if="canReview && activeTab === 'review'" class="space-y-6">
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Permohonan</span>
                            <div class="text-2xl font-black text-white mt-2">{{ stats.total }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Seluruh pengajuan E-Leave</div>
                        </div>

                        <div class="bg-slate-900/60 border border-amber-500/20 rounded-2xl p-5">
                            <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Menunggu Verifikasi</span>
                            <div class="text-2xl font-black text-amber-400 mt-2">{{ stats.pending }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Perlu persetujuan wali kelas / piket</div>
                        </div>

                        <div class="bg-slate-900/60 border border-emerald-500/20 rounded-2xl p-5">
                            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Telah Disetujui</span>
                            <div class="text-2xl font-black text-emerald-400 mt-2">{{ stats.approved }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Tersinkronisasi ke presensi</div>
                        </div>

                        <div class="bg-slate-900/60 border border-rose-500/20 rounded-2xl p-5">
                            <span class="text-xs font-semibold text-rose-400 uppercase tracking-wider">Ditolak</span>
                            <div class="text-2xl font-black text-rose-400 mt-2">{{ stats.rejected }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Permohonan tidak disetujui</div>
                        </div>
                    </div>

                    <!-- Filter Bar -->
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-wrap items-center gap-3">
                        <div class="relative flex-1 min-w-[220px]">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama pemohon, NISN, atau alasan izin..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 pl-9"
                            />
                            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <select
                            v-model="statusFilter"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Review</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                        </select>

                        <select
                            v-model="typeFilter"
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Semua Jenis</option>
                            <option value="izin">Izin Keperluan</option>
                            <option value="sakit">Sakit</option>
                            <option value="tugas_luar">Tugas Luar</option>
                        </select>
                    </div>

                    <!-- Tabel Antrean Verifikasi E-Leave -->
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-300">
                                <thead class="bg-slate-950/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                    <tr>
                                        <th class="px-6 py-4">Pemohon & Kelas</th>
                                        <th class="px-6 py-4">Jenis & Periode</th>
                                        <th class="px-6 py-4">Keterangan / Alasan</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-right">Tindakan Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <tr v-for="item in allLeaves" :key="item.id" class="hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-white">{{ item.applicant_name }}</div>
                                            <div class="text-xs text-slate-400 mt-0.5">
                                                {{ item.applicant_identifier }} • <span class="text-indigo-400 font-semibold">{{ item.classroom_name }}</span>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase"
                                                :class="{
                                                    'bg-rose-500/15 text-rose-300 border border-rose-500/30': item.type === 'sakit',
                                                    'bg-indigo-500/15 text-indigo-300 border border-indigo-500/30': item.type === 'izin',
                                                    'bg-amber-500/15 text-amber-300 border border-amber-500/30': item.type === 'tugas_luar',
                                                }"
                                            >
                                                {{ item.type.replace('_', ' ') }} ({{ item.days_count }} Hari)
                                            </span>
                                            <div class="text-xs text-slate-400 mt-1">
                                                {{ item.start_date_formatted }} s/d {{ item.end_date_formatted }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 max-w-md">
                                            <p class="text-xs text-slate-200 leading-relaxed">{{ item.reason }}</p>
                                            <span class="text-[10px] text-slate-500">Diajukan {{ item.created_at_formatted }}</span>
                                        </td>

                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                            <span
                                                class="px-2.5 py-1 rounded-full text-xs font-bold uppercase"
                                                :class="{
                                                    'bg-amber-500/15 text-amber-300 border border-amber-500/30': item.status === 'pending',
                                                    'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30': item.status === 'approved',
                                                    'bg-rose-500/15 text-rose-300 border border-rose-500/30': item.status === 'rejected',
                                                }"
                                            >
                                                {{ item.status === 'approved' ? 'Disetujui' : (item.status === 'rejected' ? 'Ditolak' : 'Menunggu') }}
                                            </span>
                                            <div v-if="item.approver_name" class="text-[10px] text-slate-400 mt-1">
                                                Oleh: {{ item.approver_name }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div v-if="item.status === 'pending'" class="inline-flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    @click="reviewLeave(item, 'approved')"
                                                    class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow transition"
                                                >
                                                    ✓ Setujui
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="reviewLeave(item, 'rejected')"
                                                    class="px-3 py-1.5 rounded-xl bg-rose-500/15 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 text-xs font-bold transition"
                                                >
                                                    ✕ Tolak
                                                </button>
                                            </div>
                                            <span v-else class="text-xs text-slate-500 italic">Telah Diproses</span>
                                        </td>
                                    </tr>

                                    <tr v-if="allLeaves.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                            Belum ada permohonan izin atau sakit yang sesuai dengan filter.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB 2 / SELF-SERVICE: PENGAJUAN MANDIRI ==================== -->
                <div v-if="!canReview || activeTab === 'my_leaves'" class="space-y-5">
                    <!-- Formulir Pengajuan -->
                    <div v-if="showForm" class="rounded-2xl bg-slate-900 p-6 border border-indigo-500/30 shadow-xl space-y-4">
                        <h2 class="text-sm font-bold text-white border-b border-slate-800 pb-2.5">
                            Formulir Surat Permohonan Izin / Sakit
                        </h2>

                        <form @submit.prevent="submitLeave" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1.5">Jenis Ketidakhadiran</label>
                                <div class="grid grid-cols-3 gap-2.5">
                                    <button
                                        type="button"
                                        @click="form.type = 'izin'"
                                        :class="['py-2.5 text-xs font-semibold rounded-xl border transition', form.type === 'izin' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-300']"
                                    >
                                        📝 Izin
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.type = 'sakit'"
                                        :class="['py-2.5 text-xs font-semibold rounded-xl border transition', form.type === 'sakit' ? 'bg-rose-600 border-rose-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-300']"
                                    >
                                        🏥 Sakit
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.type = 'tugas_luar'"
                                        :class="['py-2.5 text-xs font-semibold rounded-xl border transition', form.type === 'tugas_luar' ? 'bg-amber-600 border-amber-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-300']"
                                    >
                                        💼 Tugas Luar
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-300 mb-1">Tanggal Mulai</label>
                                    <input
                                        type="date"
                                        v-model="form.start_date"
                                        required
                                        class="w-full rounded-xl bg-slate-800 border border-slate-700 text-xs text-white p-2.5 focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-300 mb-1">Sampai Tanggal</label>
                                    <input
                                        type="date"
                                        v-model="form.end_date"
                                        required
                                        class="w-full rounded-xl bg-slate-800 border border-slate-700 text-xs text-white p-2.5 focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Keterangan / Alasan Lengkap</label>
                                <textarea
                                    v-model="form.reason"
                                    rows="3"
                                    placeholder="Tuliskan keterangan keperluan izin atau keluhan sakit..."
                                    required
                                    class="w-full rounded-xl bg-slate-800 border border-slate-700 text-xs text-white p-3 focus:border-indigo-500"
                                ></textarea>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button
                                    type="button"
                                    @click="showForm = false"
                                    class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-xs text-white shadow-lg transition"
                                >
                                    {{ form.processing ? 'Mengirim...' : 'Kirim Permohonan Izin' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Daftar Riwayat Pengajuan Mandiri -->
                    <div class="rounded-2xl bg-slate-900/80 p-5 border border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Riwayat Pengajuan Anda
                            </h3>
                            <button
                                v-if="!showForm"
                                type="button"
                                @click="showForm = true"
                                class="text-xs font-semibold text-indigo-400 hover:text-indigo-300"
                            >
                                + Buat Permohonan Baru
                            </button>
                        </div>

                        <div v-if="leaves.length === 0" class="py-8 text-center text-xs text-slate-500">
                            Belum ada riwayat permohonan izin / sakit pribadi.
                        </div>

                        <div v-else class="space-y-2.5">
                            <div
                                v-for="leave in leaves"
                                :key="leave.id"
                                class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-white uppercase flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full" :class="{
                                            'bg-rose-500': leave.type === 'sakit',
                                            'bg-amber-500': leave.type === 'izin',
                                            'bg-blue-500': leave.type === 'tugas_luar'
                                        }"></span>
                                        {{ leave.type.replace('_', ' ') }}
                                    </span>
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                        :class="{
                                            'bg-amber-500/20 text-amber-300 border border-amber-500/30': leave.status === 'pending',
                                            'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': leave.status === 'approved',
                                            'bg-rose-500/20 text-rose-300 border border-rose-500/30': leave.status === 'rejected'
                                        }"
                                    >
                                        {{ leave.status === 'approved' ? 'Disetujui' : (leave.status === 'rejected' ? 'Ditolak' : 'Menunggu Review') }}
                                    </span>
                                </div>

                                <p class="text-xs text-slate-300 leading-snug">{{ leave.reason }}</p>

                                <div class="flex justify-between items-center text-[10px] text-slate-500 pt-1 border-t border-slate-800/60">
                                    <span>Periode: {{ leave.start_date }} s/d {{ leave.end_date }}</span>
                                    <span v-if="leave.approver">Oleh: {{ leave.approver.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
