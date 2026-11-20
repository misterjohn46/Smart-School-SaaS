<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    filters: Object,
    searched: Boolean,
    result: Object,
});

const regNumber = ref(props.filters?.registration_number || '');
const birthDate = ref(props.filters?.birth_date || '');
const isSearching = ref(false);

const searchStatus = () => {
    if (!regNumber.value || !birthDate.value) {
        alert('Mohon isi Nomor Pendaftaran dan Tanggal Lahir.');
        return;
    }

    isSearching.value = true;
    router.get(route('ppdb.public.check'), {
        registration_number: regNumber.value,
        birth_date: birthDate.value,
    }, {
        preserveState: true,
        onFinish: () => isSearching.value = false,
    });
};
</script>

<template>
    <Head title="Cek Status Pendaftaran PPDB Online" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans pb-20">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
            <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
                <Link :href="route('ppdb.public.index')" class="flex items-center gap-2 text-slate-600 hover:text-slate-900 text-xs font-bold transition-colors">
                    <span>←</span> Beranda PPDB
                </Link>
                <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600">Portal Hasil Seleksi</span>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 pt-10">
            <!-- Search Card -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-5">
                <div class="text-center max-w-md mx-auto">
                    <span class="text-4xl block mb-2">🔍</span>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Cek Status Seleksi PPDB</h1>
                    <p class="text-xs text-slate-500 mt-1">Masukkan Nomor Pendaftaran dan Tanggal Lahir calon siswa untuk melihat hasil verifikasi berkas dan pengumuman.</p>
                </div>

                <form @submit.prevent="searchStatus" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Pendaftaran Resmi</label>
                        <input
                            v-model="regNumber"
                            type="text"
                            placeholder="Contoh: PPDB-2026-0001"
                            required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-2xl text-xs font-mono font-bold tracking-wider focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir Calon Siswa</label>
                        <input
                            v-model="birthDate"
                            type="date"
                            required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-2xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="isSearching"
                        class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-2xl text-xs font-black shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center gap-2"
                    >
                        <span>{{ isSearching ? 'Mencari Data...' : 'Cari Status Pendaftaran' }}</span>
                    </button>
                </form>
            </div>

            <!-- Hasil Pencarian -->
            <div v-if="searched" class="mt-8 animate-in fade-in zoom-in-95 duration-200">
                <!-- Tidak Ditemukan -->
                <div v-if="!result" class="bg-rose-50 border border-rose-200 p-6 rounded-3xl text-center space-y-2">
                    <span class="text-3xl block">❌</span>
                    <h3 class="font-black text-rose-800 text-sm">Data Pendaftaran Tidak Ditemukan</h3>
                    <p class="text-xs text-rose-600 max-w-sm mx-auto">
                        Kombinasi Nomor Pendaftaran dan Tanggal Lahir tidak cocok. Mohon periksa kembali data Anda sesuai Kartu Tanda Bukti Pendaftaran.
                    </p>
                </div>

                <!-- Ditemukan -->
                <div v-else class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden space-y-6 p-6 sm:p-8">
                    <!-- Status Banner -->
                    <div
                        :class="[
                            'p-5 rounded-2xl border flex flex-col sm:flex-row items-center justify-between gap-4',
                            result.status === 'accepted' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' :
                            result.status === 'enrolled' ? 'bg-purple-50 border-purple-200 text-purple-900' :
                            result.status === 'rejected' ? 'bg-rose-50 border-rose-200 text-rose-900' :
                            result.status === 'verified' ? 'bg-blue-50 border-blue-200 text-blue-900' :
                            result.status === 'not_accepted' ? 'bg-slate-100 border-slate-200 text-slate-700' :
                            'bg-amber-50 border-amber-200 text-amber-900'
                        ]"
                    >
                        <div class="text-center sm:text-left">
                            <span class="text-[10px] font-bold uppercase tracking-wider block opacity-75">STATUS KELULUSAN SELEKSI</span>
                            <span class="text-base sm:text-lg font-black tracking-tight block mt-0.5">
                                {{ result.status_label }}
                            </span>
                            <span v-if="result.enrolled_classroom" class="text-xs font-bold block mt-1">
                                Rombel / Kelas Baru: {{ result.enrolled_classroom }}
                            </span>
                        </div>

                        <div class="shrink-0">
                            <span
                                v-if="result.status === 'accepted' || result.status === 'enrolled'"
                                class="text-3xl"
                            >
                                🎉
                            </span>
                            <span v-else-if="result.status === 'rejected'" class="text-3xl">⚠️</span>
                            <span v-else class="text-3xl">⏳</span>
                        </div>
                    </div>

                    <!-- Alasan Penolakan jika rejected -->
                    <div v-if="result.status === 'rejected' && result.rejection_reason" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 space-y-1">
                        <span class="font-bold block">Catatan Penolakan / Perbaikan Berkas:</span>
                        <p>{{ result.rejection_reason }}</p>
                    </div>

                    <!-- Rincian Data Siswa -->
                    <div class="space-y-3 text-xs">
                        <h4 class="font-extrabold text-slate-900 uppercase tracking-wider text-[11px] border-b border-slate-100 pb-1">
                            Rincian Pendaftar
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-slate-400">Nomor Registrasi:</span>
                                <span class="font-mono font-bold text-slate-900">{{ result.registration_number }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-slate-400">Nama Lengkap:</span>
                                <span class="font-bold text-slate-900">{{ result.full_name }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-slate-400">Asal Sekolah:</span>
                                <span class="font-semibold text-slate-800">{{ result.previous_school }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-slate-400">Pilihan Jurusan:</span>
                                <span class="font-semibold text-slate-800">{{ result.department }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-slate-400">Jalur:</span>
                                <span class="font-bold uppercase text-indigo-600">{{ result.track }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-slate-400">Gelombang:</span>
                                <span class="font-semibold text-slate-800">{{ result.batch_name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <Link
                            :href="route('ppdb.public.proof', result.registration_number)"
                            class="px-5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                        >
                            <span>🖨️</span> Cetak Ulang Tanda Bukti
                        </Link>

                        <a
                            href="https://wa.me/?text=Halo%20panitia%20PPDB"
                            target="_blank"
                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5"
                        >
                            <span>💬</span> Hubungi Panitia
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
