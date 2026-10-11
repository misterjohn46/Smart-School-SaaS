<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    students: Array,
    template: Object,
    classrooms: Array,
});

const printSheet = () => {
    window.print();
};
</script>

<template>
    <div class="min-h-screen bg-slate-900 text-slate-100 p-6 print:p-0 print:bg-white">
        <Head title="Cetak Massal KTS Digital (Format Kertas A4)" />

        <!-- Control Bar (Hidden when printing) -->
        <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between print:hidden">
            <div>
                <h1 class="text-lg font-bold text-white">Cetak Massal Kartu Siswa (KTS)</h1>
                <p class="text-xs text-slate-400">Format tata letak lembar A4 (8 kartu per halaman siap potong)</p>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    :href="route('dashboard')"
                    class="px-3 py-1.5 rounded-lg bg-slate-800 text-xs font-semibold text-slate-300 hover:text-white transition"
                >
                    Kembali
                </Link>
                <button
                    type="button"
                    @click="printSheet"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-xs text-white shadow-lg transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Lembar A4 (PDF)
                </button>
            </div>
        </div>

        <!-- Printable A4 Sheet Grid: 2 columns x 4 rows = 8 cards -->
        <div class="max-w-4xl mx-auto bg-white text-slate-900 p-6 rounded-2xl shadow-xl print:p-0 print:shadow-none print:max-w-none">
            <div class="grid grid-cols-2 gap-4 print:gap-2">
                <div
                    v-for="(student, idx) in students"
                    :key="idx"
                    class="w-[340px] h-[214px] rounded-xl overflow-hidden p-3.5 flex flex-col justify-between text-white border border-indigo-400/40 print:w-[85.6mm] print:h-[54mm] print:rounded-lg print:border-gray-400 print:text-white"
                    style="background: linear-gradient(135deg, #4f46e5 0%, #1e1b4b 60%, #0f172a 100%);"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/20 pb-1.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-6 h-6 rounded bg-white/20 flex items-center justify-center font-black text-amber-300 text-[10px]">
                                SMK
                            </div>
                            <div>
                                <h4 class="text-[9px] font-black tracking-wider text-white uppercase leading-none">SMK INFORMATIKA TERPADU</h4>
                                <span class="text-[7px] text-indigo-200">KARTU TANDA SISWA</span>
                            </div>
                        </div>
                        <span class="text-[7px] font-mono text-amber-300 font-bold bg-amber-500/20 px-1 py-0.5 rounded">
                            13.56 MHz
                        </span>
                    </div>

                    <!-- Body -->
                    <div class="flex items-center gap-2.5 my-auto">
                        <div class="w-14 h-16 rounded bg-slate-800 border border-amber-400/60 flex items-center justify-center font-bold text-white text-sm shrink-0">
                            {{ student.name.charAt(0) }}
                        </div>
                        <div class="overflow-hidden space-y-0.5 text-left">
                            <h5 class="text-[11px] font-black text-white truncate leading-tight">{{ student.name }}</h5>
                            <p class="text-[8px] text-amber-300 font-mono font-bold">NISN: {{ student.nisn }}</p>
                            <p class="text-[8px] text-slate-200 truncate">{{ student.classroom }} • {{ student.department }}</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-end justify-between border-t border-white/20 pt-1 text-[7.5px]">
                        <span class="font-mono text-emerald-300 font-bold">{{ student.rfid_uid }}</span>
                        <span class="font-mono font-bold text-white uppercase">STATUS: SISWA AKTIF</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
</style>
