<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    plans: {
        type: Array,
        default: () => [],
    },
    laravelVersion: String,
    phpVersion: String,
});

// State
const billingCycle = ref('monthly'); // 'monthly' | 'yearly'
const showRegisterModal = ref(false);
const selectedPlanSlug = ref('pro');
const activeFaq = ref(null);

// Form State
const form = ref({
    school_name: '',
    npsn: '',
    subdomain: '',
    admin_name: '',
    admin_email: '',
    admin_phone: '',
    plan_slug: 'pro',
});

const isSubmitting = ref(false);
const errorMessage = ref('');
const registerSuccess = ref(null);

const toggleFaq = (index) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const openRegisterModal = (planSlug = 'pro') => {
    selectedPlanSlug.value = planSlug;
    form.value.plan_slug = planSlug;
    showRegisterModal.value = true;
    errorMessage.value = '';
    registerSuccess.value = null;
};

const closeRegisterModal = () => {
    showRegisterModal.value = false;
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(number);
};

const cleanSubdomain = () => {
    form.value.subdomain = form.value.subdomain
        .toLowerCase()
        .replace(/[^a-z0-9\-]/g, '');
};

const submitRegistration = async () => {
    isSubmitting.value = true;
    errorMessage.value = '';
    registerSuccess.value = null;

    try {
        const response = await axios.post('/register-school', form.value);
        if (response.data.success) {
            registerSuccess.value = response.data.data;
        }
    } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'Terjadi kesalahan sistem. Silakan periksa koneksi atau coba lagi.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

const faqs = [
    {
        q: 'Apakah sistem CBT bisa dijalankan secara Semi-Offline tanpa internet?',
        a: 'Tentu saja! Sistem CBT kami mengadopsi model ANBK/UNBK Kemendikbud. Anda cukup mengunduh paket soal ke 1 PC server lokal di lab sekolah, dan seluruh siswa dapat ujian secara 100% offline via jaringan LAN atau WiFi lokal sekolah tanpa butuh kuota internet.',
    },
    {
        q: 'Apakah sekolah bisa menggunakan domain resmi sendiri (contoh: portal.sman1.sch.id)?',
        a: 'Bisa sekali! Untuk paket Enterprise atau add-on, sekolah dapat memasang custom domain resmi (.sch.id) lengkap dengan penerbitan sertifikat SSL (HTTPS) gratis dan otomatis.',
    },
    {
        q: 'Bagaimana cara kerja presensi RFID IoT di gerbang sekolah?',
        a: 'Sekolah dapat memasang kotak perangkat IoT mandiri (berbasis ESP32) yang terhubung ke WiFi sekolah di dinding gerbang. Siswa cukup menempelkan (tap) kartu pelajarnya, dan dalam waktu kurang dari 1 detik notifikasi kehadiran otomatis terkirim ke nomor WhatsApp orang tua murid.',
    },
    {
        q: 'Bagaimana dengan keamanan saldo uang saku di modul E-Kantin?',
        a: 'Sistem menggunakan enkripsi PIN 6-digit dan mekanisme Database Pessimistic Locking tingkat perbankan. Saldo tabungan siswa terisolasi penuh dan terlindungi dari risiko duplikasi transaksi atau saldo ganda saat jam istirahat ramai.',
    },
];
</script>

<template>
    <Head title="Platform SaaS Manajemen Sekolah Terpadu - EduPrime" />

    <div class="min-h-screen bg-slate-900 text-slate-100 selection:bg-indigo-500 selection:text-white font-sans antialiased">
        <!-- ── Navigation Bar ────────────────────────────────────────── -->
        <header class="sticky top-0 z-40 backdrop-blur-md bg-slate-900/80 border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xl font-black tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-100 to-indigo-200">EduPrime<span class="text-indigo-400">SaaS</span></span>
                        <span class="block text-[10px] font-semibold tracking-wider text-indigo-400 uppercase">School Ecosystem</span>
                    </div>
                </div>

                <nav class="hidden md:flex items-center space-x-8 text-sm font-medium text-slate-300">
                    <a href="#fitur" class="hover:text-indigo-400 transition-colors">Fitur Unggulan</a>
                    <a href="#cbt" class="hover:text-indigo-400 transition-colors">Mesin CBT</a>
                    <a href="#harga" class="hover:text-indigo-400 transition-colors">Paket Langganan</a>
                    <a href="#faq" class="hover:text-indigo-400 transition-colors">FAQ</a>
                </nav>

                <div class="flex items-center space-x-4">
                    <button
                        @click="openRegisterModal('pro')"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 text-white text-sm font-semibold shadow-lg shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5"
                    >
                        Daftar Uji Coba Gratis
                    </button>
                </div>
            </div>
        </header>

        <!-- ── Hero Section ───────────────────────────────────────────── -->
        <section class="relative pt-16 pb-24 overflow-hidden">
            <div class="absolute inset-0 -z-10 flex items-center justify-center">
                <div class="w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-3xl"></div>
                <div class="w-[400px] h-[400px] bg-violet-500/10 rounded-full blur-3xl -translate-y-32 translate-x-32"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold mb-8">
                    <span class="flex h-2 w-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span>Platform All-in-One Generasi Baru Sekolah Indonesia</span>
                </div>

                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight max-w-5xl mx-auto leading-[1.15] text-white">
                    Satu Ekosistem Cerdas untuk <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-sky-300 to-violet-400">Seluruh Manajemen Sekolah</span>
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed">
                    Solusi terpadu SIAKAD, CBT Decoupled (Online & Semi-Offline), Presensi Mandiri IoT RFID & AI Wajah, E-Kantin Cashless, Buku Tamu Digital, hingga Berita Acara Rapat (BAP) resmi.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <button
                        @click="openRegisterModal('pro')"
                        class="w-full sm:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-500 via-indigo-600 to-violet-600 hover:from-indigo-600 hover:to-violet-700 text-white font-bold text-base shadow-xl shadow-indigo-500/30 transition-all transform hover:-translate-y-0.5 flex items-center justify-center space-x-2"
                    >
                        <span>Mulai Uji Coba Gratis 14 Hari</span>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                    <a
                        href="https://wa.me/6281234567890?text=Halo%20EduPrime%20SaaS,%20saya%20tertarik%20konsultasi%20sistem%20informasi%20sekolah"
                        target="_blank"
                        class="w-full sm:w-auto px-8 py-4 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700 text-slate-200 font-semibold text-base transition-all flex items-center justify-center space-x-2"
                    >
                        <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.071.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z" />
                        </svg>
                        <span>Konsultasi via WhatsApp</span>
                    </a>
                </div>

                <!-- Trust Metrics Bar -->
                <div class="mt-16 pt-8 border-t border-slate-800/80 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white">ISO CR-80</div>
                        <div class="text-xs text-slate-400 mt-1">Standar KTS Kartu Pelajar</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white">Zero-Lag CBT</div>
                        <div class="text-xs text-slate-400 mt-1">Online & Semi-Offline ANBK</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white">Multi-Tenant</div>
                        <div class="text-xs text-slate-400 mt-1">Database Terisolasi Penuh</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white">Auto-SSL</div>
                        <div class="text-xs text-slate-400 mt-1">Dukungan Domain Sekolah .sch.id</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── 6 Pilar Fitur Unggulan ────────────────────────────────── -->
        <section id="fitur" class="py-20 bg-slate-800/30 border-y border-slate-800/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-400 mb-2">Solusi Sekolah Masa Depan</h2>
                    <p class="text-3xl sm:text-4xl font-black text-white tracking-tight">Kaya Fitur, Modern, dan Dirancang Sesuai Kurikulum Indonesia</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Fitur 1: CBT Decoupled -->
                    <div class="p-8 rounded-2xl bg-slate-800/50 border border-slate-700/60 hover:border-indigo-500/50 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Ujian Online CBT Decoupled</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Server ujian terpisah bebas down! Mendukung Mode Full Online Cloud dan <strong>Semi-Offline (Gaya ANBK)</strong> via LAN Lab Sekolah tanpa kuota internet, dilengkapi <strong>Aplikasi Android Exambro APK Anti-Curang</strong>.
                        </p>
                    </div>

                    <!-- Fitur 2: Presensi Cerdas & IoT RFID -->
                    <div class="p-8 rounded-2xl bg-slate-800/50 border border-slate-700/60 hover:border-indigo-500/50 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Presensi Mandiri IoT & AI Wajah</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Tap kartu RFID pada perangkat IoT gerbang (ESP32 via WiFi) atau scan AI Wajah di webcam. Otomatis kirim notifikasi pesan WhatsApp ke nomor HP orang tua secara instan saat itu juga.
                        </p>
                    </div>

                    <!-- Fitur 3: E-Kantin Cashless -->
                    <div class="p-8 rounded-2xl bg-slate-800/50 border border-slate-700/60 hover:border-indigo-500/50 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">E-Kantin & Tabungan Digital</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Sekolah bebas uang tunai! Siswa jajan cukup tap kartu RFID atau pesan dari HP menggunakan saldo E-Wallet tabungan siswa. Kasir stand vendor praktis & penarikan saldo transparan.
                        </p>
                    </div>

                    <!-- Fitur 4: Smart Timetable Generator -->
                    <div class="p-8 rounded-2xl bg-slate-800/50 border border-slate-700/60 hover:border-indigo-500/50 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Jadwal Pelajaran Drag & Drop</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Susun jadwal KBM tanpa pusing bentrok! Antarmuka visual tarik-lepas dengan peringatan bentrok guru dan lab secara otomatis, plus generator cerdas 1-klik.
                        </p>
                    </div>

                    <!-- Fitur 5: Buku Tamu & Rapat Digital -->
                    <div class="p-8 rounded-2xl bg-slate-800/50 border border-slate-700/60 hover:border-indigo-500/50 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Buku Tamu & Berita Acara Rapat</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Kios buku tamu tablet lobi dengan foto & tanda tangan digital, serta modul notulen rapat dewan guru lengkap dengan tanda tangan digital HP dan cetak Berita Acara (BAP) PDF resmi.
                        </p>
                    </div>

                    <!-- Fitur 6: Web Profil & Custom Domain -->
                    <div class="p-8 rounded-2xl bg-slate-800/50 border border-slate-700/60 hover:border-indigo-500/50 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Web Sekolah & Custom Domain</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Setiap sekolah otomatis memiliki website profil publik yang mewah, berita SEO, galeri kegiatan, dan tombol PPDB Online, serta siap dipasang domain sekolah sendiri (<em>.sch.id</em>) dengan SSL otomatis.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Paket Harga & Langganan ───────────────────────────────── -->
        <section id="harga" class="py-24 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-400 mb-2">Investasi Terbaik Sekolah</h2>
                    <p class="text-3xl sm:text-4xl font-black text-white tracking-tight">Pilih Paket Sesuai Kebutuhan Sekolah Anda</p>
                    
                    <!-- Billing Toggle -->
                    <div class="mt-8 inline-flex items-center p-1 rounded-xl bg-slate-800 border border-slate-700">
                        <button
                            @click="billingCycle = 'monthly'"
                            :class="billingCycle === 'monthly' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-5 py-2 rounded-lg text-sm font-semibold transition-all"
                        >
                            Tagihan Bulanan
                        </button>
                        <button
                            @click="billingCycle = 'yearly'"
                            :class="billingCycle === 'yearly' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-5 py-2 rounded-lg text-sm font-semibold transition-all flex items-center space-x-2"
                        >
                            <span>Tagihan Tahunan</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">Hemat 2 Bulan!</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        :class="plan.is_popular ? 'border-2 border-indigo-500 bg-slate-800/80 relative shadow-2xl shadow-indigo-500/20' : 'border border-slate-800 bg-slate-800/40'"
                        class="p-8 rounded-3xl flex flex-col justify-between transition-all hover:border-slate-700"
                    >
                        <div v-if="plan.is_popular" class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-indigo-500 text-white text-xs font-black uppercase tracking-wider shadow-md">
                            Paling Direkomendasikan
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                            <p class="text-xs text-slate-400 mt-2 min-h-[36px]">{{ plan.description }}</p>

                            <div class="mt-6 mb-6">
                                <span class="text-4xl font-black text-white">
                                    {{ formatRupiah(billingCycle === 'monthly' ? plan.price_monthly : Math.round(plan.price_yearly / 12)) }}
                                </span>
                                <span class="text-xs text-slate-400">/ bulan</span>
                                <div v-if="billingCycle === 'yearly'" class="text-[11px] text-emerald-400 mt-1 font-medium">
                                    Ditagih {{ formatRupiah(plan.price_yearly) }} per tahun
                                </div>
                            </div>

                            <div class="py-4 border-t border-slate-700/60 space-y-2 text-xs font-semibold text-slate-300">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>Kapasitas Hingga {{ plan.max_students.toLocaleString() }} Siswa</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                    <span>Penyimpanan Berkas {{ Math.round(plan.max_storage_mb / 1024) }} GB</span>
                                </div>
                            </div>

                            <ul class="mt-4 space-y-3 text-xs text-slate-300">
                                <li v-for="(feat, idx) in plan.features" :key="idx" class="flex items-start space-x-3">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ feat }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8">
                            <button
                                @click="openRegisterModal(plan.slug)"
                                :class="plan.is_popular ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-500/30' : 'bg-slate-700 hover:bg-slate-600 text-slate-100'"
                                class="w-full py-3.5 rounded-xl font-bold text-sm transition-all transform hover:-translate-y-0.5"
                            >
                                Pilih {{ plan.name }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── FAQ Section ────────────────────────────────────────────── -->
        <section id="faq" class="py-20 bg-slate-800/20 border-t border-slate-800">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-400 mb-2">Pertanyaan Umum</h2>
                    <p class="text-3xl font-black text-white">Hal yang Sering Ditanyakan</p>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(item, idx) in faqs"
                        :key="idx"
                        class="rounded-2xl bg-slate-800/40 border border-slate-700/60 overflow-hidden"
                    >
                        <button
                            @click="toggleFaq(idx)"
                            class="w-full px-6 py-4 text-left flex items-center justify-between font-bold text-sm sm:text-base text-white hover:text-indigo-400 transition-colors"
                        >
                            <span>{{ item.q }}</span>
                            <svg
                                class="w-5 h-5 transition-transform duration-200"
                                :class="activeFaq === idx ? 'rotate-180 text-indigo-400' : 'text-slate-400'"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="activeFaq === idx" class="px-6 pb-5 text-sm text-slate-400 leading-relaxed border-t border-slate-700/30 pt-3">
                            {{ item.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Footer ─────────────────────────────────────────────────── -->
        <footer class="py-12 border-t border-slate-800 text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4">
                <p>&copy; 2026 EduPrime SaaS. Hak Cipta Dilindungi Undang-Undang.</p>
                <p class="mt-2 text-slate-600">Arsitektur Multi-Tenancy Enterprise &bull; Berbasis Laravel 13 &bull; Standar Pendidikan Indonesia</p>
            </div>
        </footer>

        <!-- ── Modal Pendaftaran Sekolah (Onboarding Wizard) ───────────── -->
        <div v-if="showRegisterModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <div class="relative w-full max-w-xl bg-slate-800 border border-slate-700 rounded-3xl p-6 sm:p-8 shadow-2xl my-8">
                <!-- Close Button -->
                <button
                    @click="closeRegisterModal"
                    class="absolute top-5 right-5 text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-700"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Status Sukses -->
                <div v-if="registerSuccess" class="text-center py-6">
                    <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-white">Selamat! Portal Aktif</h3>
                    <p class="text-sm text-slate-400 mt-2">
                        Portal untuk <strong>{{ registerSuccess.school_name }}</strong> telah berhasil dibuat dengan masa uji coba gratis 14 hari.
                    </p>

                    <div class="mt-6 p-4 rounded-2xl bg-slate-900/80 border border-slate-700/80 text-left space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Subdomain Resmi:</span>
                            <span class="font-mono text-indigo-400 font-semibold">{{ registerSuccess.subdomain }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Paket Langganan:</span>
                            <span class="text-white font-semibold">{{ registerSuccess.plan }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Administrator:</span>
                            <span class="text-white font-semibold">{{ registerSuccess.admin_name }}</span>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                        <a
                            :href="registerSuccess.portal_url"
                            class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-lg shadow-indigo-500/30 transition-all text-center"
                        >
                            Buka Portal Sekolah Sekarang
                        </a>
                        <button
                            @click="closeRegisterModal"
                            class="px-6 py-3 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 font-semibold text-sm transition-all"
                        >
                            Tutup
                        </button>
                    </div>
                </div>

                <!-- Form Pendaftaran -->
                <div v-else>
                    <div class="mb-6">
                        <h3 class="text-2xl font-black text-white">Daftarkan Sekolah Anda</h3>
                        <p class="text-xs text-slate-400 mt-1">Uji coba gratis seluruh modul selama 14 hari penuh tanpa kartu kredit.</p>
                    </div>

                    <!-- Error Alert -->
                    <div v-if="errorMessage" class="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center space-x-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ errorMessage }}</span>
                    </div>

                    <form @submit.prevent="submitRegistration" class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">Nama Lengkap Sekolah</label>
                                <input
                                    v-model="form.school_name"
                                    type="text"
                                    placeholder="contoh: SMAN 1 Harapan Bangsa"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">NPSN Sekolah</label>
                                <input
                                    v-model="form.npsn"
                                    type="text"
                                    placeholder="contoh: 20108921"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Subdomain yang Diinginkan</label>
                            <div class="flex items-center rounded-xl bg-slate-900 border border-slate-700 focus-within:border-indigo-500 overflow-hidden">
                                <input
                                    v-model="form.subdomain"
                                    @input="cleanSubdomain"
                                    type="text"
                                    placeholder="sman1harapan"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-transparent text-white placeholder-slate-500 focus:outline-none font-mono text-xs"
                                />
                                <span class="px-3 text-slate-500 font-mono text-xs select-none">.platform.id</span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1">Alamat ini yang akan diakses oleh siswa dan guru Anda.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">Nama Kepala Sekolah / PIC</label>
                                <input
                                    v-model="form.admin_name"
                                    type="text"
                                    placeholder="Bpk. Budi Santoso, M.Pd"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">Nomor WhatsApp Aktif</label>
                                <input
                                    v-model="form.admin_phone"
                                    type="tel"
                                    placeholder="081234567890"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Email Resmi Sekolah</label>
                            <input
                                v-model="form.admin_email"
                                type="email"
                                placeholder="admin@sman1harapan.sch.id"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Pilihan Paket Uji Coba</label>
                            <select
                                v-model="form.plan_slug"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white focus:outline-none focus:border-indigo-500 text-xs"
                            >
                                <option v-for="plan in plans" :key="plan.id" :value="plan.slug">
                                    {{ plan.name }} — (Maks {{ plan.max_students }} Siswa)
                                </option>
                            </select>
                        </div>

                        <div class="pt-4">
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="w-full py-3.5 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 text-white font-bold text-sm shadow-xl shadow-indigo-500/30 transition-all flex items-center justify-center space-x-2 disabled:opacity-50"
                            >
                                <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>{{ isSubmitting ? 'Membuat Portal Sekolah...' : 'Konfirmasi & Buat Portal Sekolah' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
