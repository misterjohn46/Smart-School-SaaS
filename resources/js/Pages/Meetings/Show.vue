<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    meeting: Object,
    currentUser: Object,
    userAttendance: Object,
    teachers: Array,
    attendancesStats: Object,
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

const activeTab = ref('notes'); // 'notes' | 'attendances' | 'media'

// Form Notulen & Keputusan
const notesForm = useForm({
    notes: props.meeting.notes || '',
    decisions: props.meeting.decisions || '',
    status: props.meeting.status || 'scheduled',
    action_items: props.meeting.action_items ? [...props.meeting.action_items] : [],
});

const addActionItem = () => {
    notesForm.action_items.push({
        task: '',
        pic: '',
        due_date: '',
        is_done: false,
    });
};

const removeActionItem = (index) => {
    notesForm.action_items.splice(index, 1);
};

const submitNotes = () => {
    notesForm.put(route('academic.meetings.notes', props.meeting.id));
};

// Canvas Signature Pad Modal
const isSignatureModalOpen = ref(false);
const signatureCanvas = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);

const signForm = useForm({
    status: props.userAttendance?.status || 'hadir',
    notes: props.userAttendance?.notes || '',
    signature: null,
});

const openSignatureModal = () => {
    signForm.status = props.userAttendance?.status || 'hadir';
    signForm.notes = props.userAttendance?.notes || '';
    isSignatureModalOpen.value = true;
    hasDrawn.value = false;
    setTimeout(() => {
        initCanvas();
    }, 150);
};

const initCanvas = () => {
    const canvas = signatureCanvas.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0f172a';
    clearCanvas();
};

const clearCanvas = () => {
    const canvas = signatureCanvas.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasDrawn.value = false;
};

const startDrawing = (e) => {
    isDrawing.value = true;
    hasDrawn.value = true;
    draw(e);
};

const stopDrawing = () => {
    isDrawing.value = false;
    const canvas = signatureCanvas.value;
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.beginPath();
    }
};

const getPos = (e) => {
    const canvas = signatureCanvas.value;
    const rect = canvas.getBoundingClientRect();
    if (e.touches && e.touches.length > 0) {
        return {
            x: e.touches[0].clientX - rect.left,
            y: e.touches[0].clientY - rect.top,
        };
    }
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top,
    };
};

const draw = (e) => {
    if (!isDrawing.value) return;
    const canvas = signatureCanvas.value;
    const ctx = canvas.getContext('2d');
    const pos = getPos(e);

    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
};

const submitSignature = () => {
    if (signForm.status === 'hadir' && !hasDrawn.value && !props.userAttendance?.signature) {
        alert('Mohon bubuhkan tanda tangan Anda pada bidang kanvas.');
        return;
    }

    if (signatureCanvas.value && hasDrawn.value) {
        signForm.signature = signatureCanvas.value.toDataURL('image/png');
    }

    signForm.post(route('academic.meetings.sign', props.meeting.id), {
        onSuccess: () => isSignatureModalOpen.value = false,
    });
};

// Modal WhatsApp Share
const isWaModalOpen = ref(false);
const waText = ref('');
const waUrl = ref('');

const openWaModal = async () => {
    try {
        const res = await fetch(route('academic.meetings.wa-text', props.meeting.id));
        const data = await res.json();
        waText.value = data.text;
        waUrl.value = data.wa_url;
        isWaModalOpen.value = true;
    } catch (e) {
        alert('Gagal mengambil format teks WhatsApp');
    }
};

const copyWaText = () => {
    navigator.clipboard.writeText(waText.value);
    alert('Teks undangan rapat berhasil disalin ke clipboard!');
};

// Modal Tambah Peserta Manual
const isAddParticipantModalOpen = ref(false);
const participantForm = useForm({
    user_id: '',
    participant_name: '',
    participant_role: 'Guru Pengajar',
    status: 'hadir',
});

const onTeacherSelect = () => {
    const t = props.teachers.find(item => item.id === participantForm.user_id);
    if (t) {
        participantForm.participant_name = t.name;
    }
};

const submitAddParticipant = () => {
    participantForm.post(route('academic.meetings.participants.store', props.meeting.id), {
        onSuccess: () => {
            isAddParticipantModalOpen.value = false;
            participantForm.reset();
        },
    });
};

// Upload Foto Dokumentasi
const photoInput = ref(null);
const uploadPhoto = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const pForm = useForm({ photo: file });
    pForm.post(route('academic.meetings.photos.store', props.meeting.id), {
        forceFormData: true,
    });
};

// Upload Berkas Materi
const isUploadModalOpen = ref(false);
const uploadForm = useForm({
    title: '',
    file: null,
});

const submitUploadAttachment = () => {
    uploadForm.post(route('academic.meetings.attachments.store', props.meeting.id), {
        forceFormData: true,
        onSuccess: () => {
            isUploadModalOpen.value = false;
            uploadForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="`Ruang Rapat - ${meeting.title}`" />

    <AuthenticatedLayout :menus="adminMenus" section-title="Sistem Sekolah">
        <div class="p-6 sm:p-8 max-w-7xl mx-auto space-y-6">
            <!-- Back & Breadcrumb -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('academic.meetings.index')"
                    class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors flex items-center gap-1.5"
                >
                    <span>←</span> Kembali ke Daftar Rapat
                </Link>

                <span class="text-[11px] font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full">
                    No. BAP: {{ meeting.meeting_number }}
                </span>
            </div>

            <!-- Banner Utama Rapat -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2 max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                            {{ meeting.type_label }}
                        </span>
                        <span
                            :class="[
                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                meeting.status === 'completed' ? 'bg-emerald-100 text-emerald-800' :
                                meeting.status === 'ongoing' ? 'bg-amber-100 text-amber-800 animate-pulse' :
                                meeting.status === 'cancelled' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800'
                            ]"
                        >
                            {{ meeting.status === 'completed' ? 'Selesai (BAP Terbit)' : meeting.status }}
                        </span>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug">
                        {{ meeting.title }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-slate-500 pt-1">
                        <div class="flex items-center gap-1">
                            <span>📅</span>
                            <span class="font-bold text-slate-700">
                                {{ new Date(meeting.meeting_date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span>⏰</span>
                            <span>{{ meeting.start_time }} WIB {{ meeting.end_time ? `s/d ${meeting.end_time} WIB` : '' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span>📍</span>
                            <span class="font-semibold text-slate-700">{{ meeting.location }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span>👤</span>
                            <span>Pimpinan: <strong>{{ meeting.leader_name || meeting.leader_user?.name || 'Kepala Sekolah' }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Action Button Group -->
                <div class="flex flex-wrap items-center gap-2.5 self-start lg:self-auto">
                    <button
                        @click="openSignatureModal"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5"
                    >
                        <span>✍️</span> Presensi & TTD Saya
                    </button>

                    <button
                        @click="openWaModal"
                        class="px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                        title="Siarkan Undangan WhatsApp"
                    >
                        <span>📲</span> Broadcast WA
                    </button>

                    <a
                        :href="route('academic.meetings.bap-pdf', meeting.id)"
                        target="_blank"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5"
                        title="Unduh Berita Acara Rapat PDF"
                    >
                        <span>🖨️</span> Unduh BAP PDF
                    </a>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-200">
                <button
                    @click="activeTab = 'notes'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'notes'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>📝</span> Notulen, Keputusan & Tindak Lanjut
                </button>
                <button
                    @click="activeTab = 'attendances'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'attendances'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>👥</span> Lembar Presensi & Tanda Tangan ({{ attendancesStats.total }})
                </button>
                <button
                    @click="activeTab = 'media'"
                    :class="[
                        'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'media'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>📸</span> Dokumentasi & Materi Lampiran ({{ (meeting.photos?.length || 0) + (meeting.attachments?.length || 0) }})
                </button>
            </div>

            <!-- TAB 1: NOTULEN & RESUME KEPUTUSAN -->
            <div v-if="activeTab === 'notes'" class="space-y-6">
                <form @submit.prevent="submitNotes" class="space-y-6">
                    <!-- Status Rapat Selector -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">Status Pelaksanaan Rapat</span>
                            <span class="text-[11px] text-slate-400">Ubah status menjadi 'Selesai' untuk meresmikan Berita Acara (BAP).</span>
                        </div>

                        <select
                            v-model="notesForm.status"
                            class="px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        >
                            <option value="scheduled">Terjadwal</option>
                            <option value="ongoing">Sedang Berlangsung</option>
                            <option value="completed">Selesai (Resmikan BAP)</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Pokok Agenda & Notulen Rapat -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-3">
                            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                                <span>📖</span> Pokok Agenda & Pembahasan
                            </h3>
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-700 whitespace-pre-line leading-relaxed">
                                {{ meeting.agenda || 'Tidak ada catatan agenda spesifik.' }}
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-3">
                            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                                <span>✍️</span> Notulen / Jalannya Rapat Real-Time
                            </h3>
                            <textarea
                                v-model="notesForm.notes"
                                rows="8"
                                placeholder="Notulis mencatat jalannya rapat, pertanyaan peserta, respon pimpinan, dan poin penting..."
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-2xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden leading-relaxed"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Kesimpulan & Keputusan Resmi Rapat -->
                    <div class="bg-white p-6 rounded-3xl border border-emerald-200 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-black text-emerald-950 text-sm flex items-center gap-2">
                                <span>🎯</span> Kesimpulan & Keputusan Resmi Rapat
                            </h3>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                Masuk ke Lembar BAP Resmi
                            </span>
                        </div>
                        <textarea
                            v-model="notesForm.decisions"
                            rows="4"
                            placeholder="Tuliskan butir-butir keputusan yang disepakati bersama dalam forum rapat..."
                            class="w-full px-4 py-3 bg-emerald-50/30 border border-emerald-300 rounded-2xl text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden leading-relaxed"
                        ></textarea>
                    </div>

                    <!-- Tindak Lanjut / Action Items (PIC Tracker) -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                                    <span>🚀</span> Tindak Lanjut & Penanggung Jawab (*Action Items & PIC*)
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Pantau tugas dan PIC yang harus menindaklanjuti keputusan rapat.</p>
                            </div>
                            <button
                                type="button"
                                @click="addActionItem"
                                class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-colors flex items-center gap-1"
                            >
                                <span>+</span> Tambah Tugas (PIC)
                            </button>
                        </div>

                        <div v-if="notesForm.action_items.length === 0" class="text-center py-6 text-xs text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                            Belum ada action item tindak lanjut. Klik <strong>+ Tambah Tugas (PIC)</strong> untuk menambahkan.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(item, idx) in notesForm.action_items"
                                :key="idx"
                                class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row items-center gap-3 text-xs"
                            >
                                <div class="flex-1 w-full sm:w-auto">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Rincian Tugas</label>
                                    <input
                                        v-model="item.task"
                                        type="text"
                                        placeholder="Contoh: Cetak kisi-kisi dan distribusi naskah soal"
                                        required
                                        class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs"
                                    />
                                </div>

                                <div class="w-full sm:w-48">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Penanggung Jawab (PIC)</label>
                                    <input
                                        v-model="item.pic"
                                        type="text"
                                        placeholder="Nama Guru / Staf"
                                        required
                                        class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-bold"
                                    />
                                </div>

                                <div class="w-full sm:w-36">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tenggat Waktu</label>
                                    <input
                                        v-model="item.due_date"
                                        type="date"
                                        class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs"
                                    />
                                </div>

                                <button
                                    type="button"
                                    @click="removeActionItem(idx)"
                                    class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-xl mt-4 sm:mt-5"
                                    title="Hapus"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Simpan Button -->
                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="notesForm.processing"
                            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-2xl text-xs font-black shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2"
                        >
                            <span>💾</span> {{ notesForm.processing ? 'Menyimpan Notulen...' : 'Simpan Notulen & Keputusan' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: LEMBAR PRESENSI & TANDA TANGAN DIGITAL -->
            <div v-if="activeTab === 'attendances'" class="space-y-4">
                <!-- Recap Bar -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-4 text-xs font-bold">
                        <span class="text-slate-600">Total: {{ attendancesStats.total }} Peserta</span>
                        <span class="text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">✓ Hadir: {{ attendancesStats.hadir }}</span>
                        <span class="text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">Izin: {{ attendancesStats.izin }}</span>
                        <span class="text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">Sakit: {{ attendancesStats.sakit }}</span>
                        <span class="text-purple-700 bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-200">✍️ Sudah TTD: {{ attendancesStats.signed }}</span>
                    </div>

                    <button
                        @click="isAddParticipantModalOpen = true"
                        class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                    >
                        <span>+</span> Tambah Peserta Manual
                    </button>
                </div>

                <!-- Tabel Presensi -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3">No</th>
                                    <th class="px-5 py-3">Nama Peserta Rapat</th>
                                    <th class="px-5 py-3">Peran / Jabatan</th>
                                    <th class="px-5 py-3 text-center">Status Kehadiran</th>
                                    <th class="px-5 py-3 text-center">Waktu Presensi</th>
                                    <th class="px-5 py-3 text-center">Tanda Tangan Digital</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(att, idx) in meeting.attendances" :key="att.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-3.5 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-900 text-sm">{{ att.participant_name }}</div>
                                        <div v-if="att.notes" class="text-[11px] text-amber-700 italic">"{{ att.notes }}"</div>
                                    </td>
                                    <td class="px-5 py-3.5 font-medium text-slate-700">
                                        {{ att.participant_role || 'Peserta Rapat' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span
                                            :class="[
                                                'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider',
                                                att.status === 'hadir' ? 'bg-emerald-100 text-emerald-800' :
                                                att.status === 'izin' ? 'bg-amber-100 text-amber-800' :
                                                att.status === 'sakit' ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800'
                                            ]"
                                        >
                                            {{ att.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-mono text-[11px] text-slate-500">
                                        {{ att.signed_at ? new Date(att.signed_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <div v-if="att.signature" class="inline-block p-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                                            <img :src="att.signature" alt="TTD" class="h-8 max-w-[120px] object-contain mx-auto" />
                                        </div>
                                        <span v-else class="text-[11px] text-slate-400 italic">Belum Tanda Tangan</span>
                                    </td>
                                </tr>
                                <tr v-if="meeting.attendances.length === 0">
                                    <td colspan="6" class="text-center py-8 text-slate-400 text-xs">Belum ada peserta yang terdaftar pada rapat ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: DOKUMENTASI FOTO & BERKAS MATERI -->
            <div v-if="activeTab === 'media'" class="space-y-6">
                <!-- Dokumentasi Foto Kegiatan Rapat -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                                <span>📸</span> Galeri Dokumentasi Foto Rapat
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Unggah foto suasana jalannya rapat untuk arsip visual sekolah.</p>
                        </div>

                        <div>
                            <input
                                ref="photoInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="uploadPhoto"
                            />
                            <button
                                @click="photoInput?.click()"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5"
                            >
                                <span>+</span> Unggah Foto
                            </button>
                        </div>
                    </div>

                    <div v-if="!meeting.photos || meeting.photos.length === 0" class="text-center py-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-xs text-slate-400">
                        Belum ada dokumentasi foto yang diunggah.
                    </div>

                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div
                            v-for="(photo, pIdx) in meeting.photos"
                            :key="pIdx"
                            class="group relative rounded-2xl overflow-hidden border border-slate-200 aspect-video bg-slate-100"
                        >
                            <img :src="`/storage/${photo}`" alt="Dokumentasi" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <a
                                :href="`/storage/${photo}`"
                                target="_blank"
                                class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold"
                            >
                                Perbesar ↗
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Berkas Materi Presentasi (PDF/PPT) -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                                <span>📁</span> Berkas Materi & Lampiran Presentasi (PDF / PPT)
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Materi tayang, modul, atau dokumen pendukung agenda rapat.</p>
                        </div>

                        <button
                            @click="isUploadModalOpen = true"
                            class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5"
                        >
                            <span>+</span> Unggah Berkas Materi
                        </button>
                    </div>

                    <div v-if="!meeting.attachments || meeting.attachments.length === 0" class="text-center py-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-xs text-slate-400">
                        Belum ada lampiran berkas materi presentasi.
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="(att, aIdx) in meeting.attachments"
                            :key="aIdx"
                            class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between gap-3 text-xs"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📄</span>
                                <div>
                                    <h4 class="font-bold text-slate-900">{{ att.title }}</h4>
                                    <span class="text-[11px] text-slate-400 block">{{ att.name }} ({{ att.size }}) — {{ att.uploaded_at }}</span>
                                </div>
                            </div>

                            <a
                                :href="`/storage/${att.path}`"
                                target="_blank"
                                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 rounded-xl text-xs font-bold text-slate-700 shadow-2xs"
                            >
                                Unduh ↓
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL TANDA TANGAN DIGITAL (CANVAS SIGNATURE PAD) -->
        <div v-if="isSignatureModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">Presensi & Tanda Tangan Digital</h3>
                    <button @click="isSignatureModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="bg-slate-50 p-3 rounded-2xl text-xs space-y-0.5">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Peserta Rapat</span>
                    <p class="font-black text-slate-900 text-sm">{{ currentUser.name }}</p>
                </div>

                <form @submit.prevent="submitSignature" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Status Kehadiran</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label
                                :class="[
                                    'p-2.5 rounded-xl border cursor-pointer text-center font-bold transition-all',
                                    signForm.status === 'hadir' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-500/20' : 'bg-slate-50 border-slate-200'
                                ]"
                            >
                                <input type="radio" v-model="signForm.status" value="hadir" class="hidden" />
                                Hadir
                            </label>
                            <label
                                :class="[
                                    'p-2.5 rounded-xl border cursor-pointer text-center font-bold transition-all',
                                    signForm.status === 'izin' ? 'bg-amber-50 border-amber-500 text-amber-800 ring-2 ring-amber-500/20' : 'bg-slate-50 border-slate-200'
                                ]"
                            >
                                <input type="radio" v-model="signForm.status" value="izin" class="hidden" />
                                Izin
                            </label>
                            <label
                                :class="[
                                    'p-2.5 rounded-xl border cursor-pointer text-center font-bold transition-all',
                                    signForm.status === 'sakit' ? 'bg-blue-50 border-blue-500 text-blue-800 ring-2 ring-blue-500/20' : 'bg-slate-50 border-slate-200'
                                ]"
                            >
                                <input type="radio" v-model="signForm.status" value="sakit" class="hidden" />
                                Sakit
                            </label>
                        </div>
                    </div>

                    <div v-if="signForm.status !== 'hadir'">
                        <label class="block font-bold text-slate-700 mb-1">Keterangan / Alasan Izin</label>
                        <input
                            v-model="signForm.notes"
                            type="text"
                            placeholder="Contoh: Mengikuti dinas luar / pelatihan kurikulum"
                            class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs"
                        />
                    </div>

                    <div v-if="signForm.status === 'hadir'" class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">Tanda Tangan di Layar (Sentuh / Mouse)</label>
                            <button
                                type="button"
                                @click="clearCanvas"
                                class="text-[11px] text-rose-600 hover:underline font-bold"
                            >
                                ✕ Bersihkan
                            </button>
                        </div>

                        <div class="border-2 border-dashed border-slate-300 rounded-2xl p-1 bg-slate-50">
                            <canvas
                                ref="signatureCanvas"
                                width="360"
                                height="150"
                                class="w-full h-36 bg-white rounded-xl cursor-crosshair touch-none"
                                @mousedown="startDrawing"
                                @mousemove="draw"
                                @mouseup="stopDrawing"
                                @mouseleave="stopDrawing"
                                @touchstart.prevent="startDrawing"
                                @touchmove.prevent="draw"
                                @touchend.prevent="stopDrawing"
                            ></canvas>
                        </div>
                        <span class="text-[10px] text-slate-400 block text-center">Goreskan tanda tangan Anda pada kotak di atas.</span>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isSignatureModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button
                            type="submit"
                            :disabled="signForm.processing"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md shadow-emerald-600/20"
                        >
                            {{ signForm.processing ? 'Menyimpan...' : 'Simpan Presensi Saya' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL BROADCAST WHATSAPP -->
        <div v-if="isWaModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">Siarkan Undangan Rapat WhatsApp</h3>
                    <button @click="isWaModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700">Format Pesan Undangan Resmi:</label>
                    <textarea
                        v-model="waText"
                        rows="10"
                        readonly
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-2xl text-xs font-mono leading-relaxed select-all"
                    ></textarea>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <button
                        @click="copyWaText"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold flex items-center gap-1.5"
                    >
                        <span>📋</span> Salin Teks Undangan
                    </button>

                    <a
                        :href="waUrl"
                        target="_blank"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md flex items-center gap-1.5"
                    >
                        <span>📲</span> Buka WhatsApp Web / App
                    </a>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH PESERTA MANUAL -->
        <div v-if="isAddParticipantModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">Tambah Peserta Rapat Manual</h3>
                    <button @click="isAddParticipantModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitAddParticipant" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih dari Daftar Guru / Pegawai</label>
                        <select v-model="participantForm.user_id" @change="onTeacherSelect" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs">
                            <option value="">-- Input Manual / Non-Guru --</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Peserta <span class="text-rose-500">*</span></label>
                        <input v-model="participantForm.participant_name" type="text" required class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Peran / Jabatan</label>
                        <input v-model="participantForm.participant_role" type="text" placeholder="Guru Mapel / Wali Murid / Pengurus Komite" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Status Kehadiran Awal</label>
                        <select v-model="participantForm.status" class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs">
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpa">Alpa / Belum Hadir</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isAddParticipantModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button type="submit" :disabled="participantForm.processing" class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold shadow-xs">
                            Tambahkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL UNGGAH BERKAS MATERI -->
        <div v-if="isUploadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">Unggah Berkas Materi Presentasi</h3>
                    <button @click="isUploadModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitUploadAttachment" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Judul Dokumen / Materi <span class="text-rose-500">*</span></label>
                        <input v-model="uploadForm.title" type="text" placeholder="Contoh: Slide Presentasi Evaluasi KBM Semester Ganjil" required class="w-full px-3 py-2 border rounded-xl bg-slate-50 text-xs" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih Berkas (PDF, PPT, DOCX, XLSX maks 10MB) <span class="text-rose-500">*</span></label>
                        <input
                            type="file"
                            accept=".pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx"
                            required
                            @change="(e) => uploadForm.file = e.target.files[0]"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700"
                        />
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button type="button" @click="isUploadModalOpen = false" class="px-4 py-2 border rounded-xl text-slate-600">Batal</button>
                        <button type="submit" :disabled="uploadForm.processing || !uploadForm.file" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-xs">
                            {{ uploadForm.processing ? 'Mengunggah...' : 'Unggah Materi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
