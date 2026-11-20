<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    plans: Array,
});

const menus = [
    { label: 'Sekolah Mitra (Tenants)', route: 'central.tenants.index', icon: 'database' },
    { label: 'Paket Langganan SaaS', route: 'central.plans.index', icon: 'rectangle-stack' },
    { label: 'Dashboard Operasional', route: 'dashboard', icon: 'home' },
];

const showModal = ref(false);
const isEditing = ref(false);
const selectedPlanId = ref(null);

const form = useForm({
    name: '',
    slug: '',
    price_monthly: 0,
    price_yearly: 0,
    max_students: 500,
    max_teachers: 50,
    max_storage_mb: 5120,
    is_active: true,
    is_popular: false,
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    selectedPlanId.value = null;
    form.reset();
    form.is_active = true;
    form.is_popular = false;
    showModal.value = true;
};

const openEditModal = (plan) => {
    isEditing.value = true;
    selectedPlanId.value = plan.id;
    form.name = plan.name;
    form.slug = plan.slug;
    form.price_monthly = plan.price_monthly;
    form.price_yearly = plan.price_yearly;
    form.max_students = plan.max_students;
    form.max_teachers = plan.max_teachers || 50;
    form.max_storage_mb = plan.max_storage_mb || 5120;
    form.is_active = Boolean(plan.is_active);
    form.is_popular = Boolean(plan.is_popular);
    form.description = plan.description || '';
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('central.plans.update', selectedPlanId.value), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('central.plans.store'), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const triggerDelete = (plan) => {
    if (confirm(`Hapus paket langganan "${plan.name}"?`)) {
        router.delete(route('central.plans.destroy', plan.id));
    }
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};
</script>

<template>
    <Head title="Paket Langganan SaaS - Central Management" />

    <AuthenticatedLayout :menus="menus" section-title="SaaS Platform Central">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-800 dark:text-white tracking-wide">
                        Paket Langganan (SaaS Plans)
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Katalog paket langganan, tarif bulanan/tahunan, kuota kapasitas, dan fitur lisensi.
                    </p>
                </div>
                <div>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/20 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Paket Baru
                    </button>
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- ===== PLANS CARDS GRID ===== -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="[
                        'rounded-2xl p-6 border transition-all relative flex flex-col justify-between',
                        plan.is_popular
                            ? 'bg-gradient-to-b from-indigo-50/50 to-white dark:from-indigo-950/20 dark:to-slate-800 border-indigo-500 shadow-md ring-1 ring-indigo-500/20'
                            : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700/60 shadow-sm'
                    ]"
                >
                    <div v-if="plan.is_popular" class="absolute -top-3 right-6 px-3 py-1 rounded-full bg-indigo-600 text-[10px] font-black uppercase tracking-wider text-white shadow-sm">
                        Terpopuler
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ plan.name }}</h3>
                                <span class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400 font-semibold">{{ plan.slug }}</span>
                            </div>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                    plan.is_active
                                        ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
                                ]"
                            >
                                {{ plan.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-3 line-clamp-2">
                            {{ plan.description || 'Paket solusi komprehensif untuk digitalisasi sekolah.' }}
                        </p>

                        <!-- Price block -->
                        <div class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-700/60">
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-black text-slate-900 dark:text-white">{{ formatRupiah(plan.price_monthly) }}</span>
                                <span class="text-xs text-slate-400">/ bulan</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                atau {{ formatRupiah(plan.price_yearly) }} / tahun
                            </div>
                        </div>

                        <!-- Quota stats -->
                        <div class="mt-4 space-y-2 text-xs">
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                <span>Maksimal Siswa</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ plan.max_students }} Siswa</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                <span>Maksimal Tenaga Pendidik</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ plan.max_teachers || 50 }} Guru</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                <span>Penyimpanan Aset</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ (plan.max_storage_mb || 5120) / 1024 }} GB</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300 pt-2 border-t border-slate-100 dark:border-slate-700/50">
                                <span>Langganan Sekolah Aktif</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ plan.subscriptions_count || 0 }} Sekolah</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-end gap-2">
                        <button
                            @click="openEditModal(plan)"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            Edit Paket
                        </button>
                        <button
                            @click="triggerDelete(plan)"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MODAL TAMBAH / EDIT PAKET ===== -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ isEditing ? 'Edit Paket Langganan' : 'Tambah Paket Baru' }}
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-3.5 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Paket</label>
                            <input
                                v-model="form.name"
                                required
                                type="text"
                                placeholder="Starter Plan"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Slug / Identitas Paket</label>
                            <input
                                v-model="form.slug"
                                required
                                type="text"
                                placeholder="starter"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tarif Bulanan (Rp)</label>
                            <input
                                v-model.number="form.price_monthly"
                                required
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tarif Tahunan (Rp)</label>
                            <input
                                v-model.number="form.price_yearly"
                                required
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Maks. Siswa</label>
                            <input
                                v-model.number="form.max_students"
                                required
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Maks. Guru</label>
                            <input
                                v-model.number="form.max_teachers"
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Storage (MB)</label>
                            <input
                                v-model.number="form.max_storage_mb"
                                type="number"
                                min="100"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Paket</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Deskripsi singkat keunggulan paket..."
                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-6 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-slate-700 dark:text-slate-300 font-medium">Aktif Dijual</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_popular"
                                type="checkbox"
                                class="rounded text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-slate-700 dark:text-slate-300 font-medium">Tandai Populer</span>
                        </label>
                    </div>

                    <div class="pt-3 flex justify-end gap-2 border-t border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold disabled:opacity-50"
                        >
                            {{ isEditing ? 'Simpan Perubahan' : 'Buat Paket Baru' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
