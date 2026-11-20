<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    batches: Array,
    departments: Array,
});

const tracks = [
    {
        title: 'Jalur Zonasi',
        icon: '📍',
        desc: 'Diperuntukkan bagi calon peserta didik yang berdomisili di dalam wilayah zonasi terdekat dari sekolah.',
        color: 'from-blue-500 to-indigo-600',
    },
    {
        title: 'Jalur Prestasi',
        icon: '🏆',
        desc: 'Berdasarkan nilai akumulasi rapor 5 semester dan/atau prestasi kejuaraan akademik maupun non-akademik.',
        color: 'from-amber-500 to-orange-600',
    },
    {
        title: 'Jalur Afirmasi',
        icon: '🤝',
        desc: 'Ditujukan bagi calon peserta didik dari keluarga ekonomi tidak mampu (pemegang KIP/PKH/KKS) & disabilitas.',
        color: 'from-emerald-500 to-teal-600',
    },
    {
        title: 'Jalur Reguler / Umum',
        icon: '📝',
        desc: 'Jalur umum dan perpindahan tugas orang tua/wali dengan proses seleksi administrasi terpadu sekolah.',
        color: 'from-purple-500 to-violet-600',
    },
];

const steps = [
    { step: 1, title: 'Pilih Jalur & Gelombang', desc: 'Tentukan jalur pendaftaran dan gelombang yang sedang aktif.' },
    { step: 2, title: 'Isi Formulir Online', desc: 'Lengkapi biodata calon siswa, data orang tua/wali, serta peminatan jurusan.' },
    { step: 3, title: 'Unggah Berkas', desc: 'Unggah scan Pas Foto, KK, Akta Kelahiran, dan Ijazah / SKL.' },
    { step: 4, title: 'Cetak Bukti Daftar', desc: 'Dapatkan nomor pendaftaran resmi dan simpan kartu tanda bukti.' },
    { step: 5, title: 'Pengumuman & Daftar Ulang', desc: 'Cek kelulusan seleksi secara mandiri dan konfirmasi daftar ulang.' },
];
</script>

<template>
    <Head title="PPDB Online - Penerimaan Peserta Didik Baru" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-indigo-500 selection:text-white">
        <!-- Top Navbar Publik -->
        <header class="bg-white/90 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-amber-500 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-indigo-500/20">
                        S
                    </div>
                    <div>
                        <span class="text-base font-extrabold text-slate-900 tracking-tight block leading-tight">PPDB ONLINE</span>
                        <span class="text-[11px] text-slate-500 font-semibold tracking-wider uppercase block">Sistem Informasi Sekolah</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('ppdb.public.check')"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                    >
                        <span>🔍</span> Cek Status Pendaftaran
                    </Link>
                    <Link
                        :href="route('ppdb.public.register')"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black shadow-md shadow-indigo-600/30 transition-all flex items-center gap-1.5"
                    >
                        <span>✍️</span> Daftar Sekarang
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28 bg-gradient-to-b from-indigo-50/60 via-white to-slate-50 border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 mb-6">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Tahun Ajaran Baru Telah Dibuka
                </span>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-tight max-w-4xl mx-auto">
                    Penerimaan Peserta Didik Baru <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-amber-500">(PPDB Online)</span>
                </h1>

                <p class="text-base sm:text-lg text-slate-600 mt-5 max-w-2xl mx-auto leading-relaxed">
                    Selamat datang di portal resmi pendaftaran calon siswa baru. Proses pendaftaran mudah, transparan, dan dapat dipantau langsung secara real-time dari rumah.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        :href="route('ppdb.public.register')"
                        class="w-full sm:w-auto px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-sm font-black shadow-xl shadow-indigo-600/30 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2"
                    >
                        <span>📝</span> Mulai Pendaftaran Online
                    </Link>
                    <Link
                        :href="route('ppdb.public.check')"
                        class="w-full sm:w-auto px-8 py-4 bg-white hover:bg-slate-100 border border-slate-300 text-slate-800 rounded-2xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2"
                    >
                        <span>🔍</span> Pengecekan Hasil Seleksi
                    </Link>
                </div>
            </div>
        </section>

        <!-- Gelombang Pendaftaran Aktif -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Jadwal & Gelombang Pendaftaran</h2>
                <p class="text-sm text-slate-500 mt-2">Pilih gelombang pendaftaran yang sedang dibuka untuk memulai registrasi online.</p>
            </div>

            <div v-if="batches.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-200 p-8 shadow-xs">
                <span class="text-5xl block">📅</span>
                <h3 class="text-base font-bold text-slate-700 mt-3">Belum Ada Gelombang Pendaftaran yang Dibuka</h3>
                <p class="text-xs text-slate-400 mt-1">Silakan pantau halaman ini secara berkala untuk jadwal resmi pembukaan PPDB.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="b in batches"
                    :key="b.id"
                    class="bg-white rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-lg transition-all p-6 flex flex-col justify-between space-y-5"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ b.academic_year }}
                            </span>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                    b.is_open ? 'bg-emerald-100 text-emerald-800 animate-pulse' : 'bg-slate-100 text-slate-500'
                                ]"
                            >
                                {{ b.is_open ? '● Sedang Dibuka' : 'Ditutup' }}
                            </span>
                        </div>

                        <h3 class="text-lg font-black text-slate-900 mt-3 leading-snug">{{ b.name }}</h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ b.description || 'Penerimaan peserta didik baru terpadu.' }}</p>

                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>📅 Periode:</span>
                                <span class="font-bold text-slate-800">{{ b.start_date }} - {{ b.end_date }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>📢 Pengumuman:</span>
                                <span class="font-bold text-indigo-600">{{ b.announcement_date || 'Sesuai Jadwal' }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>👥 Kuota Tersedia:</span>
                                <span class="font-bold text-emerald-600">{{ b.quota_remaining }} dari {{ b.quota }} kursi</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>💳 Biaya Formulir:</span>
                                <span class="font-bold text-slate-900">
                                    {{ b.registration_fee > 0 ? `Rp ${b.registration_fee.toLocaleString('id-ID')}` : 'GRATIS' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <Link
                        v-if="b.is_open"
                        :href="route('ppdb.public.register', { batch_id: b.id })"
                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black text-center shadow-md shadow-indigo-600/20 transition-all block"
                    >
                        Daftar Gelombang Ini →
                    </Link>
                    <button
                        v-else
                        disabled
                        class="w-full py-3 bg-slate-100 text-slate-400 rounded-xl text-xs font-bold text-center cursor-not-allowed"
                    >
                        Pendaftaran Selesai
                    </button>
                </div>
            </div>
        </section>

        <!-- Jalur Pendaftaran Section -->
        <section class="bg-white py-16 border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">4 Jalur Pendaftaran Resmi</h2>
                    <p class="text-sm text-slate-500 mt-2">Pilih jalur yang sesuai dengan kriteria dan berkas pendukung calon peserta didik.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="t in tracks"
                        :key="t.title"
                        class="p-6 rounded-3xl border border-slate-200/90 bg-slate-50/50 hover:bg-white hover:shadow-lg transition-all space-y-3"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-2xl shadow-xs">
                            {{ t.icon }}
                        </div>
                        <h3 class="font-extrabold text-slate-900 text-base">{{ t.title }}</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">{{ t.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Alur Pendaftaran 5 Langkah -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Alur Pendaftaran Mudah & Cepat</h2>
                <p class="text-sm text-slate-500 mt-2">Hanya 5 tahapan dari pendaftaran awal hingga pengumuman penerimaan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div
                    v-for="s in steps"
                    :key="s.step"
                    class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs relative flex flex-col justify-between space-y-3"
                >
                    <div>
                        <span class="w-8 h-8 rounded-xl bg-indigo-600 text-white font-black text-xs flex items-center justify-center mb-3">
                            {{ s.step }}
                        </span>
                        <h4 class="font-bold text-slate-800 text-sm leading-snug">{{ s.title }}</h4>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">{{ s.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-900 text-slate-400 py-10 border-t border-slate-800 text-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-white">SIAKAD & PPDB Online</span>
                    <span>— Hak Cipta Terlindungi</span>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="route('ppdb.public.check')" class="hover:text-white transition-colors">Cek Status</Link>
                    <Link :href="route('login')" class="hover:text-white transition-colors">Login Admin / Guru</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
