<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    payment: Object,
    school: Object,
});

const triggerPrint = () => {
    window.print();
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-800 py-8 px-4 flex flex-col items-center justify-start print:p-0 print:bg-white">
        <Head :title="'Kuitansi Pembayaran - ' + payment.payment_number" />

        <!-- Print Action Bar (Hidden during print) -->
        <div class="w-full max-w-2xl mb-4 flex items-center justify-between print:hidden">
            <button
                type="button"
                onclick="window.close()"
                class="px-3.5 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-xs font-semibold text-slate-700 transition"
            >
                ← Tutup Jendela
            </button>

            <button
                type="button"
                @click="triggerPrint"
                class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white shadow transition flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak Kuitansi Resmi (Ctrl + P)
            </button>
        </div>

        <!-- Official Receipt Document (A5 Landscape / Portrait Compatible) -->
        <div class="w-full max-w-2xl bg-white border border-slate-300 rounded-2xl shadow-xl p-8 print:border-none print:shadow-none print:p-4 print:rounded-none">
            <!-- School Header -->
            <div class="flex items-center justify-between border-b-2 border-slate-800 pb-4 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-900 text-white flex items-center justify-center font-black text-2xl">
                        IT
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-slate-900 uppercase tracking-tight">{{ school.name }}</h1>
                        <p class="text-xs text-slate-600">NPSN: {{ school.npsn }} • {{ school.address }}</p>
                        <p class="text-[11px] text-slate-500">Telp: {{ school.phone }} • Email: {{ school.email }}</p>
                    </div>
                </div>

                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded bg-emerald-100 text-emerald-800 text-xs font-black uppercase tracking-wider">
                        Kuitansi Sah
                    </span>
                    <div class="text-xs font-mono font-bold text-slate-700 mt-1">{{ payment.payment_number }}</div>
                    <div class="text-[10px] text-slate-500">{{ payment.payment_date }}</div>
                </div>
            </div>

            <!-- Student & Transaction Details Grid -->
            <div class="grid grid-cols-2 gap-4 text-xs mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div>
                    <span class="text-[10px] text-slate-500 uppercase font-semibold">Telah Diterima Dari:</span>
                    <div class="text-sm font-bold text-slate-900">{{ payment.student.name }}</div>
                    <div class="text-slate-600">NISN: {{ payment.student.nisn }}</div>
                    <div class="text-slate-600">Kelas: {{ payment.student.classroom }} ({{ payment.student.department }})</div>
                </div>

                <div class="text-right">
                    <span class="text-[10px] text-slate-500 uppercase font-semibold">Metode & Kasir:</span>
                    <div class="text-xs font-bold text-slate-800 uppercase">
                        {{ payment.payment_method === 'cash' ? 'Tunai / Cash' : payment.payment_method === 'wallet' ? 'Dompet Digital KTS' : payment.payment_method }}
                    </div>
                    <div class="text-slate-600">Petugas Kasir: {{ payment.cashier_name }}</div>
                    <div class="text-emerald-700 font-bold">Status: {{ payment.status.toUpperCase() }}</div>
                </div>
            </div>

            <!-- Itemized Bills Table -->
            <div class="mb-6">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-300 bg-slate-100 text-slate-700">
                            <th class="py-2.5 px-3 font-bold">No</th>
                            <th class="py-2.5 px-3 font-bold">Rincian Pos Pembayaran</th>
                            <th class="py-2.5 px-3 text-right font-bold">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="(item, idx) in payment.items" :key="idx">
                            <td class="py-2 px-3 text-slate-500">{{ idx + 1 }}</td>
                            <td class="py-2 px-3 font-semibold text-slate-800">{{ item.title }}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">{{ item.amount_formatted }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-800 font-bold bg-slate-50">
                            <td colspan="2" class="py-3 px-3 text-slate-800 uppercase tracking-wide">Total Pembayaran Lunas</td>
                            <td class="py-3 px-3 text-right font-mono text-base font-black text-emerald-700">
                                {{ payment.amount_formatted }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Terbilang Box -->
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl mb-6 text-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 block mb-0.5">Terbilang:</span>
                <span class="font-bold text-emerald-950 italic">"{{ payment.terbilang }}"</span>
            </div>

            <!-- Signatures & Authentic Verification Stamp -->
            <div class="grid grid-cols-3 gap-4 text-xs items-end pt-4">
                <!-- QR Code Verification -->
                <div class="flex flex-col items-center justify-center p-2 rounded-lg border border-slate-200 bg-slate-50 text-center">
                    <div class="w-20 h-20 bg-white p-1 border border-slate-300 rounded flex items-center justify-center font-mono text-[9px] text-center text-slate-400">
                        [QR VERIFIED<br>{{ payment.payment_number }}]
                    </div>
                    <span class="text-[9px] text-slate-500 mt-1">Scan untuk Cek Keaslian</span>
                </div>

                <!-- Payer Signature -->
                <div class="text-center">
                    <span class="text-[11px] text-slate-500">Penyetor / Siswa</span>
                    <div class="h-16"></div>
                    <span class="font-bold text-slate-800 underline block">{{ payment.student.name }}</span>
                </div>

                <!-- Treasurer Digital Stamp & Signature -->
                <div class="text-center relative">
                    <div class="absolute inset-0 flex items-center justify-center opacity-25 pointer-events-none">
                        <div class="w-20 h-20 rounded-full border-2 border-dashed border-emerald-600 flex items-center justify-center text-[10px] font-black uppercase text-emerald-800 transform rotate-12">
                            LUNAS
                        </div>
                    </div>

                    <span class="text-[11px] text-slate-500">Bendahara Penerima</span>
                    <div class="h-16 flex items-center justify-center font-serif italic text-slate-400 text-xs">
                        (Ditandatangani Digital)
                    </div>
                    <span class="font-bold text-slate-800 underline block">{{ school.treasurer }}</span>
                </div>
            </div>

            <!-- Footer Disclaimer -->
            <div class="mt-6 pt-3 border-t border-slate-200 text-center text-[10px] text-slate-400">
                Dokumen ini merupakan bukti pembayaran sah yang dihasilkan oleh Sistem Informasi Keuangan Sekolah Terpadu.
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body {
        background-color: white !important;
    }
}
</style>
