<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    student: Object,
    wallet: Object,
    transactions: Array,
});

const isChangingPin = ref(false);
const currentPin = ref('');
const newPin = ref('');
const confirmPin = ref('');
const pinMessage = ref('');
const pinError = ref('');
const isSubmittingPin = ref(false);

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

const handleChangePin = async () => {
    pinMessage.value = '';
    pinError.value = '';

    if (newPin.value.length !== 6 || !/^\d+$/.test(newPin.value)) {
        pinError.value = 'PIN baru harus tepat 6 digit angka.';
        return;
    }

    if (newPin.value !== confirmPin.value) {
        pinError.value = 'Konfirmasi PIN tidak cocok.';
        return;
    }

    isSubmittingPin.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/wallet/change-pin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                wallet_id: props.wallet.id,
                current_pin: currentPin.value,
                new_pin: newPin.value,
            })
        });

        const data = await res.json();
        if (data.success) {
            pinMessage.value = data.message;
            currentPin.value = '';
            newPin.value = '';
            confirmPin.value = '';
            setTimeout(() => {
                isChangingPin.value = false;
                pinMessage.value = '';
            }, 2000);
        } else {
            pinError.value = data.message || 'Gagal mengubah PIN.';
        }
    } catch (e) {
        pinError.value = 'Terjadi kesalahan sistem.';
    } finally {
        isSubmittingPin.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 pb-16">
        <Head title="Tabungan & Dompet Digital Siswa (E-Wallet)" />

        <!-- Top Header Navigation -->
        <header class="bg-slate-900/90 border-b border-slate-800 px-6 py-4 sticky top-0 z-20 backdrop-blur-md">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('portal.student')"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-base font-bold text-white leading-tight">DOMPET DIGITAL & TABUNGAN SISWA</h1>
                        <p class="text-xs text-slate-400">Cashless Smart School • SMK Informatika Terpadu</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('canteen.index')"
                        class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow transition flex items-center gap-1.5"
                    >
                        🛒 Jajan E-Kantin
                    </Link>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <!-- Virtual Debit Card (Gold / Navy Glassmorphism) -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-tr from-indigo-950 via-slate-900 to-amber-950/40 p-6 md:p-8 shadow-2xl border border-indigo-500/30">
                <div class="absolute -right-8 -top-8 w-44 h-44 bg-amber-500/10 rounded-full blur-3xl"></div>
                <div class="absolute right-10 bottom-4 opacity-10 text-8xl font-black">
                    KTS
                </div>

                <div class="flex justify-between items-start mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-xs font-mono font-bold tracking-widest text-indigo-300 uppercase">SMART CARD DEBIT</span>
                        </div>
                        <h2 class="text-xl font-black text-white mt-1">SMK INFORMATIKA TERPADU</h2>
                    </div>

                    <div class="w-12 h-10 rounded-lg bg-amber-400/20 border border-amber-300/40 flex items-center justify-center">
                        <div class="w-8 h-6 rounded border border-amber-300/60 bg-amber-500/30 grid grid-cols-2 gap-0.5 p-0.5">
                            <div class="bg-amber-300/60 rounded-xs"></div>
                            <div class="bg-amber-300/60 rounded-xs"></div>
                        </div>
                    </div>
                </div>

                <!-- Balance Display -->
                <div class="space-y-1 mb-8">
                    <span class="text-xs text-slate-400 uppercase font-semibold">Total Saldo Tabungan Aktif</span>
                    <div class="text-3xl md:text-4xl font-black text-white font-mono tracking-tight">
                        {{ formatRupiah(wallet?.balance) }}
                    </div>
                </div>

                <!-- Account & Student Details -->
                <div class="pt-4 border-t border-indigo-400/20 flex flex-wrap justify-between items-end gap-4">
                    <div>
                        <div class="text-[10px] text-indigo-300 font-mono uppercase">Nomor Rekening Tabungan</div>
                        <div class="text-sm font-mono font-bold text-white tracking-wider">{{ wallet?.account_number }}</div>
                    </div>

                    <div>
                        <div class="text-[10px] text-indigo-300 uppercase">Nama Pemilik Rekening</div>
                        <div class="text-sm font-bold text-white">{{ student?.name }} ({{ student?.classroom }})</div>
                    </div>

                    <button
                        type="button"
                        @click="isChangingPin = !isChangingPin"
                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs text-indigo-200 font-semibold border border-white/10 backdrop-blur-sm transition flex items-center gap-1.5"
                    >
                        🔒 {{ isChangingPin ? 'Tutup Ubah PIN' : 'Ubah PIN 6-Digit' }}
                    </button>
                </div>
            </div>

            <!-- Daily Limit & Spending Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Limit Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Batas Jajan Harian</span>
                        <span class="text-xs font-mono font-semibold text-emerald-400">
                            Sisa: {{ formatRupiah(wallet?.remaining_limit) }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-gradient-to-r from-emerald-500 to-amber-500 rounded-full transition-all duration-500"
                            :style="{ width: Math.min(100, ((wallet?.spent_today || 0) / (wallet?.daily_limit || 1)) * 100) + '%' }"
                        ></div>
                    </div>

                    <div class="flex justify-between text-[11px] text-slate-400">
                        <span>Terpakai: {{ formatRupiah(wallet?.spent_today) }}</span>
                        <span>Maks: {{ formatRupiah(wallet?.daily_limit) }} / hari</span>
                    </div>
                </div>

                <!-- Info Security Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-950 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="text-xs">
                        <h3 class="font-bold text-white mb-0.5">Keamanan Transaksi Terproteksi</h3>
                        <p class="text-slate-400 leading-relaxed">
                            Semua transaksi di kasir dan kantin dilindungi oleh enkripsi 6-digit PIN dan batas harian yang terpantau langsung oleh orang tua.
                        </p>
                    </div>
                </div>
            </div>

            <!-- PIN Change Form (Collapsible) -->
            <div v-if="isChangingPin" class="bg-slate-900 border border-indigo-500/40 rounded-2xl p-6 shadow-xl space-y-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>🔒</span> Form Perubahan PIN 6-Digit Dompet
                </h3>

                <div v-if="pinMessage" class="p-3 bg-emerald-950/60 border border-emerald-600 rounded-xl text-emerald-300 text-xs">
                    {{ pinMessage }}
                </div>
                <div v-if="pinError" class="p-3 bg-rose-950/60 border border-rose-600 rounded-xl text-rose-300 text-xs">
                    {{ pinError }}
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">PIN Lama</label>
                        <input
                            type="password"
                            v-model="currentPin"
                            maxlength="6"
                            placeholder="******"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-center text-sm font-mono tracking-widest text-white focus:outline-none focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">PIN Baru (6 Digit)</label>
                        <input
                            type="password"
                            v-model="newPin"
                            maxlength="6"
                            placeholder="******"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-center text-sm font-mono tracking-widest text-white focus:outline-none focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Konfirmasi PIN Baru</label>
                        <input
                            type="password"
                            v-model="confirmPin"
                            maxlength="6"
                            placeholder="******"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-center text-sm font-mono tracking-widest text-white focus:outline-none focus:border-indigo-500"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="isChangingPin = false"
                        class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleChangePin"
                        :disabled="isSubmittingPin"
                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white transition disabled:opacity-50"
                    >
                        Simpan PIN Baru
                    </button>
                </div>
            </div>

            <!-- Transaction Ledger History -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Riwayat Mutasi Saldo Terakhir</h3>
                    <span class="text-xs text-slate-500">{{ transactions?.length || 0 }} transaksi</span>
                </div>

                <div v-if="!transactions || transactions.length === 0" class="text-center py-8 text-slate-500 text-xs">
                    Belum ada riwayat transaksi dompet digital.
                </div>

                <div v-else class="divide-y divide-slate-800/80">
                    <div
                        v-for="trx in transactions"
                        :key="trx.id"
                        class="py-3 flex items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shrink-0',
                                    trx.type === 'topup' || trx.type === 'refund'
                                        ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/30'
                                        : 'bg-rose-950/60 text-rose-400 border border-rose-500/30'
                                ]"
                            >
                                {{ trx.type === 'topup' || trx.type === 'refund' ? '↓' : '↑' }}
                            </div>
                            <div>
                                <div class="text-xs font-bold text-white">{{ trx.description }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ trx.transaction_number }} • {{ trx.created_at }}</div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div
                                :class="[
                                    'text-xs font-bold font-mono',
                                    trx.type === 'topup' || trx.type === 'refund' ? 'text-emerald-400' : 'text-rose-400'
                                ]"
                            >
                                {{ trx.type === 'topup' || trx.type === 'refund' ? '+' : '-' }}{{ formatRupiah(trx.amount) }}
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono">
                                Saldo: {{ formatRupiah(trx.balance_after) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
