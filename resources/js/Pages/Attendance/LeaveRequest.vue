<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    leaves: Array,
});

const showForm = ref(false);

const form = useForm({
    type: 'izin',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    reason: '',
});

const submitLeave = () => {
    form.post(route('attendance.leave.store'), {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 py-6 px-4">
        <Head title="Pengajuan Izin & Sakit (E-Leave)" />

        <div class="max-w-lg mx-auto space-y-5">
            <!-- Header Bar -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('dashboard')"
                    class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white transition text-xs flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Dashboard
                </Link>

                <h1 class="text-sm font-bold text-white">Pengajuan Izin & Sakit</h1>

                <button
                    type="button"
                    @click="showForm = !showForm"
                    class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-xs text-white shadow-md transition"
                >
                    {{ showForm ? 'Batal' : '+ Ajukan Izin' }}
                </button>
            </div>

            <!-- Formulir Pengajuan (Collapsible) -->
            <div v-if="showForm" class="rounded-2xl bg-slate-900 p-5 border border-indigo-500/30 shadow-xl space-y-4">
                <h2 class="text-sm font-bold text-white border-b border-slate-800 pb-2">
                    Formulir Surat Permohonan Izin / Sakit
                </h2>

                <form @submit.prevent="submitLeave" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Jenis Ketidakhadiran</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="form.type = 'izin'"
                                :class="['py-2 text-xs font-semibold rounded-xl border transition', form.type === 'izin' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-300']"
                            >
                                📝 Izin
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'sakit'"
                                :class="['py-2 text-xs font-semibold rounded-xl border transition', form.type === 'sakit' ? 'bg-rose-600 border-rose-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-300']"
                            >
                                🏥 Sakit
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'tugas_luar'"
                                :class="['py-2 text-xs font-semibold rounded-xl border transition', form.type === 'tugas_luar' ? 'bg-amber-600 border-amber-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-300']"
                            >
                                💼 Tugas Luar
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Tanggal Mulai</label>
                            <input
                                type="date"
                                v-model="form.start_date"
                                required
                                class="w-full rounded-xl bg-slate-800 border-slate-700 text-xs text-white p-2.5 focus:border-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Sampai Tanggal</label>
                            <input
                                type="date"
                                v-model="form.end_date"
                                required
                                class="w-full rounded-xl bg-slate-800 border-slate-700 text-xs text-white p-2.5 focus:border-indigo-500"
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
                            class="w-full rounded-xl bg-slate-800 border-slate-700 text-xs text-white p-3 focus:border-indigo-500"
                        ></textarea>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-xs text-white shadow-lg transition"
                        >
                            {{ form.processing ? 'Mengirim...' : 'Kirim Permohonan Izin' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Daftar Riwayat Pengajuan Izin -->
            <div class="rounded-2xl bg-slate-900/80 p-5 border border-slate-800 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                    Riwayat Pengajuan Anda
                </h3>

                <div v-if="leaves.length === 0" class="py-8 text-center text-xs text-slate-500">
                    Belum ada riwayat permohonan izin / sakit.
                </div>

                <div v-else class="space-y-2.5">
                    <div
                        v-for="leave in leaves"
                        :key="leave.id"
                        class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 space-y-1.5"
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
    </div>
</template>
