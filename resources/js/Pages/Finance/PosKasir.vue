<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    academicYears: Array,
    classrooms: Array,
    feeCategories: Array,
});

const searchQuery = ref('');
const selectedClassroom = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const selectedStudent = ref(null);

const selectedBills = ref([]);
const paymentMethod = ref('cash');
const cashReceived = ref('');
const walletPin = ref('');
const referenceNumber = ref('');
const notes = ref('');

const isSubmitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const lastPayment = ref(null);

const doSearch = async () => {
    if (!searchQuery.value && !selectedClassroom.value) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    errorMessage.value = '';

    try {
        const params = new URLSearchParams();
        if (searchQuery.value) params.append('q', searchQuery.value);
        if (selectedClassroom.value) params.append('classroom_id', selectedClassroom.value);

        const res = await fetch(`/finance/search-students?${params.toString()}`);
        const data = await res.json();
        if (data.success) {
            searchResults.value = data.students;
        }
    } catch (e) {
        errorMessage.value = 'Gagal mencari data siswa.';
    } finally {
        isSearching.value = false;
    }
};

const selectStudent = (student) => {
    selectedStudent.value = student;
    selectedBills.value = [];
    cashReceived.value = '';
    walletPin.value = '';
    referenceNumber.value = '';
    notes.value = '';
    successMessage.value = '';
    errorMessage.value = '';
    lastPayment.value = null;

    // Default checklist all unpaid bills
    if (student.bills && student.bills.length > 0) {
        selectedBills.value = student.bills.map(b => ({
            bill_id: b.id,
            title: b.title,
            max_amount: b.remaining_amount,
            amount: b.remaining_amount,
            checked: true,
        }));
    }
};

const totalPayAmount = computed(() => {
    return selectedBills.value
        .filter(b => b.checked)
        .reduce((sum, b) => sum + (parseFloat(b.amount) || 0), 0);
});

const cashChange = computed(() => {
    const received = parseFloat(cashReceived.value) || 0;
    return Math.max(0, received - totalPayAmount.value);
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

const submitPayment = async () => {
    if (!selectedStudent.value) return;
    if (totalPayAmount.value <= 0) {
        errorMessage.value = 'Pilih minimal satu tagihan untuk dibayar.';
        return;
    }

    if (paymentMethod.value === 'cash') {
        const received = parseFloat(cashReceived.value) || 0;
        if (received < totalPayAmount.value) {
            errorMessage.value = `Uang tunai yang diterima (${formatRupiah(received)}) kurang dari total tagihan (${formatRupiah(totalPayAmount.value)}).`;
            return;
        }
    }

    if (paymentMethod.value === 'wallet' && (!walletPin.value || walletPin.value.length !== 6)) {
        errorMessage.value = 'Masukkan 6-digit PIN dompet digital siswa untuk otorisasi.';
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    const allocations = selectedBills.value
        .filter(b => b.checked && parseFloat(b.amount) > 0)
        .map(b => ({
            bill_id: b.bill_id,
            amount: parseFloat(b.amount),
        }));

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/finance/pay', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                student_profile_id: selectedStudent.value.id,
                payment_method: paymentMethod.value,
                allocations: allocations,
                total_amount: totalPayAmount.value,
                pin: walletPin.value || null,
                reference_number: referenceNumber.value || null,
                notes: notes.value || null,
            })
        });

        const data = await res.json();
        if (data.success) {
            successMessage.value = 'Pembayaran berhasil disimpan!';
            lastPayment.value = data.payment;

            // Refresh student data
            doSearch();
            selectedBills.value = [];
        } else {
            errorMessage.value = data.message || 'Gagal memproses pembayaran.';
        }
    } catch (e) {
        errorMessage.value = 'Terjadi kesalahan sistem saat menghubungi server.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col">
        <Head title="POS Kasir Pembayaran SPP & Keuangan Sekolah" />

        <!-- Top Header Navigation -->
        <header class="bg-slate-900/90 border-b border-slate-800/80 px-6 py-4 sticky top-0 z-20 backdrop-blur-md">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white font-black text-xl shadow-lg">
                        Rp
                    </div>
                    <div>
                        <h1 class="text-base font-black tracking-wide text-white">POS KASIR SPP & KEUANGAN SEKOLAH</h1>
                        <p class="text-xs text-slate-400">Terminal Penerimaan Kas Resmi • SMK Informatika Terpadu</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 hover:text-white transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Dashboard
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Body Grid -->
        <div class="max-w-7xl mx-auto w-full px-6 py-6 grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1">
            <!-- Left Column: Search & Student Selection (5 Cols) -->
            <div class="lg:col-span-5 space-y-4">
                <!-- Search Box -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Cari Siswa / Scan Barcode KTS
                    </h2>

                    <div class="space-y-3">
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                @keyup.enter="doSearch"
                                type="text"
                                placeholder="Ketik Nama, NISN, atau Tap RFID..."
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                            />
                            <button
                                @click="doSearch"
                                class="absolute right-2 top-2 px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg transition"
                            >
                                Cari
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <select
                                v-model="selectedClassroom"
                                @change="doSearch"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
                            >
                                <option value="">Semua Kelas</option>
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Search Results List -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl max-h-[500px] overflow-y-auto">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400">Hasil Pencarian ({{ searchResults.length }})</span>
                        <span v-if="isSearching" class="text-xs text-emerald-400 animate-pulse">Memuat...</span>
                    </div>

                    <div v-if="searchResults.length === 0 && !isSearching" class="text-center py-8 text-slate-500 text-xs">
                        Masukkan nama siswa atau pilih kelas untuk menampilkan tunggakan.
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="s in searchResults"
                            :key="s.id"
                            @click="selectStudent(s)"
                            :class="[
                                'p-3 rounded-xl border transition cursor-pointer text-left',
                                selectedStudent?.id === s.id
                                    ? 'bg-emerald-950/40 border-emerald-500'
                                    : 'bg-slate-950/60 border-slate-800 hover:border-slate-700'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-white">{{ s.name }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono">{{ s.classroom }}</span>
                            </div>
                            <div class="flex items-center justify-between mt-1 text-xs text-slate-400">
                                <span>NISN: {{ s.nisn }}</span>
                                <span class="text-rose-400 font-semibold">{{ formatRupiah(s.total_arrears) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Billing Invoices & Payment Processing (7 Cols) -->
            <div class="lg:col-span-7 space-y-4">
                <div v-if="!selectedStudent" class="h-full min-h-[400px] bg-slate-900 border border-slate-800 rounded-2xl flex flex-col items-center justify-center p-8 text-center text-slate-500">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800/80 flex items-center justify-center text-slate-400 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-300 mb-1">Belum Ada Siswa Dipilih</h3>
                    <p class="text-xs max-w-sm">Pilih siswa pada panel kiri untuk melihat tagihan SPP, pos biaya gedung, dan memproses kuitansi pembayaran.</p>
                </div>

                <div v-else class="space-y-4">
                    <!-- Student Header Card -->
                    <div class="bg-gradient-to-r from-slate-900 to-indigo-950/40 border border-slate-800 rounded-2xl p-4 flex items-center justify-between">
                        <div>
                            <div class="text-xs text-indigo-300 font-semibold uppercase tracking-wider">Siswa Terpilih</div>
                            <h2 class="text-lg font-black text-white">{{ selectedStudent.name }}</h2>
                            <p class="text-xs text-slate-400">NISN: {{ selectedStudent.nisn }} • Kelas: {{ selectedStudent.classroom }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-400 uppercase">Saldo Dompet Siswa</div>
                            <div class="text-sm font-black text-emerald-400 font-mono">{{ formatRupiah(selectedStudent.wallet_balance) }}</div>
                        </div>
                    </div>

                    <!-- Notification Banners -->
                    <div v-if="successMessage" class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-600 text-emerald-300 text-xs flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ successMessage }}</span>
                        </div>
                        <a
                            v-if="lastPayment"
                            :href="lastPayment.receipt_url"
                            target="_blank"
                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs transition"
                        >
                            Cetak Kuitansi Resmi 🖨️
                        </a>
                    </div>

                    <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-950/60 border border-rose-600 text-rose-300 text-xs flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ errorMessage }}</span>
                    </div>

                    <!-- Bills Checklist Table -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Daftar Tagihan Belum Lunas</h3>

                        <div v-if="selectedBills.length === 0" class="text-center py-6 text-emerald-400 text-xs">
                            🎉 Siswa tidak memiliki tunggakan tagihan saat ini.
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="(b, idx) in selectedBills"
                                :key="b.bill_id"
                                class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        v-model="b.checked"
                                        class="w-4 h-4 rounded text-emerald-600 bg-slate-800 border-slate-700 focus:ring-0"
                                    />
                                    <div>
                                        <div class="text-xs font-bold text-white">{{ b.title }}</div>
                                        <div class="text-[10px] text-slate-400">Sisa Tagihan: {{ formatRupiah(b.max_amount) }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-400">Bayar:</span>
                                    <input
                                        type="number"
                                        v-model="b.amount"
                                        :disabled="!b.checked"
                                        :max="b.max_amount"
                                        class="w-28 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-xs text-right font-mono text-emerald-400 focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Execution Box -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <span class="text-sm font-bold text-slate-300">Total Yang Akan Dibayar:</span>
                            <span class="text-2xl font-black text-emerald-400 font-mono">{{ formatRupiah(totalPayAmount) }}</span>
                        </div>

                        <!-- Payment Method Toggle -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-2">Metode Pembayaran:</label>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    type="button"
                                    @click="paymentMethod = 'cash'"
                                    :class="['py-2 px-3 rounded-xl text-xs font-bold border transition flex flex-col items-center gap-1', paymentMethod === 'cash' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-950 text-slate-300 border-slate-800']"
                                >
                                    💵 Tunai (Kasir)
                                </button>
                                <button
                                    type="button"
                                    @click="paymentMethod = 'wallet'"
                                    :class="['py-2 px-3 rounded-xl text-xs font-bold border transition flex flex-col items-center gap-1', paymentMethod === 'wallet' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-950 text-slate-300 border-slate-800']"
                                >
                                    💳 Saldo KTS
                                </button>
                                <button
                                    type="button"
                                    @click="paymentMethod = 'bank_transfer'"
                                    :class="['py-2 px-3 rounded-xl text-xs font-bold border transition flex flex-col items-center gap-1', paymentMethod === 'bank_transfer' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-950 text-slate-300 border-slate-800']"
                                >
                                    🏦 Transfer Bank
                                </button>
                                <button
                                    type="button"
                                    @click="paymentMethod = 'qris'"
                                    :class="['py-2 px-3 rounded-xl text-xs font-bold border transition flex flex-col items-center gap-1', paymentMethod === 'qris' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-950 text-slate-300 border-slate-800']"
                                >
                                    📱 QRIS Dinamis
                                </button>
                            </div>
                        </div>

                        <!-- Cash Payment Calculator -->
                        <div v-if="paymentMethod === 'cash'" class="p-3 bg-slate-950 border border-slate-800 rounded-xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-400">Uang Diterima dari Siswa/Wali:</span>
                                <input
                                    type="number"
                                    v-model="cashReceived"
                                    placeholder="Nominal Uang..."
                                    class="w-40 bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-right font-mono text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-800/80 pt-2">
                                <span class="text-xs font-bold text-slate-300">Kembalian:</span>
                                <span class="text-base font-black text-indigo-300 font-mono">{{ formatRupiah(cashChange) }}</span>
                            </div>
                        </div>

                        <!-- Wallet PIN Check -->
                        <div v-if="paymentMethod === 'wallet'" class="p-3 bg-slate-950 border border-slate-800 rounded-xl space-y-2">
                            <label class="block text-xs font-bold text-slate-300">PIN 6-Digit Dompet Siswa:</label>
                            <input
                                type="password"
                                v-model="walletPin"
                                maxlength="6"
                                placeholder="******"
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-center text-lg tracking-widest font-mono text-emerald-400 focus:outline-none focus:border-emerald-500"
                            />
                            <p class="text-[10px] text-slate-400 text-center">PIN default akun demo: <span class="font-mono text-white">123456</span></p>
                        </div>

                        <!-- Action Submit Button -->
                        <button
                            type="button"
                            @click="submitPayment"
                            :disabled="isSubmitting || totalPayAmount <= 0"
                            class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black rounded-xl shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <span v-if="isSubmitting" class="animate-spin">🔄</span>
                            <span>PROSES PEMBAYARAN & TERBITKAN KUITANSI</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
