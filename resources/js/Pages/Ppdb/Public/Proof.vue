<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    registration: Object,
});

const printCard = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Tanda Bukti Pendaftaran - ${registration.registration_number}`" />

    <div class="min-h-screen bg-slate-100 text-slate-800 font-sans py-8 px-4 print:p-0 print:bg-white">
        <!-- Floating Actions Bar (Hidden on print) -->
        <div class="max-w-3xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
            <Link
                :href="route('ppdb.public.index')"
                class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs"
            >
                <span>←</span> Beranda PPDB
            </Link>

            <div class="flex items-center gap-2">
                <Link
                    :href="route('ppdb.public.check')"
                    class="px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                >
                    <span>🔍</span> Cek Status Kelulusan
                </Link>
                <button
                    @click="printCard"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5"
                >
                    <span>🖨️</span> Cetak / Simpan PDF
                </button>
            </div>
        </div>

        <!-- Kartu Bukti Pendaftaran Resmi (A4 layout) -->
        <div class="max-w-3xl mx-auto bg-white rounded-3xl border border-slate-300 shadow-md p-8 sm:p-12 print:border-0 print:shadow-none print:p-4 print:max-w-none">
            <!-- Kop Dokumen Resmi -->
            <div class="border-b-2 border-slate-900 pb-4 text-center relative">
                <div class="flex items-center justify-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center font-black text-white text-2xl shadow-sm">
                        S
                    </div>
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-500">KARTU TANDA BUKTI PENDAFTARAN</h2>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">PENERIMAAN PESERTA DIDIK BARU (PPDB)</h1>
                        <p class="text-xs text-slate-600 font-semibold">{{ registration.batch_name }} — Tahun Ajaran {{ registration.academic_year }}</p>
                    </div>
                </div>
            </div>

            <!-- Banner Nomor Registrasi -->
            <div class="mt-6 bg-slate-900 text-white p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">NOMOR PENDAFTARAN RESMI</span>
                    <span class="text-2xl sm:text-3xl font-mono font-black text-amber-400 tracking-wider block">
                        {{ registration.registration_number }}
                    </span>
                    <span class="text-[11px] text-slate-300 mt-0.5 block">
                        Tanggal Daftar: {{ registration.created_at }}
                    </span>
                </div>

                <div class="text-right sm:border-l sm:border-slate-800 sm:pl-6">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">KODE AKSES / PIN CEK STATUS</span>
                    <span class="text-xl font-mono font-black text-emerald-400 tracking-widest block">
                        {{ registration.access_token }}
                    </span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Rahasia (simpan baik-baik)</span>
                </div>
            </div>

            <!-- Data Diri Siswa -->
            <div class="mt-6 space-y-4 text-xs">
                <h3 class="font-extrabold text-slate-900 uppercase tracking-wider text-[11px] border-b border-slate-200 pb-1 flex items-center gap-1.5">
                    <span>👤</span> DATA PRIBADI CALON PESERTA DIDIK
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5">
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Nama Lengkap:</span>
                        <span class="font-bold text-slate-900">{{ registration.full_name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Jenis Kelamin:</span>
                        <span class="font-semibold text-slate-800">{{ registration.gender }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">NISN / NIK:</span>
                        <span class="font-mono font-semibold text-slate-800">{{ registration.nisn }} / {{ registration.nik }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Tempat, Tanggal Lahir:</span>
                        <span class="font-semibold text-slate-800">{{ registration.ttl }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Asal Sekolah:</span>
                        <span class="font-bold text-slate-800">{{ registration.previous_school }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Nomor WhatsApp:</span>
                        <span class="font-semibold text-slate-800">{{ registration.phone_number }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1 sm:col-span-2">
                        <span class="text-slate-500">Alamat Tempat Tinggal:</span>
                        <span class="font-semibold text-slate-800 text-right">{{ registration.address }}</span>
                    </div>
                </div>
            </div>

            <!-- Peminatan & Seleksi -->
            <div class="mt-6 space-y-4 text-xs">
                <h3 class="font-extrabold text-slate-900 uppercase tracking-wider text-[11px] border-b border-slate-200 pb-1 flex items-center gap-1.5">
                    <span>🎯</span> PILIHAN JALUR & PEMINATAN
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5">
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Jalur Pendaftaran:</span>
                        <span class="font-bold text-indigo-700">{{ registration.track_label }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Pilihan Jurusan 1:</span>
                        <span class="font-bold text-slate-900">{{ registration.department }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Pilihan Jurusan 2:</span>
                        <span class="font-semibold text-slate-800">{{ registration.second_department }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Nama Orang Tua / Wali:</span>
                        <span class="font-semibold text-slate-800">{{ registration.parent_name }} ({{ registration.parent_phone }})</span>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Penting -->
            <div class="mt-8 bg-slate-50 border border-slate-200 p-4 rounded-2xl text-xs space-y-2">
                <h4 class="font-bold text-slate-800 flex items-center gap-1">
                    <span>📌</span> PETUNJUK & INFORMASI PENTING:
                </h4>
                <ol class="list-decimal pl-5 space-y-1 text-slate-600 leading-relaxed text-[11px]">
                    <li>Simpan kartu bukti ini dengan baik. Nomor pendaftaran dan PIN digunakan untuk mengecek status seleksi.</li>
                    <li>Pengumuman hasil verifikasi berkas dan kelulusan seleksi dapat diakses di menu <strong>Cek Status PPDB</strong>.</li>
                    <li>Perkiraan pengumuman kelulusan: <strong>{{ registration.announcement_date }}</strong>.</li>
                    <li>Calon siswa yang dinyatakan <strong>DITERIMA</strong> wajib melakukan proses daftar ulang sesuai ketentuan panitia sekolah.</li>
                </ol>
            </div>

            <!-- Tanda Tangan Print -->
            <div class="mt-10 pt-4 flex justify-between items-end text-xs text-slate-600">
                <div class="text-center w-48">
                    <p>Calon Peserta Didik,</p>
                    <div class="h-16"></div>
                    <p class="font-bold border-t border-slate-400 pt-1 text-slate-900">({{ registration.full_name }})</p>
                </div>

                <div class="text-center w-48">
                    <p>Panitia PPDB Sekolah,</p>
                    <div class="h-16"></div>
                    <p class="font-bold border-t border-slate-400 pt-1 text-slate-900">Panitia Penerimaan</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page {
        margin: 1cm;
        size: A4 portrait;
    }
}
</style>
