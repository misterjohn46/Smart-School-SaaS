<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    student: Object,
    template: Object,
    school: Object,
});

const isFlipped = ref(false);

const printCard = () => {
    window.print();
};
</script>

<template>
    <div class="min-h-screen bg-slate-900 text-slate-100 py-10 px-4 flex flex-col items-center justify-center print:p-0 print:bg-white">
        <Head :title="'KTS Digital - ' + student.name" />

        <!-- Header Actions (Hidden when printing) -->
        <div class="w-full max-w-md mb-6 flex items-center justify-between print:hidden">
            <Link
                :href="route('dashboard')"
                class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="isFlipped = !isFlipped"
                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-indigo-300 border border-slate-700 transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Putar Kartu ({{ isFlipped ? 'Belakang' : 'Depan' }})
                </button>

                <button
                    type="button"
                    @click="printCard"
                    class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow-lg transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak KTS
                </button>
            </div>
        </div>

        <!-- Perspectived Card Container: ISO CR-80 Format (85.6mm x 54mm -> scaled for high DPI screen: 350px x 220px) -->
        <div class="relative w-[350px] h-[220px] [perspective:1000px] print:w-[85.6mm] print:h-[54mm] print:m-auto">
            <div
                class="w-full h-full relative transition-all duration-500 [transform-style:preserve-3d] shadow-2xl rounded-2xl print:shadow-none"
                :class="{ '[transform:rotateY(180deg)]': isFlipped }"
            >
                <!-- ================= SISI DEPAN (FRONT) ================= -->
                <div
                    class="absolute inset-0 w-full h-full rounded-2xl overflow-hidden [backface-visibility:hidden] p-4 flex flex-col justify-between text-white border border-indigo-500/30"
                    :style="{
                        background: 'linear-gradient(135deg, ' + (template.primary_color || '#4f46e5') + ' 0%, #1e1b4b 60%, #0f172a 100%)'
                    }"
                >
                    <!-- Background Decorative Circles -->
                    <div class="absolute -top-10 -right-10 w-36 h-36 bg-amber-500/20 rounded-full blur-xl"></div>
                    <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-indigo-500/20 rounded-full blur-lg"></div>

                    <!-- Header Kartu -->
                    <div class="relative z-10 flex items-center justify-between border-b border-white/20 pb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur-md flex items-center justify-center font-black text-amber-300 text-xs border border-white/30">
                                SMK
                            </div>
                            <div>
                                <h3 class="text-[11px] font-black tracking-wider leading-tight text-white uppercase">{{ school.name }}</h3>
                                <p class="text-[8px] text-indigo-200 tracking-tight">NPSN: {{ school.npsn }} • KARTU TANDA SISWA</p>
                            </div>
                        </div>

                        <!-- RFID Chip Indicator -->
                        <div class="flex items-center gap-1 text-[8px] font-mono text-amber-300 bg-amber-500/10 px-1.5 py-0.5 rounded border border-amber-400/30">
                            <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            13.56 MHz
                        </div>
                    </div>

                    <!-- Body: Foto & Biodata -->
                    <div class="relative z-10 flex items-center gap-3 my-auto">
                        <!-- Foto Siswa dengan Border Hologram -->
                        <div class="w-16 h-20 rounded-lg overflow-hidden bg-slate-800 border-2 border-amber-400/70 shadow-md shrink-0 flex items-center justify-center text-center">
                            <span class="text-2xl font-black text-white/50">{{ student.name.charAt(0) }}</span>
                        </div>

                        <!-- Biodata Singkat -->
                        <div class="overflow-hidden space-y-0.5 text-left">
                            <h4 class="text-xs font-black text-white truncate leading-tight tracking-wide">{{ student.name }}</h4>
                            <p class="text-[9px] text-amber-300 font-mono font-bold tracking-wider">NISN: {{ student.nisn }}</p>
                            <p class="text-[9px] text-slate-200 truncate">{{ student.classroom }} ({{ student.department }})</p>
                            <p class="text-[8px] text-slate-300">{{ student.birth_place }}, {{ student.birth_date }}</p>
                        </div>
                    </div>

                    <!-- Footer: UID RFID & Smart Badge -->
                    <div class="relative z-10 flex items-end justify-between border-t border-white/20 pt-2 text-[8px]">
                        <div>
                            <span class="text-slate-300 block text-[7px] uppercase font-semibold">RFID Card UID</span>
                            <span class="font-mono text-emerald-300 font-bold tracking-widest">{{ student.rfid_uid }}</span>
                        </div>
                        <div class="bg-white/10 px-2 py-0.5 rounded font-mono font-bold text-white text-[8px] tracking-wider uppercase border border-white/10">
                            BERLAKU: SISWA AKTIF
                        </div>
                    </div>
                </div>

                <!-- ================= SISI BELAKANG (BACK) ================= -->
                <div
                    class="absolute inset-0 w-full h-full rounded-2xl overflow-hidden [backface-visibility:hidden] [transform:rotateY(180deg)] p-4 flex flex-col justify-between text-slate-900 border border-slate-300"
                    style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);"
                >
                    <!-- Ketentuan Kartu -->
                    <div>
                        <h4 class="text-[9px] font-black uppercase tracking-wider text-indigo-900 border-b border-slate-300 pb-1 text-center">
                            KETENTUAN PENGGUNAAN KARTU
                        </h4>
                        <ol class="text-[7.5px] text-slate-600 list-decimal pl-3.5 mt-1.5 space-y-0.5 leading-tight">
                            <li>Kartu ini wajib dibawa saat menghadiri kegiatan sekolah.</li>
                            <li>Gunakan kartu untuk presensi tap RFID di gerbang & absensi kelas.</li>
                            <li>Kartu ini juga berfungsi sebagai dompet transaksi E-Kantin & Perpustakaan.</li>
                            <li>Bila menemukan kartu ini, harap hubungi TU {{ school.name }}.</li>
                        </ol>
                    </div>

                    <!-- Barcode Vektor Perpustakaan & NISN -->
                    <div class="my-auto text-center py-1 bg-white/70 rounded-lg border border-slate-200">
                        <div class="font-mono text-[8px] font-bold text-slate-700 tracking-[0.25em] select-none">
                            ||| | |||| | ||| |||| | || | ||||
                        </div>
                        <span class="font-mono text-[8px] text-slate-500 tracking-widest block -mt-0.5">
                            *{{ student.nisn }}*
                        </span>
                    </div>

                    <!-- Legalitas: TTD Kepala Sekolah & QR Code Verifikasi -->
                    <div class="flex items-end justify-between border-t border-slate-300 pt-1 text-[7.5px]">
                        <!-- QR Code Digital Verifikasi -->
                        <div class="w-10 h-10 bg-white p-1 rounded border border-slate-300 flex items-center justify-center font-mono text-[6px] text-center font-bold leading-tight">
                            QR SECURE
                        </div>

                        <!-- Tanda Tangan Digital Kepala Sekolah -->
                        <div class="text-center">
                            <span class="block text-[7px] text-slate-500">Jakarta, 15 Juli 2024</span>
                            <span class="block text-[7px] font-bold text-slate-700">{{ template.signatory_title }}</span>
                            <div class="my-0.5 text-indigo-700 font-serif italic text-[9px] font-bold">
                                𝔅𝔞𝔪𝔟𝔞𝔫𝔤 𝔖.
                            </div>
                            <span class="block font-bold text-slate-900 underline text-[7.5px] leading-none">{{ template.signatory_name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instruction Helper (Hidden when printing) -->
        <p class="text-xs text-slate-400 mt-6 text-center max-w-sm print:hidden">
            Format kartu dioptimalkan sesuai standar <strong>ISO/IEC 7810 CR-80</strong> (85.6 mm × 54.0 mm). Klik <em>Putar Kartu</em> untuk memeriksa sisi depan & belakang.
        </p>
    </div>
</template>

<style>
@media print {
    @page {
        size: 85.6mm 54mm;
        margin: 0;
    }
    body {
        margin: 0;
        padding: 0;
        background: white !important;
    }
}
</style>
