<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    meetings: Object,
    teachers: Array,
    filters: Object,
    stats: Object,
});

const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
    { label: 'Materi & Tugas (LMS)', route: 'academic.learning.index', icon: 'book-open' },
    { label: 'CBT Ujian Online', route: 'academic.cbt.index', icon: 'desktop' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Buku Rapor & Leger', route: 'academic.reports.index', icon: 'document' },
    { label: 'PPDB Murid Baru', route: 'academic.ppdb.index', icon: 'users' },
    { label: 'Rapat & BAP Digital', route: 'academic.meetings.index', icon: 'document' },
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

const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || 'all');
const selectedStatus = ref(props.filters.status || 'all');

const applyFilter = () => {
    router.get(route('academic.meetings.index'), {
        search: search.value,
        type: selectedType.value,
        status: selectedStatus.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilter = () => {
    search.value = '';
    selectedType.value = 'all';
    selectedStatus.value = 'all';
    applyFilter();
};

// Modal Buat Rapat
const isCreateModalOpen = ref(false);
const form = useForm({
    title: '',
    meeting_type: 'dewan_guru',
    meeting_date: new Date().toISOString().substring(0, 10),
    start_time: '08:30',
    end_time: '11:00',
    location: 'Ruang Guru & Meeting Hall',
    meeting_link: '',
    leader_user_id: '',
    secretary_user_id: '',
    agenda: '',
    target_audience: 'Seluruh Dewan Guru & Staf TU',
    auto_invite_teachers: true,
});

const openCreateModal = () => {
    form.reset();
    form.meeting_date = new Date().toISOString().substring(0, 10);
    form.start_time = '08:30';
    form.end_time = '11:00';
    form.location = 'Ruang Guru & Meeting Hall';
    form.target_audience = 'Seluruh Dewan Guru & Staf TU';
    form.auto_invite_teachers = true;
    if (props.teachers.length > 0) {
        form.leader_user_id = props.teachers[0].id;
    }
    isCreateModalOpen.value = true;
};

const submitCreate = () => {
    form.post(route('academic.meetings.store'), {
        onSuccess: () => isCreateModalOpen.value = false,
    });
};

const deleteMeeting = (meeting) => {
    if (confirm(`Yakin ingin menghapus agenda rapat '${meeting.title}'?`)) {
        router.delete(route('academic.meetings.destroy', meeting.id));
    }
};

const meetingTypeLabels = {
    dewan_guru: 'Rapat Dewan Guru',
    pleno: 'Rapat Pleno',
    wali_murid: 'Pertemuan Orang Tua / Wali',
    komite: 'Rapat Komite Sekolah',
    koordinasi_jurusan: 'Koordinasi Jurusan / KKG',
    kedisiplinan: 'Evaluasi & Kedisiplinan',
    lainnya: 'Rapat Umum',
};
</script>

<template>
    <Head title="Manajemen Rapat, Notulen & BAP Digital" />

    <AuthenticatedLayout :menus="adminMenus" section-title="Sistem Sekolah">
        <div class="p-6 sm:p-8 max-w-7xl mx-auto space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>📋</span> Manajemen Rapat, Notulen & BAP Digital
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Paperless meeting management: penjadwalan, presensi tanda tangan digital di HP, notulen, dan cetak Berita Acara (BAP) PDF resmi.
                    </p>
                </div>

                <button
                    @click="openCreateModal"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black shadow-md shadow-indigo-600/30 flex items-center gap-2 transition-all self-start sm:self-auto"
                >
                    <span>+</span> Jadwalkan Rapat Baru
                </button>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Agenda Rapat</span>
                    <span class="text-2xl font-black text-slate-900 mt-1 block">{{ stats.total }}</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-blue-200 bg-blue-50/20 shadow-2xs">
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Rapat Mendatang</span>
                    <span class="text-2xl font-black text-blue-700 mt-1 block">{{ stats.scheduled }}</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-2xs">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Sedang Berlangsung</span>
                    <span class="text-2xl font-black text-amber-700 mt-1 block">{{ stats.ongoing }}</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-emerald-200 bg-emerald-50/20 shadow-2xs">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">BAP Resmi Terbit (Selesai)</span>
                    <span class="text-2xl font-black text-emerald-700 mt-1 block">{{ stats.completed }}</span>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilter"
                        type="text"
                        placeholder="Cari Judul / Tempat / Agenda / No. BAP..."
                        class="px-3.5 py-1.5 border border-slate-300 rounded-xl text-xs w-64 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                    />

                    <select v-model="selectedType" @change="applyFilter" class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                        <option value="all">Semua Jenis Rapat</option>
                        <option value="dewan_guru">Rapat Dewan Guru</option>
                        <option value="pleno">Rapat Pleno</option>
                        <option value="wali_murid">Pertemuan Orang Tua</option>
                        <option value="komite">Rapat Komite Sekolah</option>
                        <option value="koordinasi_jurusan">Koordinasi Jurusan</option>
                        <option value="kedisiplinan">Kedisiplinan</option>
                    </select>

                    <select v-model="selectedStatus" @change="applyFilter" class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                        <option value="all">Semua Status</option>
                        <option value="scheduled">Terjadwal</option>
                        <option value="ongoing">Sedang Berlangsung</option>
                        <option value="completed">Selesai (BAP Terbit)</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>

                    <button @click="applyFilter" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900">
                        Filter
                    </button>
                    <button @click="resetFilter" class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-xs hover:bg-slate-200">
                        Reset
                    </button>
                </div>
            </div>

            <!-- List of Meetings Grid -->
            <div v-if="meetings.data.length === 0" class="text-center py-16 bg-white rounded-3xl border border-slate-200 shadow-xs space-y-2">
                <span class="text-4xl block">📅</span>
                <h3 class="text-base font-bold text-slate-700">Belum Ada Agenda Rapat</h3>
                <p class="text-xs text-slate-400">Jadwalkan rapat dewan guru, pleno, atau komite sekolah dengan menekan tombol di atas.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="m in meetings.data"
                    :key="m.id"
                    class="bg-white rounded-3xl border border-slate-200 shadow-xs hover:shadow-lg transition-all p-6 flex flex-col justify-between space-y-5"
                >
                    <div>
                        <!-- Header Card -->
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ meetingTypeLabels[m.meeting_type] || 'Rapat' }}
                            </span>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                    m.status === 'completed' ? 'bg-emerald-100 text-emerald-800' :
                                    m.status === 'ongoing' ? 'bg-amber-100 text-amber-800 animate-pulse' :
                                    m.status === 'cancelled' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800'
                                ]"
                            >
                                {{ m.status === 'completed' ? 'BAP Terbit' : m.status }}
                            </span>
                        </div>

                        <!-- Judul & No BAP -->
                        <h3 class="font-extrabold text-slate-900 text-base mt-2.5 leading-snug">
                            {{ m.title }}
                        </h3>
                        <span class="text-[10px] font-mono text-slate-400 block mt-0.5">
                            No. BAP: {{ m.meeting_number || '-' }}
                        </span>

                        <!-- Detail Info -->
                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <div class="flex items-center gap-2">
                                <span>📅</span>
                                <span class="font-bold text-slate-800">
                                    {{ new Date(m.meeting_date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' }) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span>⏰</span>
                                <span>{{ m.start_time }} WIB {{ m.end_time ? `s/d ${m.end_time} WIB` : 's/d Selesai' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span>📍</span>
                                <span class="truncate">{{ m.location }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-500">
                                <span>👤</span>
                                <span>Pimpinan: <strong>{{ m.leader_name || m.leader_user?.name || 'Kepala Sekolah' }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-500">
                                <span>👥</span>
                                <span>Presensi: <strong>{{ m.attendances_count || 0 }} Peserta Terdaftar</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Footer -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 gap-2">
                        <Link
                            :href="route('academic.meetings.show', m.id)"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs flex-1 justify-center"
                        >
                            <span>📝</span> Ruang Rapat & Notulen
                        </Link>

                        <a
                            :href="route('academic.meetings.bap-pdf', m.id)"
                            target="_blank"
                            class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors"
                            title="Unduh / Cetak Berita Acara (BAP) PDF"
                        >
                            <span>🖨️</span>
                        </a>

                        <button
                            @click="deleteMeeting(m)"
                            class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs transition-colors"
                            title="Hapus Agenda"
                        >
                            <span>🗑️</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL JADWALKAN RAPAT BARU -->
        <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">Jadwalkan Agenda Rapat Baru</h3>
                    <button @click="isCreateModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama / Judul Rapat <span class="text-rose-500">*</span></label>
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Contoh: Rapat Pleno Evaluasi Kurikulum & KBM"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Rapat</label>
                            <select v-model="form.meeting_type" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                <option value="dewan_guru">Rapat Dewan Guru</option>
                                <option value="pleno">Rapat Pleno Sekolah</option>
                                <option value="wali_murid">Pertemuan Orang Tua / Wali</option>
                                <option value="komite">Rapat Komite Sekolah</option>
                                <option value="koordinasi_jurusan">Koordinasi Jurusan / KKG</option>
                                <option value="kedisiplinan">Rapat Evaluasi Kedisiplinan</option>
                                <option value="lainnya">Lainnya / Umum</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Rapat <span class="text-rose-500">*</span></label>
                            <input v-model="form.meeting_date" type="date" required class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Waktu Mulai <span class="text-rose-500">*</span></label>
                            <input v-model="form.start_time" type="time" required class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Waktu Selesai</label>
                            <input v-model="form.end_time" type="time" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tempat / Ruang Pertemuan <span class="text-rose-500">*</span></label>
                        <input
                            v-model="form.location"
                            type="text"
                            placeholder="Contoh: Ruang Guru / Meeting Hall / Lab Multimedia"
                            required
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tautan Rapat Online (Opsional - Zoom / Google Meet)</label>
                        <input
                            v-model="form.meeting_link"
                            type="url"
                            placeholder="https://meet.google.com/xyz-abcd-efg"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pimpinan Rapat</label>
                            <select v-model="form.leader_user_id" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                <option value="">-- Pilih Pimpinan Rapat --</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Notulis / Sekretaris</label>
                            <select v-model="form.secretary_user_id" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                <option value="">-- Pilih Notulis Rapat --</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pokok Bahasan / Agenda</label>
                        <textarea
                            v-model="form.agenda"
                            rows="3"
                            placeholder="Tuliskan poin-poin agenda yang akan dibahas..."
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        ></textarea>
                    </div>

                    <div class="p-3 bg-indigo-50/60 border border-indigo-100 rounded-2xl">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" v-model="form.auto_invite_teachers" class="accent-indigo-600 rounded" />
                            <span class="font-bold text-indigo-900">Otomatis Masukkan Seluruh Dewan Guru ke Lembar Presensi</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isCreateModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-md shadow-indigo-600/20"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Jadwalkan Rapat' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
