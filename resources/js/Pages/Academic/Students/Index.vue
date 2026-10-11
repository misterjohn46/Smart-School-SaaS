<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    students: Object,
    classrooms: Array,
    filters: Object,
    stats: Object,
});

const page = usePage();

// Navigasi Terpadu Administrator Sekolah
const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
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
    { label: 'Data Siswa & Rombel', route: 'academic.students.index', icon: 'users' },
    { label: 'Data Guru & PTK', route: 'academic.teachers.index', icon: 'academic-cap' },
    { label: 'Rombel (Kelas)', route: 'academic.classrooms.index', icon: 'rectangle-stack' },
    { label: 'Mata Pelajaran', route: 'academic.subjects.index', icon: 'book-open' },
];

// Reactive Filters
const search = ref(props.filters.search || '');
const classroomId = ref(props.filters.classroom_id || '');
const gender = ref(props.filters.gender || '');

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isCredentialModalOpen = ref(false);
const activeCredential = ref(null);
const copySuccess = ref(false);

const form = useForm({
    full_name: '',
    email: '',
    nisn: '',
    nik: '',
    classroom_id: '',
    gender: 'L',
    parent_name: '',
    parent_phone_wa: '',
    address: '',
    password: '',
});

// Watch created or reset credentials from session flash
watch(
    () => page.props.flash?.created_credential,
    (cred) => {
        if (cred) {
            activeCredential.value = cred;
            isCredentialModalOpen.value = true;
        }
    },
    { immediate: true }
);

watch(
    () => page.props.flash?.reset_credential,
    (cred) => {
        if (cred) {
            activeCredential.value = cred;
            isCredentialModalOpen.value = true;
        }
    },
    { immediate: true }
);

// Filter handler
const applyFilter = () => {
    router.get(
        route('academic.students.index'),
        {
            search: search.value || undefined,
            classroom_id: classroomId.value || undefined,
            gender: gender.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

// Open Create Modal
const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.classroom_id = props.classrooms[0]?.id || '';
    form.gender = 'L';
    isModalOpen.value = true;
};

// Open Edit Modal
const openEditModal = (student) => {
    isEditing.value = true;
    editingId.value = student.id;
    form.clearErrors();
    form.full_name = student.full_name;
    form.email = student.user?.email || '';
    form.nisn = student.nisn;
    form.nik = student.nik || '';
    form.classroom_id = student.classroom_id;
    form.gender = student.gender || 'L';
    form.parent_name = student.parent_name || '';
    form.parent_phone_wa = student.parent_phone_wa || '';
    form.address = student.address || '';
    form.password = '';
    isModalOpen.value = true;
};

// Submit Form
const submitForm = () => {
    if (isEditing.value) {
        form.put(route('academic.students.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('academic.students.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

// Delete Student
const deleteStudent = (student) => {
    if (confirm(`Apakah Anda yakin ingin menghapus data siswa "${student.full_name}"? Seluruh data akun dan profil akan terhapus.`)) {
        router.delete(route('academic.students.destroy', student.id), {
            preserveScroll: true,
        });
    }
};

// Reset Password
const resetPassword = (student) => {
    if (confirm(`Reset kata sandi akun login untuk siswa "${student.full_name}"?`)) {
        router.post(route('academic.students.reset-password', student.id), {}, {
            preserveScroll: true,
        });
    }
};

// Copy credentials to clipboard
const copyCredential = () => {
    if (!activeCredential.value) return;
    const text = `KREDENSIAL LOGIN SIAKAD SISWA\nNama: ${activeCredential.value.name}\nNISN: ${activeCredential.value.nisn || '-'}\nEmail: ${activeCredential.value.email}\nPassword: ${activeCredential.value.password}\nLogin di: ${window.location.origin}/login`;
    navigator.clipboard.writeText(text).then(() => {
        copySuccess.value = true;
        setTimeout(() => {
            copySuccess.value = false;
        }, 3000);
    });
};

const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};
</script>

<template>
    <Head title="Master Data Siswa & Rombel - SIAKAD" />

    <AuthenticatedLayout :menus="adminMenus" section-title="Master Data Akademik">
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
            
            <!-- 1. Executive Top Hero Header -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 shadow-2xl p-6 sm:p-8 text-white">
                <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <!-- Pill status -->
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                                Master Data Akademik
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-medium bg-slate-800/80 text-slate-300 border border-slate-700/60">
                                {{ stats.classrooms_count }} Rombongan Belajar
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                            Data Siswa & Rombongan Belajar
                        </h1>
                        <p class="text-sm text-slate-300 mt-2 max-w-3xl leading-relaxed">
                            Pusat manajemen biodata siswa, pembagian rombel kelas, akun portal login siswa, dompet digital, serta nomor identitas (NISN / NIK) dalam satu antarmuka terpadu.
                        </p>
                    </div>

                    <!-- Header Quick Actions -->
                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <Link
                            :href="route('kts.batch')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 hover:text-white font-semibold text-xs border border-slate-700 transition"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                            Cetak Kartu KTS
                        </Link>

                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-400 hover:to-blue-500 text-white font-black text-xs shadow-lg shadow-indigo-500/25 transition transform active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            + Tambah Siswa Baru
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Success Alert -->
            <div
                v-if="page.props.flash?.status"
                class="rounded-2xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-emerald-400 flex items-center justify-between shadow-lg"
            >
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <p class="text-sm font-bold text-emerald-300">{{ page.props.flash.status }}</p>
                </div>
            </div>

            <!-- 2. KPI Metrics Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white dark:bg-slate-800 p-5 border border-gray-200/80 dark:border-slate-700/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Total Siswa</span>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ stats.total }}</div>
                    <span class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5 block font-medium">Terdaftar di Database</span>
                </div>

                <div class="rounded-2xl bg-white dark:bg-slate-800 p-5 border border-blue-200 dark:border-blue-900/60 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 block">Siswa Laki-laki</span>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ stats.male }}</div>
                    <span class="text-xs text-gray-500 mt-0.5 block">Kode Gender (L)</span>
                </div>

                <div class="rounded-2xl bg-white dark:bg-slate-800 p-5 border border-pink-200 dark:border-pink-900/60 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-pink-600 dark:text-pink-400 block">Siswa Perempuan</span>
                    <div class="text-2xl font-black text-pink-600 dark:text-pink-400 mt-1">{{ stats.female }}</div>
                    <span class="text-xs text-gray-500 mt-0.5 block">Kode Gender (P)</span>
                </div>

                <div class="rounded-2xl bg-white dark:bg-slate-800 p-5 border border-emerald-200 dark:border-emerald-900/60 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block">Rombel Kelas</span>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.classrooms_count }}</div>
                    <span class="text-xs text-emerald-700 dark:text-emerald-500 mt-0.5 block font-medium">Kelas Aktif</span>
                </div>
            </div>

            <!-- 3. Filter Bar & Search -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-3xl border border-gray-200/80 dark:border-slate-800 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
                    <!-- Search Input -->
                    <div class="sm:col-span-6 relative">
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="applyFilter"
                            placeholder="Cari nama, NISN, NIK, atau email..."
                            class="w-full rounded-2xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 pl-9 pr-4 focus:ring-2 focus:ring-indigo-500"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Filter Kelas -->
                    <div class="sm:col-span-3">
                        <select
                            v-model="classroomId"
                            @change="applyFilter"
                            class="w-full rounded-2xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Semua Rombel Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Gender -->
                    <div class="sm:col-span-2">
                        <select
                            v-model="gender"
                            @change="applyFilter"
                            class="w-full rounded-2xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Semua Gender</option>
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="sm:col-span-1">
                        <button
                            @click="applyFilter"
                            class="w-full h-full py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-sm transition"
                        >
                            Cari
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Students Table -->
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/80 dark:bg-slate-800/80 border-b border-gray-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="py-3.5 px-4 w-12 text-center">No</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[240px]">Siswa & Akun</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[140px]">NISN & NIK</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[140px]">Kelas & Jurusan</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[150px]">Kontak Orang Tua</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[120px]">Dompet Digital</th>
                                <th scope="col" class="py-3.5 px-4 text-center min-w-[160px]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200/70 dark:divide-slate-800/70">
                            <tr
                                v-for="(st, idx) in students.data"
                                :key="st.id"
                                class="transition hover:bg-gray-50/60 dark:hover:bg-slate-800/50"
                            >
                                <td class="py-3.5 px-4 text-center font-mono text-xs text-gray-400 font-bold">
                                    {{ (students.current_page - 1) * students.per_page + idx + 1 }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                            {{ st.full_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white leading-tight">
                                                {{ st.full_name }}
                                            </div>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                                <span>{{ st.user?.email || '-' }}</span>
                                                <span :class="st.gender === 'L' ? 'text-blue-500 font-bold' : 'text-pink-500 font-bold'">
                                                    ({{ st.gender }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-xs">
                                    <span class="font-bold text-gray-900 dark:text-white block">{{ st.nisn }}</span>
                                    <span class="text-[11px] text-gray-400">{{ st.nik || 'NIK: -' }}</span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        {{ st.classroom?.name || 'Belum Masuk Kelas' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-xs">
                                    <div class="font-semibold text-gray-800 dark:text-gray-200">
                                        {{ st.parent_name || 'Wali: -' }}
                                    </div>
                                    <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                        {{ st.parent_phone_wa || '-' }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatRupiah(st.wallet?.balance) }}
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            @click="openEditModal(st)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 transition"
                                            title="Edit Data Siswa"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button
                                            @click="resetPassword(st)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-slate-800 transition"
                                            title="Reset Password Akun"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </button>

                                        <button
                                            @click="deleteStudent(st)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition"
                                            title="Hapus Siswa"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="students.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                    <p class="text-sm font-bold">Belum ada data siswa</p>
                                    <p class="text-xs text-gray-400 mt-1">Klik tombol "+ Tambah Siswa Baru" untuk menambahkan siswa ke rombel.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="students.links && students.links.length > 3" class="p-4 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">
                        Menampilkan {{ students.from || 0 }} - {{ students.to || 0 }} dari {{ students.total }} siswa
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in students.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1 rounded-lg transition',
                                link.active ? 'bg-indigo-600 text-white font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- 5. Modal Tambah / Edit Siswa -->
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-opacity"
            >
                <div class="relative w-full max-w-2xl rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 shadow-2xl overflow-hidden p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-slate-800">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white">
                                {{ isEditing ? 'Edit Data Siswa' : 'Tambah Siswa Baru' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Lengkapi biodata siswa, pemilihan rombel, dan informasi akun portal login.
                            </p>
                        </div>
                        <button
                            @click="isModalOpen = false"
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nama Lengkap -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Nama Lengkap Siswa *
                                </label>
                                <input
                                    type="text"
                                    v-model="form.full_name"
                                    required
                                    placeholder="Contoh: Muhammad Rizky Pratama"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                />
                                <span v-if="form.errors.full_name" class="text-[11px] text-rose-500 mt-1 block">{{ form.errors.full_name }}</span>
                            </div>

                            <!-- Email Login Siswa -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Email Akun Login *
                                </label>
                                <input
                                    type="email"
                                    v-model="form.email"
                                    required
                                    placeholder="siswa@sekolah.sch.id"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                />
                                <span v-if="form.errors.email" class="text-[11px] text-rose-500 mt-1 block">{{ form.errors.email }}</span>
                            </div>

                            <!-- Password (Opsional) -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Kata Sandi {{ isEditing ? '(Kosongkan jika tidak diubah)' : '(Default: siswa123)' }}
                                </label>
                                <input
                                    type="text"
                                    v-model="form.password"
                                    :placeholder="isEditing ? 'Biarkan kosong jika tetap' : 'siswa123'"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                />
                                <span v-if="form.errors.password" class="text-[11px] text-rose-500 mt-1 block">{{ form.errors.password }}</span>
                            </div>

                            <!-- NISN -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    NISN (10 Digit) *
                                </label>
                                <input
                                    type="text"
                                    v-model="form.nisn"
                                    required
                                    placeholder="Contoh: 0089123456"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                />
                                <span v-if="form.errors.nisn" class="text-[11px] text-rose-500 mt-1 block">{{ form.errors.nisn }}</span>
                            </div>

                            <!-- NIK -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    NIK (Nomor Induk Kependudukan)
                                </label>
                                <input
                                    type="text"
                                    v-model="form.nik"
                                    placeholder="Contoh: 3513012345670001"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <!-- Rombel Kelas -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Rombongan Belajar (Kelas) *
                                </label>
                                <select
                                    v-model="form.classroom_id"
                                    required
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                        {{ c.name }} ({{ c.department }})
                                    </option>
                                </select>
                            </div>

                            <!-- Jenis Kelamin -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Jenis Kelamin *
                                </label>
                                <div class="flex gap-4 pt-1">
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        <input type="radio" v-model="form.gender" value="L" class="text-indigo-600 focus:ring-indigo-500" />
                                        Laki-laki
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        <input type="radio" v-model="form.gender" value="P" class="text-indigo-600 focus:ring-indigo-500" />
                                        Perempuan
                                    </label>
                                </div>
                            </div>

                            <!-- Nama Orang Tua -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Nama Orang Tua / Wali
                                </label>
                                <input
                                    type="text"
                                    v-model="form.parent_name"
                                    placeholder="Nama Ayah/Ibu/Wali"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <!-- No WhatsApp Ortu -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    No WhatsApp Orang Tua
                                </label>
                                <input
                                    type="text"
                                    v-model="form.parent_phone_wa"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <!-- Alamat Tinggal -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Alamat Domisili
                                </label>
                                <input
                                    type="text"
                                    v-model="form.address"
                                    placeholder="Jl. Mawar No. 12, Kelurahan..."
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Footer Modal Buttons -->
                        <div class="pt-4 mt-6 border-t border-gray-200 dark:border-slate-800 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 font-semibold text-xs transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition disabled:opacity-50"
                            >
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Siswa') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 6. Modal Kredensial Login Siswa (Popup Keren) -->
            <div
                v-if="isCredentialModalOpen && activeCredential"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-opacity"
            >
                <div class="relative w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 shadow-2xl overflow-hidden p-6 sm:p-8 text-center">
                    <div class="w-16 h-16 rounded-3xl bg-emerald-500/10 text-emerald-500 mx-auto flex items-center justify-center mb-4 border border-emerald-500/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-black text-gray-900 dark:text-white">
                        Akun Login Siswa Siap!
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Kredensial login untuk siswa <strong>{{ activeCredential.name }}</strong> telah berhasil digenerate.
                    </p>

                    <!-- Kartu Kredensial -->
                    <div class="mt-5 p-4 rounded-2xl bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-left space-y-2 font-mono text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Email:</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ activeCredential.email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Password:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ activeCredential.password }}</span>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-2">
                        <button
                            @click="copyCredential"
                            class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            {{ copySuccess ? '✓ Kredensial Disalin!' : 'Salin Kredensial Login' }}
                        </button>

                        <button
                            @click="isCredentialModalOpen = false"
                            class="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 font-semibold text-xs hover:bg-gray-200 dark:hover:bg-slate-700 transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
