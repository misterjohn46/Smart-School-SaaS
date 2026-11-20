<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    batches: Array,
    departments: Array,
    defaultBatchId: [Number, String],
});

const currentStep = ref(1);

const form = useForm({
    ppdb_batch_id: props.defaultBatchId || (props.batches.length > 0 ? props.batches[0].id : ''),
    track: 'reguler',

    // Data Pribadi
    full_name: '',
    gender: 'L',
    nisn: '',
    nik: '',
    birth_place: '',
    birth_date: '',
    religion: 'Islam',
    previous_school: '',
    phone_number: '',
    email: '',
    address: '',

    // Data Orang Tua
    parent_name: '',
    parent_phone: '',
    parent_job: '',
    parent_income: '< Rp 3.000.000',

    // Jurusan & Nilai
    department_id: '',
    second_department_id: '',
    distance_meters: '',
    average_report_score: '',
    achievements: '',

    // Berkas
    doc_photo: null,
    doc_kk: null,
    doc_akta: null,
    doc_ijazah: null,

    // Konfirmasi
    agreement: false,
});

const photoPreview = ref(null);

const onPhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.doc_photo = file;
        const reader = new FileReader();
        reader.onload = (ev) => {
            photoPreview.value = ev.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const validateStep = (step) => {
    if (step === 1) {
        if (!form.ppdb_batch_id) {
            alert('Silakan pilih gelombang pendaftaran.');
            return false;
        }
        if (!form.track) {
            alert('Silakan pilih jalur pendaftaran.');
            return false;
        }
        return true;
    }

    if (step === 2) {
        if (!form.full_name || !form.birth_place || !form.birth_date || !form.previous_school || !form.phone_number || !form.address) {
            alert('Mohon lengkapi semua kolom biodata wajib (Nama, TTL, Asal Sekolah, No. HP, dan Alamat).');
            return false;
        }
        return true;
    }

    if (step === 3) {
        if (!form.parent_name || !form.parent_phone) {
            alert('Mohon isi nama orang tua/wali dan nomor kontak WhatsApp aktif.');
            return false;
        }
        return true;
    }

    if (step === 4) {
        return true;
    }

    return true;
};

const nextStep = () => {
    if (validateStep(currentStep.value)) {
        if (currentStep.value < 5) {
            currentStep.value++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const submitForm = () => {
    if (!form.agreement) {
        alert('Anda wajib menyetujui pernyataan kebenaran data.');
        return;
    }
    form.post(route('ppdb.public.store'), {
        forceFormData: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};

const stepLabels = [
    { number: 1, title: 'Jalur & Gelombang' },
    { number: 2, title: 'Biodata Calon Siswa' },
    { number: 3, title: 'Data Orang Tua' },
    { number: 4, title: 'Peminatan & Nilai' },
    { number: 5, title: 'Unggah Berkas & Kirim' },
];
</script>

<template>
    <Head title="Formulir Pendaftaran PPDB Online" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans pb-20">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
            <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
                <Link :href="route('ppdb.public.index')" class="flex items-center gap-2 text-slate-600 hover:text-slate-900 text-xs font-bold transition-colors">
                    <span>←</span> Kembali ke Informasi PPDB
                </Link>
                <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600">Pendaftaran Siswa Baru</span>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 pt-8">
            <!-- Progress Stepper -->
            <div class="bg-white p-4 sm:p-6 rounded-3xl border border-slate-200 shadow-xs mb-8">
                <div class="grid grid-cols-5 gap-2">
                    <div
                        v-for="s in stepLabels"
                        :key="s.number"
                        class="text-center"
                    >
                        <div
                            :class="[
                                'w-8 h-8 mx-auto rounded-full flex items-center justify-center text-xs font-black transition-all mb-1.5',
                                currentStep === s.number ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 ring-4 ring-indigo-50' :
                                currentStep > s.number ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400'
                            ]"
                        >
                            {{ currentStep > s.number ? '✓' : s.number }}
                        </div>
                        <span
                            :class="[
                                'text-[10px] hidden sm:block font-bold truncate',
                                currentStep === s.number ? 'text-indigo-600' : 'text-slate-400'
                            ]"
                        >
                            {{ s.title }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Error Notification -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-700 space-y-1">
                <p class="font-bold">Perhatian! Ada beberapa data yang perlu diperbaiki:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    <li v-for="(err, field) in form.errors" :key="field">{{ err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submitForm">
                <!-- STEP 1: Jalur & Gelombang -->
                <div v-if="currentStep === 1" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-6">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">Langkah 1: Pilih Gelombang & Jalur Pendaftaran</h2>
                        <p class="text-xs text-slate-500 mt-1">Pastikan gelombang yang dipilih sesuai dengan kriteria pendaftaran Anda.</p>
                    </div>

                    <!-- Pilihan Gelombang -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700">Gelombang Pendaftaran <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                v-for="b in batches"
                                :key="b.id"
                                :class="[
                                    'p-4 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between space-y-2',
                                    form.ppdb_batch_id === b.id ? 'bg-indigo-50/70 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-50/50 border-slate-200 hover:bg-white'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-black text-slate-900 text-sm">{{ b.name }}</span>
                                    <input type="radio" v-model="form.ppdb_batch_id" :value="b.id" class="accent-indigo-600" />
                                </div>
                                <div class="text-[11px] text-slate-500 space-y-0.5">
                                    <p>Tahun: <strong>{{ b.academic_year?.name || 'Baru' }}</strong></p>
                                    <p>Biaya: <strong>{{ b.registration_fee > 0 ? `Rp ${b.registration_fee.toLocaleString('id-ID')}` : 'Gratis' }}</strong></p>
                                    <p>Batas: <strong>{{ b.end_date }}</strong></p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Pilihan Jalur -->
                    <div class="space-y-2 pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700">Jalur Pendaftaran <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                v-for="t in [
                                    { id: 'zonasi', name: 'Jalur Zonasi', desc: 'Berdasarkan radius jarak domisili ke sekolah' },
                                    { id: 'prestasi', name: 'Jalur Prestasi', desc: 'Berdasarkan nilai rapor dan piagam kejuaraan' },
                                    { id: 'afirmasi', name: 'Jalur Afirmasi', desc: 'Pemegang kartu bantuan KIP / PKH / Disabilitas' },
                                    { id: 'reguler', name: 'Jalur Reguler / Umum', desc: 'Seleksi berkas administrasi dan tes umum' },
                                    { id: 'pindahan', name: 'Jalur Pindahan', desc: 'Perpindahan tugas instansi dinas orang tua' }
                                ]"
                                :key="t.id"
                                :class="[
                                    'p-4 rounded-2xl border cursor-pointer transition-all flex items-start justify-between gap-3',
                                    form.track === t.id ? 'bg-indigo-50/70 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-50/50 border-slate-200 hover:bg-white'
                                ]"
                            >
                                <div>
                                    <span class="font-bold text-slate-900 text-xs block">{{ t.name }}</span>
                                    <span class="text-[11px] text-slate-400 leading-tight block mt-0.5">{{ t.desc }}</span>
                                </div>
                                <input type="radio" v-model="form.track" :value="t.id" class="accent-indigo-600 mt-0.5" />
                            </label>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Biodata Calon Siswa -->
                <div v-if="currentStep === 2" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-5">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">Langkah 2: Data Diri Calon Siswa</h2>
                        <p class="text-xs text-slate-500 mt-1">Isikan identitas lengkap calon peserta didik sesuai Kartu Keluarga (KK).</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.full_name"
                                type="text"
                                placeholder="Contoh: Muhammad Rayhan"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                <select v-model="form.gender" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                    <option value="L">Laki-laki (L)</option>
                                    <option value="P">Perempuan (P)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Agama</label>
                                <select v-model="form.religion" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                    <option value="Islam">Islam</option>
                                    <option value="Kristen Protestan">Kristen Protestan</option>
                                    <option value="Katolik">Katolik</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Buddha">Buddha</option>
                                    <option value="Konghucu">Konghucu</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">NISN (10 Digit)</label>
                                <input
                                    v-model="form.nisn"
                                    type="text"
                                    maxlength="20"
                                    placeholder="Contoh: 0098765432"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">NIK Siswa (16 Digit KK)</label>
                                <input
                                    v-model="form.nik"
                                    type="text"
                                    maxlength="20"
                                    placeholder="Contoh: 3201234567890001"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Lahir <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.birth_place"
                                    type="text"
                                    placeholder="Kota/Kabupaten kelahiran"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.birth_date"
                                    type="date"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Asal Sekolah Sebelumnya <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.previous_school"
                                type="text"
                                placeholder="Contoh: SMP Negeri 1 Jakarta / MTs Negeri"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp / HP Siswa <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.phone_number"
                                    type="tel"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email Aktif</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="nama@email.com"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Tempat Tinggal Lengkap <span class="text-rose-500">*</span></label>
                            <textarea
                                v-model="form.address"
                                rows="3"
                                placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Data Orang Tua / Wali -->
                <div v-if="currentStep === 3" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-5">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">Langkah 3: Data Orang Tua / Wali Murid</h2>
                        <p class="text-xs text-slate-500 mt-1">Digunakan untuk konfirmasi pendaftaran dan kontak darurat sekolah.</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Ayah / Ibu / Wali <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.parent_name"
                                type="text"
                                placeholder="Nama lengkap orang tua"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp Orang Tua <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.parent_phone"
                                    type="tel"
                                    placeholder="Contoh: 081298765432"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Pekerjaan Orang Tua</label>
                                <input
                                    v-model="form.parent_job"
                                    type="text"
                                    placeholder="PNS / Wiraswasta / Karyawan / Buruh"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Rentang Penghasilan Bulanan</label>
                            <select v-model="form.parent_income" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                <option value="< Rp 1.500.000">&lt; Rp 1.500.000</option>
                                <option value="Rp 1.500.000 - Rp 3.000.000">Rp 1.500.000 - Rp 3.000.000</option>
                                <option value="Rp 3.000.000 - Rp 5.000.000">Rp 3.000.000 - Rp 5.000.000</option>
                                <option value="Rp 5.000.000 - Rp 10.000.000">Rp 5.000.000 - Rp 10.000.000</option>
                                <option value="> Rp 10.000.000">&gt; Rp 10.000.000</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: Peminatan Jurusan & Nilai -->
                <div v-if="currentStep === 4" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-5">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">Langkah 4: Peminatan Jurusan & Prestasi</h2>
                        <p class="text-xs text-slate-500 mt-1">Pilih program keahlian yang diminati serta informasi pendukung seleksi.</p>
                    </div>

                    <div class="space-y-4">
                        <div v-if="departments.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan Jurusan 1 (Utama)</label>
                                <select v-model="form.department_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                    <option value="">-- Pilih Jurusan Utama --</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan Jurusan 2 (Cadangan)</label>
                                <select v-model="form.second_department_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                    <option value="">-- Pilihan Kedua (Opsional) --</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Khusus Zonasi -->
                        <div v-if="form.track === 'zonasi'" class="p-4 bg-blue-50/60 border border-blue-200 rounded-2xl space-y-2">
                            <label class="block text-xs font-bold text-blue-900">Perkiraan Jarak Rumah ke Sekolah (Meter)</label>
                            <input
                                v-model="form.distance_meters"
                                type="number"
                                placeholder="Contoh: 1200 (dalam meter)"
                                class="w-full px-4 py-2 bg-white border border-blue-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-hidden"
                            />
                            <p class="text-[11px] text-blue-700 leading-relaxed">Jarak akan diverifikasi panitia berdasarkan titik alamat Kartu Keluarga.</p>
                        </div>

                        <!-- Khusus Prestasi -->
                        <div v-if="form.track === 'prestasi'" class="p-4 bg-amber-50/60 border border-amber-200 rounded-2xl space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-amber-900 mb-1">Nilai Rata-rata Rapor (Skala 0 - 100)</label>
                                <input
                                    v-model="form.average_report_score"
                                    type="number"
                                    step="0.1"
                                    placeholder="Contoh: 88.5"
                                    class="w-full px-4 py-2 bg-white border border-amber-300 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-amber-900 mb-1">Uraian Prestasi / Piagam Kejuaraan</label>
                                <textarea
                                    v-model="form.achievements"
                                    rows="2"
                                    placeholder="Contoh: Juara 1 OSN Matematika Tingkat Kota 2025"
                                    class="w-full px-4 py-2 bg-white border border-amber-300 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:outline-hidden"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: Unggah Dokumen Berkas & Submit -->
                <div v-if="currentStep === 5" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-6">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">Langkah 5: Unggah Berkas Persyaratan</h2>
                        <p class="text-xs text-slate-500 mt-1">Unggah dokumen pendukung dalam format gambar (JPG/PNG) atau PDF maksimal 3 MB.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Pas Foto -->
                        <div class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-2">
                            <label class="block text-xs font-bold text-slate-800">1. Pas Foto Siswa (3x4)</label>
                            <input
                                type="file"
                                accept="image/jpeg,image/png"
                                @change="onPhotoChange"
                                class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700"
                            />
                            <div v-if="photoPreview" class="pt-2">
                                <img :src="photoPreview" alt="Preview Foto" class="w-20 h-24 object-cover rounded-xl border border-slate-300 shadow-xs" />
                            </div>
                        </div>

                        <!-- Kartu Keluarga -->
                        <div class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-2">
                            <label class="block text-xs font-bold text-slate-800">2. Scan Kartu Keluarga (KK)</label>
                            <input
                                type="file"
                                accept=".pdf,image/jpeg,image/png"
                                @change="(e) => form.doc_kk = e.target.files[0]"
                                class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700"
                            />
                        </div>

                        <!-- Akta Kelahiran -->
                        <div class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-2">
                            <label class="block text-xs font-bold text-slate-800">3. Scan Akta Kelahiran</label>
                            <input
                                type="file"
                                accept=".pdf,image/jpeg,image/png"
                                @change="(e) => form.doc_akta = e.target.files[0]"
                                class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700"
                            />
                        </div>

                        <!-- Ijazah / SKL -->
                        <div class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-2">
                            <label class="block text-xs font-bold text-slate-800">4. Scan Ijazah / SKL Rapor</label>
                            <input
                                type="file"
                                accept=".pdf,image/jpeg,image/png"
                                @change="(e) => form.doc_ijazah = e.target.files[0]"
                                class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700"
                            />
                        </div>
                    </div>

                    <!-- Pernyataan Kebenaran Data -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="flex items-start gap-3 p-4 bg-indigo-50/60 border border-indigo-100 rounded-2xl cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.agreement"
                                class="mt-0.5 accent-indigo-600 rounded"
                            />
                            <span class="text-xs text-indigo-900 leading-relaxed">
                                Saya menyatakan dengan sesungguhnya bahwa seluruh data dan dokumen yang saya isikan adalah <strong>benar dan dapat dipertanggungjawabkan</strong> secara hukum.
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Navigation Action Buttons -->
                <div class="flex items-center justify-between pt-6">
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        @click="prevStep"
                        class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition-all"
                    >
                        ← Kembali
                    </button>
                    <div v-else></div>

                    <button
                        v-if="currentStep < 5"
                        type="button"
                        @click="nextStep"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5"
                    >
                        Lanjutkan →
                    </button>

                    <button
                        v-else
                        type="submit"
                        :disabled="form.processing || !form.agreement"
                        class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-2xl text-xs font-black shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2"
                    >
                        <span v-if="form.processing">Mengirim Pendaftaran...</span>
                        <span v-else>✓ Kirim Pendaftaran & Dapatkan Bukti</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
