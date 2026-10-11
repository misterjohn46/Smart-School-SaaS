<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    teachers: Object,
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
const gender = ref(props.filters.gender || '');

// Modal States
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isCredentialModalOpen = ref(false);
const activeCredential = ref(null);
const copySuccess = ref(false);

const form = useForm({
    full_name: '',
    email: '',
    nip: '',
    nuptk: '',
    gender: 'L',
    phone_wa: '',
    address: '',
    password: '',
});

// Flash Credentials Listener
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

// Filter Handler
const applyFilter = () => {
    router.get(
        route('academic.teachers.index'),
        {
            search: search.value || undefined,
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
    form.gender = 'L';
    isModalOpen.value = true;
};

// Open Edit Modal
const openEditModal = (teacher) => {
    isEditing.value = true;
    editingId.value = teacher.id;
    form.clearErrors();
    form.full_name = teacher.full_name;
    form.email = teacher.user?.email || '';
    form.nip = teacher.nip || '';
    form.nuptk = teacher.nuptk || '';
    form.gender = teacher.gender || 'L';
    form.phone_wa = teacher.phone_wa || '';
    form.address = teacher.address || '';
    form.password = '';
    isModalOpen.value = true;
};

// Submit Form
const submitForm = () => {
    if (isEditing.value) {
        form.put(route('academic.teachers.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('academic.teachers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

// Delete Teacher
const deleteTeacher = (teacher) => {
    if (confirm(`Apakah Anda yakin ingin menghapus data guru "${teacher.full_name}"?`)) {
        router.delete(route('academic.teachers.destroy', teacher.id), {
            preserveScroll: true,
        });
    }
};

// Reset Password
const resetPassword = (teacher) => {
    if (confirm(`Reset kata sandi akun login untuk guru "${teacher.full_name}"?`)) {
        router.post(route('academic.teachers.reset-password', teacher.id), {}, {
            preserveScroll: true,
        });
    }
};

// Copy Credential
const copyCredential = () => {
    if (!activeCredential.value) return;
    const text = `KREDENSIAL LOGIN SIAKAD GURU\nNama: ${activeCredential.value.name}\nNIP: ${activeCredential.value.nip || '-'}\nEmail: ${activeCredential.value.email}\nPassword: ${activeCredential.value.password}\nLogin di: ${window.location.origin}/login`;
    navigator.clipboard.writeText(text).then(() => {
        copySuccess.value = true;
        setTimeout(() => {
            copySuccess.value = false;
        }, 3000);
    });
};
</script>

<template>
    <Head title="Master Data Guru & PTK - SIAKAD" />

    <AuthenticatedLayout :menus="adminMenus" section-title="Master Data Akademik">
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
            
            <!-- 1. Executive Top Hero Header -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 shadow-2xl p-6 sm:p-8 text-white">
                <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-purple-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-500/20 text-purple-300 border border-purple-400/30">
                                <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                                Dewan Guru & Tenaga Kependidikan
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-medium bg-slate-800/80 text-slate-300 border border-slate-700/60">
                                {{ stats.total }} Pengajar Aktif
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                            Data Guru & Tenaga Kependidikan (PTK)
                        </h1>
                        <p class="text-sm text-slate-300 mt-2 max-w-3xl leading-relaxed">
                            Manajemen dewan guru pengajar, Nomor Induk Pegawai (NIP), NUPTK, akun portal SIAKAD & LMS, serta nomor kontak resmi madrasah/sekolah.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <Link
                            :href="route('academic.schedules')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 hover:text-white font-semibold text-xs border border-slate-700 transition"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Jadwal Mengajar Guru
                        </Link>

                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-xs shadow-lg shadow-purple-500/25 transition transform active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            + Tambah Guru Baru
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
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl bg-white dark:bg-slate-800 p-5 border border-gray-200/80 dark:border-slate-700/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Total Guru & Staf</span>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ stats.total }}</div>
                    <span class="text-xs text-purple-600 dark:text-purple-400 mt-0.5 block font-medium">Pendidik Terdaftar</span>
                </div>

                <div class="rounded-2xl bg-white dark:bg-slate-800 p-5 border border-blue-200 dark:border-blue-900/60 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 block">Guru Laki-laki</span>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ stats.male }}</div>
                    <span class="text-xs text-gray-500 mt-0.5 block">Kode Gender (L)</span>
                </div>

                <div class="rounded-2xl bg-white dark:bg-slate-800 p-5 border border-pink-200 dark:border-pink-900/60 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-pink-600 dark:text-pink-400 block">Guru Perempuan</span>
                    <div class="text-2xl font-black text-pink-600 dark:text-pink-400 mt-1">{{ stats.female }}</div>
                    <span class="text-xs text-gray-500 mt-0.5 block">Kode Gender (P)</span>
                </div>
            </div>

            <!-- 3. Filter Bar & Search -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-3xl border border-gray-200/80 dark:border-slate-800 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
                    <div class="sm:col-span-8 relative">
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="applyFilter"
                            placeholder="Cari nama guru, NIP, NUPTK, atau email..."
                            class="w-full rounded-2xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 pl-9 pr-4 focus:ring-2 focus:ring-purple-500"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <div class="sm:col-span-3">
                        <select
                            v-model="gender"
                            @change="applyFilter"
                            class="w-full rounded-2xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-purple-500"
                        >
                            <option value="">Semua Gender</option>
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-1">
                        <button
                            @click="applyFilter"
                            class="w-full h-full py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-sm transition"
                        >
                            Cari
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Teachers Table -->
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/80 dark:bg-slate-800/80 border-b border-gray-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="py-3.5 px-4 w-12 text-center">No</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[240px]">Guru & Akun Login</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[150px]">NIP & NUPTK</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[150px]">Kontak WhatsApp</th>
                                <th scope="col" class="py-3.5 px-4 min-w-[180px]">Alamat</th>
                                <th scope="col" class="py-3.5 px-4 text-center min-w-[160px]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200/70 dark:divide-slate-800/70">
                            <tr
                                v-for="(tc, idx) in teachers.data"
                                :key="tc.id"
                                class="transition hover:bg-gray-50/60 dark:hover:bg-slate-800/50"
                            >
                                <td class="py-3.5 px-4 text-center font-mono text-xs text-gray-400 font-bold">
                                    {{ (teachers.current_page - 1) * teachers.per_page + idx + 1 }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                            {{ tc.full_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white leading-tight">
                                                {{ tc.full_name }}
                                            </div>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                                <span>{{ tc.user?.email || '-' }}</span>
                                                <span :class="tc.gender === 'L' ? 'text-blue-500 font-bold' : 'text-pink-500 font-bold'">
                                                    ({{ tc.gender }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-xs">
                                    <span class="font-bold text-gray-900 dark:text-white block">{{ tc.nip || '-' }}</span>
                                    <span class="text-[11px] text-gray-400">{{ tc.nuptk ? `NUPTK: ${tc.nuptk}` : 'NUPTK: -' }}</span>
                                </td>

                                <td class="py-3.5 px-4 text-xs font-mono">
                                    {{ tc.phone_wa || '-' }}
                                </td>

                                <td class="py-3.5 px-4 text-xs text-gray-600 dark:text-gray-300">
                                    {{ tc.address || '-' }}
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            @click="openEditModal(tc)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-800 transition"
                                            title="Edit Data Guru"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button
                                            @click="resetPassword(tc)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-slate-800 transition"
                                            title="Reset Password Akun Guru"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </button>

                                        <button
                                            @click="deleteTeacher(tc)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition"
                                            title="Hapus Guru"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="teachers.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                    <p class="text-sm font-bold">Belum ada data guru</p>
                                    <p class="text-xs text-gray-400 mt-1">Klik tombol "+ Tambah Guru Baru" untuk menambahkan tenaga pendidik.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="teachers.links && teachers.links.length > 3" class="p-4 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">
                        Menampilkan {{ teachers.from || 0 }} - {{ teachers.to || 0 }} dari {{ teachers.total }} guru
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in teachers.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1 rounded-lg transition',
                                link.active ? 'bg-purple-600 text-white font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- 5. Modal Tambah / Edit Guru -->
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-opacity"
            >
                <div class="relative w-full max-w-xl rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 shadow-2xl overflow-hidden p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-slate-800">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white">
                                {{ isEditing ? 'Edit Data Guru' : 'Tambah Guru Baru' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Lengkapi biodata guru pengajar, NIP, kontak, dan akun login SIAKAD & LMS.
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
                                    Nama Lengkap & Gelar *
                                </label>
                                <input
                                    type="text"
                                    v-model="form.full_name"
                                    required
                                    placeholder="Contoh: Ahmad Fauzi, S.Kom, M.T."
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-purple-500"
                                />
                                <span v-if="form.errors.full_name" class="text-[11px] text-rose-500 mt-1 block">{{ form.errors.full_name }}</span>
                            </div>

                            <!-- Email Login Guru -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Email Akun Login *
                                </label>
                                <input
                                    type="email"
                                    v-model="form.email"
                                    required
                                    placeholder="guru@sekolah.sch.id"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-purple-500"
                                />
                                <span v-if="form.errors.email" class="text-[11px] text-rose-500 mt-1 block">{{ form.errors.email }}</span>
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Kata Sandi {{ isEditing ? '(Kosongkan jika tidak diubah)' : '(Default: guru123)' }}
                                </label>
                                <input
                                    type="text"
                                    v-model="form.password"
                                    :placeholder="isEditing ? 'Biarkan kosong jika tetap' : 'guru123'"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-purple-500"
                                />
                            </div>

                            <!-- NIP -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    NIP (Nomor Induk Pegawai)
                                </label>
                                <input
                                    type="text"
                                    v-model="form.nip"
                                    placeholder="Contoh: 198507122010011005"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-purple-500"
                                />
                            </div>

                            <!-- NUPTK -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    NUPTK
                                </label>
                                <input
                                    type="text"
                                    v-model="form.nuptk"
                                    placeholder="Contoh: 9876543210123456"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-purple-500"
                                />
                            </div>

                            <!-- Jenis Kelamin -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Jenis Kelamin *
                                </label>
                                <div class="flex gap-4 pt-1">
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        <input type="radio" v-model="form.gender" value="L" class="text-purple-600 focus:ring-purple-500" />
                                        Laki-laki
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        <input type="radio" v-model="form.gender" value="P" class="text-purple-600 focus:ring-purple-500" />
                                        Perempuan
                                    </label>
                                </div>
                            </div>

                            <!-- No WhatsApp Guru -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    No WhatsApp / HP
                                </label>
                                <input
                                    type="text"
                                    v-model="form.phone_wa"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-purple-500"
                                />
                            </div>

                            <!-- Alamat -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Alamat Domisili
                                </label>
                                <input
                                    type="text"
                                    v-model="form.address"
                                    placeholder="Jl. Pahlawan No. 45..."
                                    class="w-full rounded-xl border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white text-xs py-2.5 px-3 focus:ring-2 focus:ring-purple-500"
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
                                class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 transition disabled:opacity-50"
                            >
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Guru') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 6. Modal Kredensial Login Guru -->
            <div
                v-if="isCredentialModalOpen && activeCredential"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-opacity"
            >
                <div class="relative w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 shadow-2xl overflow-hidden p-6 sm:p-8 text-center">
                    <div class="w-16 h-16 rounded-3xl bg-purple-500/10 text-purple-500 mx-auto flex items-center justify-center mb-4 border border-purple-500/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-black text-gray-900 dark:text-white">
                        Akun Login Guru Siap!
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Kredensial login SIAKAD & LMS untuk guru <strong>{{ activeCredential.name }}</strong> telah siap digunakan.
                    </p>

                    <div class="mt-5 p-4 rounded-2xl bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-left space-y-2 font-mono text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Email:</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400">{{ activeCredential.email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Password:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ activeCredential.password }}</span>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-2">
                        <button
                            @click="copyCredential"
                            class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 transition flex items-center justify-center gap-2"
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
