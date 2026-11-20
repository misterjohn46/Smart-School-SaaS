<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    registrations: Object,
    batches: Array,
    departments: Array,
    classrooms: Array,
    academicYears: Array,
    filters: Object,
    stats: Object,
});

// Admin Sidebar Navigation
const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
    { label: 'Materi & Tugas (LMS)', route: 'academic.learning.index', icon: 'book-open' },
    { label: 'CBT Ujian Online', route: 'academic.cbt.index', icon: 'desktop' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Buku Rapor & Leger', route: 'academic.reports.index', icon: 'document' },
    { label: 'PPDB Murid Baru', route: 'academic.ppdb.index', icon: 'users' },
    { label: 'Tagihan & SPP Massal', route: 'finance.bills.index', icon: 'credit-card' },
    { label: 'POS Kasir SPP', route: 'finance.pos', icon: 'credit-card' },
    { label: 'Jadwal Pelajaran', route: 'academic.schedules', icon: 'calendar' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'Tata Tertib & BK', route: 'counseling.violations.index', icon: 'clipboard-check' },
    { label: 'Cetak Massal KTS', route: 'kts.batch', icon: 'id-card' },
    { label: 'Radius GPS & Shift', route: 'attendance.settings.index', icon: 'location' },
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

const activeTab = ref('registrations'); // 'registrations' | 'batches'

// Filter state
const search = ref(props.filters.search || '');
const selectedBatch = ref(props.filters.batch_id || 'all');
const selectedStatus = ref(props.filters.status || 'all');
const selectedTrack = ref(props.filters.track || 'all');
const selectedDepartment = ref(props.filters.department_id || 'all');

const applyFilter = () => {
    router.get(route('academic.ppdb.index'), {
        search: search.value,
        batch_id: selectedBatch.value,
        status: selectedStatus.value,
        track: selectedTrack.value,
        department_id: selectedDepartment.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilter = () => {
    search.value = '';
    selectedBatch.value = 'all';
    selectedStatus.value = 'all';
    selectedTrack.value = 'all';
    selectedDepartment.value = 'all';
    applyFilter();
};

// Modals
const isBatchModalOpen = ref(false);
const isEditingBatch = ref(false);
const editingBatchId = ref(null);

const batchForm = useForm({
    academic_year_id: props.academicYears.length > 0 ? props.academicYears[0].id : '',
    name: '',
    start_date: '',
    end_date: '',
    announcement_date: '',
    registration_fee: 0,
    quota: 100,
    is_active: true,
    description: '',
});

const openCreateBatchModal = () => {
    isEditingBatch.value = false;
    editingBatchId.value = null;
    batchForm.reset();
    if (props.academicYears.length > 0) {
        batchForm.academic_year_id = props.academicYears[0].id;
    }
    batchForm.registration_fee = 0;
    batchForm.quota = 100;
    batchForm.is_active = true;
    isBatchModalOpen.value = true;
};

const openEditBatchModal = (batch) => {
    isEditingBatch.value = true;
    editingBatchId.value = batch.id;
    batchForm.academic_year_id = batch.academic_year_id;
    batchForm.name = batch.name;
    batchForm.start_date = batch.start_date ? batch.start_date.substring(0, 10) : '';
    batchForm.end_date = batch.end_date ? batch.end_date.substring(0, 10) : '';
    batchForm.announcement_date = batch.announcement_date ? batch.announcement_date.substring(0, 10) : '';
    batchForm.registration_fee = batch.registration_fee;
    batchForm.quota = batch.quota;
    batchForm.is_active = Boolean(batch.is_active);
    batchForm.description = batch.description || '';
    isBatchModalOpen.value = true;
};

const submitBatch = () => {
    if (isEditingBatch.value && editingBatchId.value) {
        batchForm.put(route('academic.ppdb.batches.update', editingBatchId.value), {
            onSuccess: () => isBatchModalOpen.value = false,
        });
    } else {
        batchForm.post(route('academic.ppdb.batches.store'), {
            onSuccess: () => isBatchModalOpen.value = false,
        });
    }
};

const deleteBatch = (batch) => {
    if (confirm(`Yakin ingin menghapus gelombang '${batch.name}'? Seluruh data pendaftar di dalamnya akan ikut terhapus.`)) {
        router.delete(route('academic.ppdb.batches.destroy', batch.id));
    }
};

// Modal Verifikasi Berkas
const isVerifyModalOpen = ref(false);
const selectedRegForVerify = ref(null);
const verifyForm = useForm({
    status: 'verified',
    rejection_reason: '',
    verification_notes: '',
});

const openVerifyModal = (reg) => {
    selectedRegForVerify.value = reg;
    verifyForm.status = 'verified';
    verifyForm.rejection_reason = reg.rejection_reason || '';
    verifyForm.verification_notes = reg.verification_notes || '';
    isVerifyModalOpen.value = true;
};

const submitVerify = () => {
    if (!selectedRegForVerify.value) return;
    verifyForm.post(route('academic.ppdb.registrations.verify', selectedRegForVerify.value.id), {
        onSuccess: () => isVerifyModalOpen.value = false,
    });
};

// Modal Keputusan Seleksi
const isSelectionModalOpen = ref(false);
const selectedRegForSelection = ref(null);
const selectionForm = useForm({
    status: 'accepted',
});

const openSelectionModal = (reg) => {
    selectedRegForSelection.value = reg;
    selectionForm.status = reg.status === 'accepted' ? 'accepted' : 'accepted';
    isSelectionModalOpen.value = true;
};

const submitSelection = () => {
    if (!selectedRegForSelection.value) return;
    selectionForm.post(route('academic.ppdb.registrations.selection', selectedRegForSelection.value.id), {
        onSuccess: () => isSelectionModalOpen.value = false,
    });
};

// Modal Konversi Siswa Resmi (Enrollment)
const isEnrollModalOpen = ref(false);
const selectedRegForEnroll = ref(null);
const enrollForm = useForm({
    classroom_id: props.classrooms.length > 0 ? props.classrooms[0].id : '',
    password: 'password',
});

const openEnrollModal = (reg) => {
    selectedRegForEnroll.value = reg;
    if (props.classrooms.length > 0) {
        enrollForm.classroom_id = props.classrooms[0].id;
    }
    enrollForm.password = 'password';
    isEnrollModalOpen.value = true;
};

const submitEnroll = () => {
    if (!selectedRegForEnroll.value) return;
    enrollForm.post(route('academic.ppdb.registrations.enroll', selectedRegForEnroll.value.id), {
        onSuccess: () => isEnrollModalOpen.value = false,
    });
};

// Modal Konversi Massal (Batch Enrollment)
const isBatchEnrollModalOpen = ref(false);
const selectedRegIds = ref([]);
const batchEnrollForm = useForm({
    registration_ids: [],
    classroom_id: props.classrooms.length > 0 ? props.classrooms[0].id : '',
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedRegIds.value = props.registrations.data.filter(r => r.status === 'accepted').map(r => r.id);
    } else {
        selectedRegIds.value = [];
    }
};

const openBatchEnrollModal = () => {
    if (selectedRegIds.value.length === 0) {
        alert('Pilih setidaknya 1 calon siswa berstatus DITERIMA untuk dikonversi massal.');
        return;
    }
    batchEnrollForm.registration_ids = [...selectedRegIds.value];
    if (props.classrooms.length > 0) {
        batchEnrollForm.classroom_id = props.classrooms[0].id;
    }
    isBatchEnrollModalOpen.value = true;
};

const submitBatchEnroll = () => {
    batchEnrollForm.post(route('academic.ppdb.registrations.batch-enroll'), {
        onSuccess: () => {
            isBatchEnrollModalOpen.value = false;
            selectedRegIds.value = [];
        },
    });
};

// Modal Detail Calon Siswa
const isDetailModalOpen = ref(false);
const activeDetailReg = ref(null);
const openDetailModal = (reg) => {
    activeDetailReg.value = reg;
    isDetailModalOpen.value = true;
};

const copyCredentials = (cred) => {
    const text = `Akun Siswa Baru Sekolah:\nNama: ${cred.name}\nEmail: ${cred.email}\nPassword: ${cred.password}\nNISN: ${cred.nisn}\nKelas: ${cred.classroom}`;
    navigator.clipboard.writeText(text);
    alert('Kredensial login siswa berhasil disalin ke clipboard!');
};
</script>

<template>
    <Head title="Manajemen PPDB Online & Calon Siswa Baru" />

    <AuthenticatedLayout :menus="adminMenus" section-title="Sistem Sekolah">
        <div class="p-6 sm:p-8 max-w-7xl mx-auto space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>🎓</span> Manajemen PPDB & Calon Siswa Baru
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Verifikasi berkas calon peserta didik baru, seleksi kelulusan, dan konversi 1-klik menjadi siswa resmi.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <a
                        :href="route('academic.ppdb.export')"
                        class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs"
                        title="Ekspor Seluruh Pendaftar ke CSV"
                    >
                        <span>📥</span> Ekspor CSV
                    </a>
                    <a
                        :href="route('ppdb.public.index')"
                        target="_blank"
                        class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                        title="Lihat Tampilan Website Publik Calon Siswa"
                    >
                        <span>🌐</span> Portal Publik
                    </a>
                </div>
            </div>

            <!-- Flash Alert Kredensial Siswa Hasil Konversi -->
            <div v-if="$page.props.flash?.enrolled_credential" class="p-5 bg-emerald-50 border border-emerald-300 rounded-3xl space-y-3 shadow-sm animate-in fade-in duration-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">🎉</span>
                        <div>
                            <h3 class="font-black text-emerald-900 text-sm">Siswa Baru Berhasil Dikonversi!</h3>
                            <p class="text-xs text-emerald-700">Akun login, profil siswa, dan e-wallet tabungan otomatis diinisialisasi.</p>
                        </div>
                    </div>
                    <button
                        @click="copyCredentials($page.props.flash.enrolled_credential)"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5"
                    >
                        <span>📋</span> Salin Kredensial
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2 text-xs bg-white/70 p-4 rounded-2xl border border-emerald-200">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Nama Siswa</span>
                        <span class="font-bold text-slate-900">{{ $page.props.flash.enrolled_credential.name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Email Login</span>
                        <span class="font-bold text-indigo-600">{{ $page.props.flash.enrolled_credential.email }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Password</span>
                        <span class="font-mono font-bold text-slate-800">{{ $page.props.flash.enrolled_credential.password }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">NISN</span>
                        <span class="font-mono font-bold text-slate-800">{{ $page.props.flash.enrolled_credential.nisn }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Kelas Baru</span>
                        <span class="font-bold text-emerald-700">{{ $page.props.flash.enrolled_credential.classroom }}</span>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Pendaftar</span>
                    <span class="text-2xl font-black text-slate-900 mt-1 block">{{ stats.total }}</span>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-2xs">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Pending Berkas</span>
                    <span class="text-2xl font-black text-amber-700 mt-1 block">{{ stats.pending }}</span>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-blue-200 bg-blue-50/20 shadow-2xs">
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Berkas Valid</span>
                    <span class="text-2xl font-black text-blue-700 mt-1 block">{{ stats.verified }}</span>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-emerald-200 bg-emerald-50/20 shadow-2xs">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Diterima / Lolos</span>
                    <span class="text-2xl font-black text-emerald-700 mt-1 block">{{ stats.accepted }}</span>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-rose-200 bg-rose-50/20 shadow-2xs">
                    <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Ditolak</span>
                    <span class="text-2xl font-black text-rose-700 mt-1 block">{{ stats.rejected }}</span>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-purple-200 bg-purple-50/20 shadow-2xs">
                    <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider block">Resmi Masuk Kelas</span>
                    <span class="text-2xl font-black text-purple-700 mt-1 block">{{ stats.enrolled }}</span>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-200">
                <button
                    @click="activeTab = 'registrations'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'registrations'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>👥</span> Data Pendaftar & Seleksi ({{ registrations.total || 0 }})
                </button>
                <button
                    @click="activeTab = 'batches'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'batches'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>📅</span> Gelombang Pendaftaran ({{ batches.length }})
                </button>
            </div>

            <!-- TAB 1: Pendaftar & Seleksi Siswa -->
            <div v-if="activeTab === 'registrations'" class="space-y-4">
                <!-- Filter Bar -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilter"
                            type="text"
                            placeholder="Cari Nama / No. Reg / NISN / Asal Sekolah..."
                            class="px-3.5 py-1.5 border border-slate-300 rounded-xl text-xs w-64 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        />

                        <select v-model="selectedBatch" @change="applyFilter" class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                            <option value="all">Semua Gelombang</option>
                            <option v-for="b in batches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>

                        <select v-model="selectedTrack" @change="applyFilter" class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                            <option value="all">Semua Jalur</option>
                            <option value="zonasi">Zonasi</option>
                            <option value="prestasi">Prestasi</option>
                            <option value="afirmasi">Afirmasi</option>
                            <option value="reguler">Reguler</option>
                            <option value="pindahan">Pindahan</option>
                        </select>

                        <select v-model="selectedStatus" @change="applyFilter" class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                            <option value="all">Semua Status</option>
                            <option value="pending">Pending Berkas</option>
                            <option value="verified">Berkas Valid</option>
                            <option value="accepted">Diterima</option>
                            <option value="rejected">Ditolak</option>
                            <option value="not_accepted">Tidak Lolos</option>
                            <option value="enrolled">Sudah Masuk Kelas</option>
                        </select>

                        <button @click="applyFilter" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900">
                            Filter
                        </button>
                        <button @click="resetFilter" class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-xs hover:bg-slate-200">
                            Reset
                        </button>
                    </div>

                    <button
                        v-if="selectedRegIds.length > 0"
                        @click="openBatchEnrollModal"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-black shadow-xs flex items-center gap-1.5 animate-pulse"
                    >
                        <span>⚡</span> Konversi {{ selectedRegIds.length }} Siswa Diterima
                    </button>
                </div>

                <!-- Table Pendaftar -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3 text-center">
                                        <input type="checkbox" @change="toggleSelectAll" class="rounded accent-indigo-600" />
                                    </th>
                                    <th class="px-4 py-3">No. Pendaftaran & Tgl</th>
                                    <th class="px-4 py-3">Nama Siswa & Identitas</th>
                                    <th class="px-4 py-3">Asal Sekolah & Ortu</th>
                                    <th class="px-4 py-3">Jalur & Pilihan Jurusan</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="reg in registrations.data" :key="reg.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-4 py-3.5 text-center">
                                        <input
                                            type="checkbox"
                                            :value="reg.id"
                                            v-model="selectedRegIds"
                                            :disabled="reg.status !== 'accepted'"
                                            class="rounded accent-indigo-600 disabled:opacity-30"
                                        />
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="font-mono font-black text-slate-900 block">{{ reg.registration_number }}</span>
                                        <span class="text-[10px] text-slate-400 block">{{ new Date(reg.created_at).toLocaleDateString('id-ID') }}</span>
                                        <span class="text-[10px] text-indigo-600 font-semibold">{{ reg.batch?.name }}</span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                                            <span>{{ reg.full_name }}</span>
                                            <span class="text-[10px] px-1.5 py-0.2 rounded font-bold" :class="reg.gender === 'L' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'">
                                                {{ reg.gender }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">NISN: {{ reg.nisn || '-' }} | NIK: {{ reg.nik || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="font-medium text-slate-800">{{ reg.previous_school }}</div>
                                        <div class="text-[11px] text-slate-400">Ortu: {{ reg.parent_name }} ({{ reg.parent_phone }})</div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                            {{ reg.track }}
                                        </span>
                                        <div class="text-xs font-semibold text-slate-800 mt-1">{{ reg.department?.name || 'Pilihan Umum' }}</div>
                                        <div v-if="reg.average_report_score" class="text-[10px] text-amber-600 font-bold">Rata Rapor: {{ reg.average_report_score }}</div>
                                        <div v-if="reg.distance_meters" class="text-[10px] text-blue-600 font-bold">Jarak: {{ reg.distance_meters }} m</div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span
                                            :class="[
                                                'px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider inline-block',
                                                reg.status === 'accepted' ? 'bg-emerald-100 text-emerald-800' :
                                                reg.status === 'enrolled' ? 'bg-purple-100 text-purple-800' :
                                                reg.status === 'verified' ? 'bg-blue-100 text-blue-800' :
                                                reg.status === 'rejected' ? 'bg-rose-100 text-rose-800' :
                                                reg.status === 'not_accepted' ? 'bg-slate-100 text-slate-600' :
                                                'bg-amber-100 text-amber-800'
                                            ]"
                                        >
                                            {{ reg.status }}
                                        </span>
                                        <span v-if="reg.student_profile?.classroom" class="text-[10px] text-purple-700 font-bold block mt-1">
                                            {{ reg.student_profile.classroom.name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="openDetailModal(reg)"
                                                class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs"
                                                title="Lihat Detail & Berkas"
                                            >
                                                👁️
                                            </button>

                                            <button
                                                v-if="reg.status === 'pending' || reg.status === 'rejected'"
                                                @click="openVerifyModal(reg)"
                                                class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold"
                                                title="Verifikasi Berkas"
                                            >
                                                Verifikasi
                                            </button>

                                            <button
                                                v-if="reg.status === 'verified'"
                                                @click="openSelectionModal(reg)"
                                                class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold"
                                                title="Tentukan Kelulusan Seleksi"
                                            >
                                                Seleksi
                                            </button>

                                            <button
                                                v-if="reg.status === 'accepted'"
                                                @click="openEnrollModal(reg)"
                                                class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black shadow-xs flex items-center gap-1"
                                                title="Konversi Otomatis ke Siswa Resmi"
                                            >
                                                <span>🎓</span> Jadikan Siswa
                                            </button>

                                            <Link
                                                :href="route('ppdb.public.proof', reg.registration_number)"
                                                target="_blank"
                                                class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs"
                                                title="Cetak Bukti Pendaftaran"
                                            >
                                                🖨️
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="registrations.data.length === 0">
                                    <td colspan="7" class="text-center py-10 text-slate-400 text-xs">
                                        Tidak ada data pendaftar yang sesuai filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Manajemen Gelombang PPDB -->
            <div v-if="activeTab === 'batches'" class="space-y-4">
                <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200">
                    <span class="text-xs font-bold text-slate-600">Daftar Gelombang Pendaftaran Dibuka</span>
                    <button
                        @click="openCreateBatchModal"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5"
                    >
                        <span>+</span> Buka Gelombang Baru
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="batch in batches"
                        :key="batch.id"
                        class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between space-y-4"
                    >
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700">
                                    {{ batch.academic_year?.name }}
                                </span>
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-full text-[10px] font-bold',
                                        batch.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'
                                    ]"
                                >
                                    {{ batch.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            <h3 class="font-black text-slate-900 text-base mt-2">{{ batch.name }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ batch.description || 'Tidak ada catatan.' }}</p>

                            <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                                <div class="flex justify-between">
                                    <span>Periode:</span>
                                    <span class="font-bold text-slate-800">{{ batch.start_date }} s/d {{ batch.end_date }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Pengumuman:</span>
                                    <span class="font-bold text-indigo-600">{{ batch.announcement_date || '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Kuota:</span>
                                    <span class="font-bold text-slate-900">{{ batch.quota }} Siswa</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Biaya Formulir:</span>
                                    <span class="font-bold text-slate-900">{{ batch.registration_fee > 0 ? `Rp ${batch.registration_fee.toLocaleString('id-ID')}` : 'Gratis' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                            <button
                                @click="openEditBatchModal(batch)"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteBatch(batch)"
                                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-semibold"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL GELOMBANG (BUAT/EDIT) -->
        <div v-if="isBatchModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">
                        {{ isEditingBatch ? 'Edit Gelombang PPDB' : 'Buka Gelombang PPDB Baru' }}
                    </h3>
                    <button @click="isBatchModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitBatch" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tahun Ajaran Target</label>
                        <select v-model="batchForm.academic_year_id" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs">
                            <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Gelombang</label>
                        <input v-model="batchForm.name" type="text" placeholder="Contoh: Gelombang 1 - Jalur Prestasi & Zonasi" required class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai Buka</label>
                            <input v-model="batchForm.start_date" type="date" required class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Akhir Tutup</label>
                            <input v-model="batchForm.end_date" type="date" required class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Pengumuman</label>
                            <input v-model="batchForm.announcement_date" type="date" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kuota Penerimaan (Siswa)</label>
                            <input v-model="batchForm.quota" type="number" min="1" required class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Biaya Pendaftaran Formulir (Rp)</label>
                        <input v-model="batchForm.registration_fee" type="number" min="0" placeholder="0 untuk gratis" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Deskripsi & Catatan</label>
                        <textarea v-model="batchForm.description" rows="2" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs"></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="batchForm.is_active" id="batchActive" class="accent-indigo-600 rounded" />
                        <label for="batchActive" class="font-bold text-slate-700">Aktifkan Gelombang Ini</label>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isBatchModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button type="submit" :disabled="batchForm.processing" class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold shadow-xs">
                            Simpan Gelombang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL VERIFIKASI BERKAS -->
        <div v-if="isVerifyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">Verifikasi Berkas Calon Siswa</h3>
                    <button @click="isVerifyModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="bg-slate-50 p-3 rounded-2xl text-xs space-y-1">
                    <p class="font-bold text-slate-800">{{ selectedRegForVerify?.full_name }}</p>
                    <p class="text-slate-500 font-mono">{{ selectedRegForVerify?.registration_number }} | Jalur: {{ selectedRegForVerify?.track }}</p>
                </div>

                <form @submit.prevent="submitVerify" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Hasil Keputusan Verifikasi</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                :class="[
                                    'p-3 rounded-xl border cursor-pointer text-center font-bold transition-all',
                                    verifyForm.status === 'verified' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-500/20' : 'bg-slate-50 border-slate-200'
                                ]"
                            >
                                <input type="radio" v-model="verifyForm.status" value="verified" class="hidden" />
                                ✓ Berkas Valid
                            </label>

                            <label
                                :class="[
                                    'p-3 rounded-xl border cursor-pointer text-center font-bold transition-all',
                                    verifyForm.status === 'rejected' ? 'bg-rose-50 border-rose-500 text-rose-800 ring-2 ring-rose-500/20' : 'bg-slate-50 border-slate-200'
                                ]"
                            >
                                <input type="radio" v-model="verifyForm.status" value="rejected" class="hidden" />
                                ✗ Tolak Berkas
                            </label>
                        </div>
                    </div>

                    <div v-if="verifyForm.status === 'rejected'">
                        <label class="block font-bold text-rose-700 mb-1">Alasan Penolakan / Dokumen Kurang <span class="text-rose-500">*</span></label>
                        <textarea
                            v-model="verifyForm.rejection_reason"
                            rows="2"
                            placeholder="Contoh: Scan Akta Kelahiran buram, mohon unggah ulang."
                            required
                            class="w-full px-3 py-2 border border-rose-300 bg-rose-50/40 rounded-xl text-xs"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan Panitia (Opsional)</label>
                        <input v-model="verifyForm.verification_notes" type="text" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isVerifyModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button type="submit" :disabled="verifyForm.processing" class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold shadow-xs">
                            Simpan Verifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KEPUTUSAN SELEKSI -->
        <div v-if="isSelectionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">Keputusan Seleksi Kelulusan</h3>
                    <button @click="isSelectionModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="bg-slate-50 p-3 rounded-2xl text-xs space-y-1">
                    <p class="font-bold text-slate-800">{{ selectedRegForSelection?.full_name }}</p>
                    <p class="text-slate-500">Pilihan Jurusan: {{ selectedRegForSelection?.department?.name || 'Umum' }}</p>
                </div>

                <form @submit.prevent="submitSelection" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Putusan Kelulusan Siswa</label>
                        <div class="space-y-2">
                            <label
                                :class="[
                                    'p-3 rounded-xl border cursor-pointer block font-bold transition-all',
                                    selectionForm.status === 'accepted' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-500/20' : 'bg-slate-50 border-slate-200'
                                ]"
                            >
                                <input type="radio" v-model="selectionForm.status" value="accepted" class="mr-2" />
                                🎉 DITERIMA (Lolos Seleksi PPDB)
                            </label>

                            <label
                                :class="[
                                    'p-3 rounded-xl border cursor-pointer block font-bold transition-all',
                                    selectionForm.status === 'not_accepted' ? 'bg-rose-50 border-rose-500 text-rose-800 ring-2 ring-rose-500/20' : 'bg-slate-50 border-slate-200'
                                ]"
                            >
                                <input type="radio" v-model="selectionForm.status" value="not_accepted" class="mr-2" />
                                ✗ TIDAK LOLOS SELEKSI
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isSelectionModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button type="submit" :disabled="selectionForm.processing" class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold shadow-xs">
                            Simpan Keputusan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONVERSI SISWA RESMI -->
        <div v-if="isEnrollModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">Konversi ke Siswa Resmi Sekolah</h3>
                    <button @click="isEnrollModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="bg-indigo-50/60 border border-indigo-100 p-3.5 rounded-2xl text-xs text-indigo-900 space-y-1">
                    <p class="font-bold">Otomatisasi Sistem:</p>
                    <p>Sistem akan membuatkan akun User login siswa baru, profil siswa resmi, dan dompet tabungan digital (e-wallet).</p>
                </div>

                <div class="bg-slate-50 p-3 rounded-2xl text-xs space-y-1">
                    <p class="font-bold text-slate-800">{{ selectedRegForEnroll?.full_name }}</p>
                    <p class="text-slate-500">NISN: {{ selectedRegForEnroll?.nisn || 'Dibuat otomatis' }}</p>
                </div>

                <form @submit.prevent="submitEnroll" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih Rombel / Kelas Masuk Siswa <span class="text-rose-500">*</span></label>
                        <select v-model="enrollForm.classroom_id" required class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs">
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }} ({{ c.department?.name || 'Umum' }})</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Password Login Awal</label>
                        <input v-model="enrollForm.password" type="text" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs font-mono" />
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isEnrollModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button type="submit" :disabled="enrollForm.processing" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-black shadow-md flex items-center gap-1.5">
                            <span>🎓</span> Konversi Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONVERSI MASSAL (BATCH ENROLL) -->
        <div v-if="isBatchEnrollModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base">Konversi Massal {{ batchEnrollForm.registration_ids.length }} Siswa</h3>
                    <button @click="isBatchEnrollModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    Seluruh <strong>{{ batchEnrollForm.registration_ids.length }} calon siswa terpilih</strong> akan didaftarkan ke rombel target dengan akun login default (password: <code>siswa123</code>) dan akun e-wallet otomatis.
                </p>

                <form @submit.prevent="submitBatchEnroll" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih Rombel / Kelas Masuk Target <span class="text-rose-500">*</span></label>
                        <select v-model="batchEnrollForm.classroom_id" required class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs">
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }} ({{ c.department?.name || 'Umum' }})</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isBatchEnrollModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button type="submit" :disabled="batchEnrollForm.processing" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-black shadow-md">
                            Konversi Massal Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL DETAIL & PRATINJAU BERKAS -->
        <div v-if="isDetailModalOpen && activeDetailReg" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-black text-slate-900 text-lg">{{ activeDetailReg.full_name }}</h3>
                        <p class="text-xs text-slate-400 font-mono">{{ activeDetailReg.registration_number }} | Jalur {{ activeDetailReg.track }}</p>
                    </div>
                    <button @click="isDetailModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div class="space-y-2">
                        <span class="font-bold text-slate-700 block uppercase tracking-wider text-[10px]">Data Pribadi</span>
                        <p>JK: <strong>{{ activeDetailReg.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</strong></p>
                        <p>NISN: <strong>{{ activeDetailReg.nisn || '-' }}</strong></p>
                        <p>NIK: <strong>{{ activeDetailReg.nik || '-' }}</strong></p>
                        <p>TTL: <strong>{{ activeDetailReg.birth_place }}, {{ activeDetailReg.birth_date }}</strong></p>
                        <p>Asal Sekolah: <strong>{{ activeDetailReg.previous_school }}</strong></p>
                        <p>Kontak: <strong>{{ activeDetailReg.phone_number }}</strong></p>
                        <p>Alamat: <strong>{{ activeDetailReg.address }}</strong></p>
                    </div>

                    <div class="space-y-2">
                        <span class="font-bold text-slate-700 block uppercase tracking-wider text-[10px]">Orang Tua & Peminatan</span>
                        <p>Orang Tua: <strong>{{ activeDetailReg.parent_name }}</strong></p>
                        <p>No HP Ortu: <strong>{{ activeDetailReg.parent_phone }}</strong></p>
                        <p>Pekerjaan: <strong>{{ activeDetailReg.parent_job || '-' }}</strong></p>
                        <p>Jurusan 1: <strong>{{ activeDetailReg.department?.name || 'Umum' }}</strong></p>
                        <p>Jurusan 2: <strong>{{ activeDetailReg.second_department?.name || '-' }}</strong></p>
                        <p v-if="activeDetailReg.distance_meters">Jarak Zonasi: <strong>{{ activeDetailReg.distance_meters }} m</strong></p>
                        <p v-if="activeDetailReg.average_report_score">Rata Rapor: <strong>{{ activeDetailReg.average_report_score }}</strong></p>
                    </div>
                </div>

                <!-- Pratinjau Berkas Dokumen -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <span class="font-bold text-slate-800 text-xs block uppercase tracking-wider text-[10px]">Dokumen Berkas Lampiran</span>
                    <div v-if="activeDetailReg.documents && Object.keys(activeDetailReg.documents).length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div v-for="(path, key) in activeDetailReg.documents" :key="key" class="p-2 border border-slate-200 rounded-xl bg-slate-50 text-center text-xs">
                            <span class="font-bold uppercase text-[10px] text-slate-500 block mb-1">{{ key }}</span>
                            <a :href="`/storage/${path}`" target="_blank" class="text-indigo-600 hover:underline font-bold text-[11px] block truncate">
                                Buka Berkas ↗
                            </a>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-400 italic">
                        Tidak ada berkas yang dilampirkan pendaftar.
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-100">
                    <button type="button" @click="isDetailModalOpen = false" class="px-5 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                        Tutup Detail
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
