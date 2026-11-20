<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    tenants: Object,
    filters: Object,
    stats: Object,
    flash: Object,
});

const menus = [
    { label: 'Sekolah Mitra (Tenants)', route: 'central.tenants.index', icon: 'database' },
    { label: 'Paket Langganan SaaS', route: 'central.plans.index', icon: 'rectangle-stack' },
    { label: 'Dashboard Operasional', route: 'dashboard', icon: 'home' },
];

const searchQuery = ref(props.filters.search || '');

const handleSearch = () => {
    router.get(route('central.tenants.index'), { search: searchQuery.value }, {
        preserveState: true,
        replace: true,
    });
};

// Modal State
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showCredsModal = ref(!!props.flash?.credentials);
const activeCredentials = ref(props.flash?.credentials || null);
const selectedTenant = ref(null);
const copySuccess = ref(false);

const createForm = useForm({
    school_name: '',
    npsn: '',
    subdomain: '',
    admin_name: '',
    admin_email: '',
    admin_phone: '',
    trial_days: 14,
});

const editForm = useForm({
    school_name: '',
    admin_name: '',
    admin_phone: '',
    trial_ends_at: '',
});

const submitCreate = () => {
    createForm.post(route('central.tenants.store'), {
        onSuccess: (page) => {
            showCreateModal.value = false;
            createForm.reset();
            if (page.props.flash?.credentials) {
                activeCredentials.value = page.props.flash.credentials;
                showCredsModal.value = true;
            }
        },
    });
};

const openEditModal = (tenant) => {
    selectedTenant.value = tenant;
    editForm.school_name = tenant.school_name;
    editForm.admin_name = tenant.admin_name;
    editForm.admin_phone = tenant.admin_phone || '';
    editForm.trial_ends_at = tenant.trial_ends_at ? tenant.trial_ends_at.substring(0, 10) : '';
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!selectedTenant.value) return;
    editForm.put(route('central.tenants.update', selectedTenant.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const openCredentialsModal = (tenant) => {
    activeCredentials.value = {
        school_name: tenant.school_name,
        npsn: tenant.npsn,
        admin_name: tenant.admin_name,
        admin_email: tenant.admin_email,
        admin_password: '(Gunakan Sinkron Akun jika lupa sandi)',
        subdomain: tenant.domains && tenant.domains[0] ? tenant.domains[0].domain : '-',
        login_url: window.location.origin + '/login',
    };
    showCredsModal.value = true;
};

const triggerSyncAdmin = (tenant) => {
    if (confirm(`Sinkronkan akun administrator untuk sekolah "${tenant.school_name}"? Password baru akan digenerate otomatis.`)) {
        router.post(route('central.tenants.sync-admin', tenant.id), {}, {
            onSuccess: (page) => {
                if (page.props.flash?.credentials) {
                    activeCredentials.value = page.props.flash.credentials;
                    showCredsModal.value = true;
                }
            },
        });
    }
};

const triggerDelete = (tenant) => {
    if (confirm(`Apakah Anda yakin ingin menghapus sekolah "${tenant.school_name}"? Aksi ini akan mencabut seluruh domain dan data terkait.`)) {
        router.delete(route('central.tenants.destroy', tenant.id));
    }
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    copySuccess.value = true;
    setTimeout(() => {
        copySuccess.value = false;
    }, 2000);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const isTrialActive = (dateStr) => {
    if (!dateStr) return false;
    return new Date(dateStr) > new Date();
};
</script>

<template>
    <Head title="Manajemen Sekolah Mitra (Tenants) - Central SaaS" />

    <AuthenticatedLayout :menus="menus" section-title="SaaS Platform Central">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-800 dark:text-white tracking-wide">
                        Mitra Sekolah (Tenants)
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Pusat kendali multi-sekolah, pendaftaran instan, provisioning domain, dan lisensi SaaS.
                    </p>
                </div>
                <div>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/20 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Daftarkan Sekolah Baru
                    </button>
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- ===== KPI CARDS ===== -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Mitra Sekolah</span>
                            <span class="text-3xl font-black text-slate-900 dark:text-white mt-1 block">{{ stats.total_tenants }}</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Masa Trial Aktif</span>
                            <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1 block">{{ stats.active_trials }}</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Trial Berakhir / Expired</span>
                            <span class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-1 block">{{ stats.expired_trials }}</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SEARCH BAR ===== -->
            <div class="flex items-center justify-between gap-4 bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                <div class="relative flex-1 max-w-md">
                    <input
                        v-model="searchQuery"
                        @keyup.enter="handleSearch"
                        type="text"
                        placeholder="Cari nama sekolah, NPSN, atau email admin..."
                        class="w-full pl-10 pr-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                    <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <button
                    @click="handleSearch"
                    class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold transition"
                >
                    Filter
                </button>
            </div>

            <!-- ===== TENANTS TABLE ===== -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700/80 bg-slate-50/75 dark:bg-slate-900/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Instansi Sekolah</th>
                                <th class="py-3.5 px-4">Subdomain / URL</th>
                                <th class="py-3.5 px-4">Penanggung Jawab</th>
                                <th class="py-3.5 px-4">Kontak Admin</th>
                                <th class="py-3.5 px-4">Masa Trial</th>
                                <th class="py-3.5 px-4 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 text-xs">
                            <tr
                                v-for="tenant in tenants.data"
                                :key="tenant.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ tenant.school_name }}
                                    </div>
                                    <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-mono mt-0.5">
                                        NPSN: {{ tenant.npsn }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-mono font-medium bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ tenant.domains && tenant.domains[0] ? tenant.domains[0].domain : '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ tenant.admin_name }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-700 dark:text-slate-300">{{ tenant.admin_email }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ tenant.admin_phone || '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                            isTrialActive(tenant.trial_ends_at)
                                                ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300'
                                                : 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300'
                                        ]"
                                    >
                                        {{ formatDate(tenant.trial_ends_at) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            @click="openCredentialsModal(tenant)"
                                            title="Lihat Kredensial"
                                            class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="triggerSyncAdmin(tenant)"
                                            title="Sinkron / Reset Akun Admin"
                                            class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="openEditModal(tenant)"
                                            title="Edit Info Sekolah"
                                            class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="triggerDelete(tenant)"
                                            title="Hapus Sekolah"
                                            class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!tenants.data || tenants.data.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    Belum ada data sekolah yang terdaftar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== MODAL DAFTARKAN SEKOLAH ===== -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pendaftaran Sekolah Baru</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Resmi Sekolah</label>
                        <input
                            v-model="createForm.school_name"
                            required
                            type="text"
                            placeholder="Contoh: SMA Negeri 1 Bangsa"
                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <span v-if="createForm.errors.school_name" class="text-rose-500 text-[11px] mt-0.5">{{ createForm.errors.school_name }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NPSN</label>
                            <input
                                v-model="createForm.npsn"
                                required
                                type="text"
                                placeholder="8 digit NPSN"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                            <span v-if="createForm.errors.npsn" class="text-rose-500 text-[11px] mt-0.5">{{ createForm.errors.npsn }}</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Subdomain</label>
                            <div class="flex items-center">
                                <input
                                    v-model="createForm.subdomain"
                                    required
                                    type="text"
                                    placeholder="sman1"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono"
                                />
                            </div>
                            <span v-if="createForm.errors.subdomain" class="text-rose-500 text-[11px] mt-0.5">{{ createForm.errors.subdomain }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama PIC / Admin</label>
                            <input
                                v-model="createForm.admin_name"
                                required
                                type="text"
                                placeholder="Budi Santoso, S.Kom"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                            <span v-if="createForm.errors.admin_name" class="text-rose-500 text-[11px] mt-0.5">{{ createForm.errors.admin_name }}</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. WhatsApp PIC</label>
                            <input
                                v-model="createForm.admin_phone"
                                type="text"
                                placeholder="081234567890"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Login Admin</label>
                            <input
                                v-model="createForm.admin_email"
                                required
                                type="email"
                                placeholder="admin@sman1.sch.id"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                            <span v-if="createForm.errors.admin_email" class="text-rose-500 text-[11px] mt-0.5">{{ createForm.errors.admin_email }}</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Masa Trial (Hari)</label>
                            <input
                                v-model.number="createForm.trial_days"
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="pt-3 flex justify-end gap-2 border-t border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Memproses...' : 'Daftarkan & Generate Kredensial' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== MODAL EDIT SEKOLAH ===== -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Perbarui Data Sekolah</h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Resmi Sekolah</label>
                        <input
                            v-model="editForm.school_name"
                            required
                            type="text"
                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama PIC Admin</label>
                        <input
                            v-model="editForm.admin_name"
                            required
                            type="text"
                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Kontak / WhatsApp</label>
                        <input
                            v-model="editForm.admin_phone"
                            type="text"
                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Batas Masa Berlaku Trial</label>
                        <input
                            v-model="editForm.trial_ends_at"
                            type="date"
                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white"
                        />
                    </div>

                    <div class="pt-3 flex justify-end gap-2 border-t border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== MODAL KREDENSIAL LOGIN ===== -->
        <div v-if="showCredsModal && activeCredentials" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kredensial Akses Administrator</h3>
                    </div>
                    <button @click="showCredsModal = false" class="text-slate-400 hover:text-slate-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-3 bg-slate-50 dark:bg-slate-900/60 p-4 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Nama Sekolah</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">{{ activeCredentials.school_name }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">NPSN</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">{{ activeCredentials.npsn }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Subdomain</span>
                            <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ activeCredentials.subdomain }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Email Login PIC</span>
                        <span class="font-mono text-slate-900 dark:text-white font-semibold">{{ activeCredentials.admin_email }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Kata Sandi Baru</span>
                        <div class="flex items-center justify-between gap-2 bg-amber-50 dark:bg-amber-950/40 p-2.5 rounded-lg border border-amber-200 dark:border-amber-800/60 mt-1">
                            <span class="font-mono text-amber-900 dark:text-amber-300 font-bold select-all">{{ activeCredentials.admin_password }}</span>
                            <button
                                @click="copyToClipboard(activeCredentials.admin_password)"
                                class="text-[11px] text-amber-700 dark:text-amber-300 font-semibold hover:underline"
                            >
                                {{ copySuccess ? 'Tersalin!' : 'Salin' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        @click="showCredsModal = false"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition"
                    >
                        Tutup & Simpan
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
