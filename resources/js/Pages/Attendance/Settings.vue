<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    geofences: Array,
    activeGeofence: Object,
    stats: Object,
});

// Tab Aktif
const activeTab = ref('locations'); // 'locations' | 'hours'

// State Modal Lokasi
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingGeofence = ref(null);
const gpsLoading = ref(false);
const gpsStatusMessage = ref('');

// Form Lokasi Geofence
const locationForm = useForm({
    name: '',
    address: '',
    latitude: -6.2088000,
    longitude: 106.8456000,
    radius_meters: 100,
    is_active: true,
});

// Form Jam Kerja
const hoursForm = useForm({
    check_in_start: props.activeGeofence?.check_in_start || '06:00',
    check_in_end: props.activeGeofence?.check_in_end || '07:15',
    late_tolerance_minutes: props.activeGeofence?.late_tolerance_minutes ?? 15,
    check_out_start: props.activeGeofence?.check_out_start || '15:00',
    check_out_end: props.activeGeofence?.check_out_end || '18:00',
    work_days: props.activeGeofence?.work_days || ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
});

const dayOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

const toggleWorkDay = (day) => {
    const idx = hoursForm.work_days.indexOf(day);
    if (idx > -1) {
        if (hoursForm.work_days.length > 1) {
            hoursForm.work_days.splice(idx, 1);
        }
    } else {
        hoursForm.work_days.push(day);
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    editingGeofence.value = null;
    gpsStatusMessage.value = '';
    locationForm.reset();
    locationForm.name = '';
    locationForm.address = '';
    locationForm.latitude = props.activeGeofence ? props.activeGeofence.latitude : -6.2088000;
    locationForm.longitude = props.activeGeofence ? props.activeGeofence.longitude : 106.8456000;
    locationForm.radius_meters = 100;
    locationForm.is_active = false;
    isModalOpen.value = true;
};

const openEditModal = (gf) => {
    isEditing.value = true;
    editingGeofence.value = gf;
    gpsStatusMessage.value = '';
    locationForm.name = gf.name;
    locationForm.address = gf.address || '';
    locationForm.latitude = gf.latitude;
    locationForm.longitude = gf.longitude;
    locationForm.radius_meters = gf.radius_meters;
    locationForm.is_active = gf.is_active;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    locationForm.reset();
};

// Ambil koordinat GPS dari browser pengguna
const getCurrentBrowserLocation = () => {
    if (!navigator.geolocation) {
        gpsStatusMessage.value = 'Browser Anda tidak mendukung Geolocation API.';
        return;
    }

    gpsLoading.value = true;
    gpsStatusMessage.value = 'Mendeteksi titik koordinat perangkat Anda...';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            gpsLoading.value = false;
            const lat = Number(position.coords.latitude.toFixed(7));
            const lng = Number(position.coords.longitude.toFixed(7));
            const accuracy = Math.round(position.coords.accuracy);

            locationForm.latitude = lat;
            locationForm.longitude = lng;
            gpsStatusMessage.value = `✓ Koordinat GPS berhasil disinkronkan (Akurasi: ±${accuracy} meter).`;
        },
        (error) => {
            gpsLoading.value = false;
            let msg = 'Gagal mendeteksi lokasi: ';
            if (error.code === error.PERMISSION_DENIED) {
                msg += 'Izin akses lokasi ditolak oleh pengguna.';
            } else if (error.code === error.POSITION_UNAVAILABLE) {
                msg += 'Informasi lokasi tidak tersedia.';
            } else {
                msg += 'Waktu permintaan lokasi habis.';
            }
            gpsStatusMessage.value = msg;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

const submitLocation = () => {
    if (isEditing.value && editingGeofence.value) {
        const id = editingGeofence.value.hashid || editingGeofence.value.id;
        locationForm.put(route('attendance.geofences.update', id), {
            onSuccess: () => closeModal(),
        });
    } else {
        locationForm.post(route('attendance.geofences.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const toggleStatus = (gf) => {
    const id = gf.hashid || gf.id;
    router.post(route('attendance.geofences.toggle', id));
};

const deleteLocation = (gf) => {
    if (confirm(`Yakin ingin menghapus lokasi '${gf.name}'?`)) {
        const id = gf.hashid || gf.id;
        router.delete(route('attendance.geofences.destroy', id));
    }
};

const submitHours = () => {
    hoursForm.post(route('attendance.settings.hours'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Geofence GPS & Jam Kerja Presensi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">📍</span>
                        Pengaturan Radius Geofence & Jam Presensi
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Konfigurasikan titik koordinat GPS radius sekolah, toleransi keterlambatan, dan jam shift absensi harian.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="openCreateModal"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2"
                    >
                        <span>+</span> Tambah Titik Lokasi
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-2">
                <span>✓</span> {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.errors?.error" class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-sm flex items-center gap-2">
                <span>⚠️</span> {{ $page.props.errors.error }}
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                        📍
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total_locations }}</div>
                        <div class="text-xs font-medium text-slate-500">Titik Geofence Terdaftar</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                        🟢
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-800 truncate max-w-[150px]">{{ stats.active_location_name }}</div>
                        <div class="text-xs font-medium text-emerald-600">Geofence Utama Aktif</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl font-bold">
                        📱
                    </div>
                    <div>
                        <div class="text-2xl font-black text-purple-700">{{ stats.today_gps_count }}</div>
                        <div class="text-xs font-medium text-slate-500">Presensi GPS Hari Ini</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                        ⏰
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-800">{{ activeGeofence?.check_in_end ? activeGeofence.check_in_end.slice(0, 5) : '07:15' }} WIB</div>
                        <div class="text-xs font-medium text-amber-600">Batas Masuk Tepat Waktu</div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-200">
                <button
                    @click="activeTab = 'locations'"
                    :class="[
                        'px-5 py-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'locations'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>🧭</span> Lokasi & Radius Geofence ({{ geofences.length }})
                </button>
                <button
                    @click="activeTab = 'hours'"
                    :class="[
                        'px-5 py-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-all',
                        activeTab === 'hours'
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40 rounded-t-xl'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <span>⏱️</span> Jam Kerja & Toleransi Keterlambatan
                </button>
            </div>

            <!-- TAB 1: Lokasi & Radius Geofence -->
            <div v-if="activeTab === 'locations'" class="space-y-6">
                <!-- Visual Radius Simulator Preview -->
                <div v-if="activeGeofence" class="bg-gradient-to-r from-slate-900 to-indigo-950 p-6 rounded-3xl text-white shadow-lg">
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                        <div class="space-y-3 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-bold rounded-full">
                                    ● LOKASI AKTIF
                                </span>
                                <span class="text-xs text-slate-400">ID: #{{ activeGeofence.id }}</span>
                            </div>
                            <h3 class="text-2xl font-black text-white tracking-tight">{{ activeGeofence.name }}</h3>
                            <p class="text-xs text-slate-300">{{ activeGeofence.address || 'Alamat belum dilengkapi' }}</p>
                            
                            <div class="flex flex-wrap gap-4 pt-2 text-xs">
                                <div class="bg-white/10 px-3 py-1.5 rounded-lg border border-white/10">
                                    <span class="text-slate-400 block">Latitude</span>
                                    <span class="font-mono font-bold text-white">{{ activeGeofence.latitude }}</span>
                                </div>
                                <div class="bg-white/10 px-3 py-1.5 rounded-lg border border-white/10">
                                    <span class="text-slate-400 block">Longitude</span>
                                    <span class="font-mono font-bold text-white">{{ activeGeofence.longitude }}</span>
                                </div>
                                <div class="bg-indigo-500/30 px-3 py-1.5 rounded-lg border border-indigo-400/30">
                                    <span class="text-indigo-200 block">Radius Kehadiran</span>
                                    <span class="font-bold text-white text-sm">{{ activeGeofence.radius_meters }} Meter</span>
                                </div>
                            </div>
                        </div>

                        <!-- Radar Graphic Visual -->
                        <div class="relative w-44 h-44 shrink-0 flex items-center justify-center">
                            <div class="absolute inset-0 rounded-full border border-dashed border-indigo-400/30 animate-spin" style="animation-duration: 20s;"></div>
                            <div class="absolute w-32 h-32 rounded-full border border-indigo-500/40 bg-indigo-600/10"></div>
                            <div class="absolute w-20 h-20 rounded-full border border-emerald-500/50 bg-emerald-500/20"></div>
                            <div class="w-8 h-8 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50 flex items-center justify-center text-white text-xs font-black z-10">
                                🏫
                            </div>
                            <div class="absolute -bottom-2 bg-slate-900/90 px-2.5 py-0.5 rounded-full text-[10px] text-indigo-300 font-bold border border-indigo-500/30">
                                Radius: {{ activeGeofence.radius_meters }}m
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Lokasi -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h4 class="font-bold text-slate-800 text-sm">Daftar Titik Geofence Sekolah</h4>
                        <span class="text-xs text-slate-500">{{ geofences.length }} lokasi terdaftar</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-3">Nama Lokasi & Alamat</th>
                                    <th class="px-6 py-3">Koordinat GPS</th>
                                    <th class="px-6 py-3">Radius Sekolah</th>
                                    <th class="px-6 py-3 text-center">Status Keaktifan</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="gf in geofences" :key="gf.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                            <span>🏫</span> {{ gf.name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ gf.address || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-600">
                                        <div>Lat: {{ gf.latitude }}</div>
                                        <div>Lng: {{ gf.longitude }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            🎯 {{ gf.radius_meters }} meter
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            @click="toggleStatus(gf)"
                                            :class="[
                                                'px-3 py-1 rounded-full text-xs font-bold transition-all shadow-xs',
                                                gf.is_active
                                                    ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
                                            ]"
                                        >
                                            {{ gf.is_active ? '● Aktif (Utama)' : '○ Nonaktif' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button
                                            @click="openEditModal(gf)"
                                            class="px-2.5 py-1 text-xs font-semibold text-slate-600 bg-slate-100 rounded-md hover:bg-slate-200 transition-colors"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            v-if="!gf.is_active"
                                            @click="deleteLocation(gf)"
                                            class="px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50 rounded-md hover:bg-rose-100 transition-colors"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Jam Kerja & Batas Presensi -->
            <div v-if="activeTab === 'hours'" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="text-base font-bold text-slate-800">Konfigurasi Jam Operasional & Batas Presensi</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Sistem secara otomatis mengklasifikasikan presensi sebagai 'Hadir Tepat Waktu' atau 'Terlambat' berdasarkan batasan ini.
                    </p>
                </div>

                <form @submit.prevent="submitHours" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Jam Masuk Buka -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Jam Buka Presensi Masuk
                            </label>
                            <input
                                v-model="hoursForm.check_in_start"
                                type="time"
                                step="1"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                required
                            />
                            <p class="text-[11px] text-slate-400">Jam paling awal siswa/guru dapat merekam presensi masuk.</p>
                        </div>

                        <!-- Batas Akhir Tepat Waktu -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-amber-700 uppercase tracking-wider">
                                Batas Tepat Waktu (Batas Hadir)
                            </label>
                            <input
                                v-model="hoursForm.check_in_end"
                                type="time"
                                step="1"
                                class="w-full px-3 py-2 border border-amber-300 bg-amber-50/30 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:outline-hidden font-bold text-amber-900"
                                required
                            />
                            <p class="text-[11px] text-slate-400">Presensi setelah jam ini akan otomatis berstatus <strong>Terlambat</strong>.</p>
                        </div>

                        <!-- Toleransi Keterlambatan -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Toleransi Keterlambatan (Menit)
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="hoursForm.late_tolerance_minutes"
                                    type="number"
                                    min="0"
                                    max="120"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                    required
                                />
                                <span class="text-xs text-slate-500 font-bold shrink-0">Menit</span>
                            </div>
                            <p class="text-[11px] text-slate-400">Masa dispensasi keterlambatan sebelum tercatat sebagai pelanggaran.</p>
                        </div>

                        <!-- Jam Mulai Presensi Pulang -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Jam Buka Presensi Pulang
                            </label>
                            <input
                                v-model="hoursForm.check_out_start"
                                type="time"
                                step="1"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                required
                            />
                            <p class="text-[11px] text-slate-400">Jam tap pulang tidak diterima sebelum waktu ini.</p>
                        </div>

                        <!-- Jam Tutup Presensi Pulang -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Jam Tutup Presensi Pulang
                            </label>
                            <input
                                v-model="hoursForm.check_out_end"
                                type="time"
                                step="1"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                required
                            />
                            <p class="text-[11px] text-slate-400">Batas akhir tap pulang sore hari.</p>
                        </div>
                    </div>

                    <!-- Hari Kerja Aktif -->
                    <div class="space-y-3 pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Hari Kerja Operasional Presensi
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="day in dayOptions"
                                :key="day"
                                type="button"
                                @click="toggleWorkDay(day)"
                                :class="[
                                    'px-4 py-2 rounded-xl text-xs font-bold border transition-all',
                                    hoursForm.work_days.includes(day)
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                                        : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'
                                ]"
                            >
                                {{ day }}
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button
                            type="submit"
                            :disabled="hoursForm.processing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all disabled:opacity-50"
                        >
                            {{ hoursForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan Jam Kerja' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL TAMBAH / EDIT LOKASI GEOFENCE -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                        <span>📍</span> {{ isEditing ? 'Edit Lokasi Geofence' : 'Tambah Lokasi Geofence Baru' }}
                    </h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>

                <form @submit.prevent="submitLocation" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lokasi / Kampus</label>
                        <input
                            v-model="locationForm.name"
                            type="text"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            placeholder="Contoh: Kampus Utama SMK Informatika"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap</label>
                        <textarea
                            v-model="locationForm.address"
                            rows="2"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            placeholder="Contoh: Jl. Pendidikan No. 45, Jakarta Selatan"
                        ></textarea>
                    </div>

                    <!-- Tombol GPS Browser Geolocation -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700">Penyelarasan Titik Koordinat</span>
                            <button
                                type="button"
                                @click="getCurrentBrowserLocation"
                                :disabled="gpsLoading"
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs flex items-center gap-1.5 transition-colors disabled:opacity-50"
                            >
                                <span>📡</span> {{ gpsLoading ? 'Mencari Satelit...' : 'Ambil Koordinat Saya Saat Ini' }}
                            </button>
                        </div>
                        <p v-if="gpsStatusMessage" class="text-[11px] font-medium" :class="gpsStatusMessage.startsWith('✓') ? 'text-emerald-700' : 'text-rose-600'">
                            {{ gpsStatusMessage }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Latitude</label>
                            <input
                                v-model="locationForm.latitude"
                                type="number"
                                step="any"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Longitude</label>
                            <input
                                v-model="locationForm.longitude"
                                type="number"
                                step="any"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                required
                            />
                        </div>
                    </div>

                    <!-- Radius Slider -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-bold text-slate-700">Radius Jangkauan Presensi</label>
                            <span class="text-xs font-extrabold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-100">
                                {{ locationForm.radius_meters }} Meter
                            </span>
                        </div>
                        <input
                            v-model.number="locationForm.radius_meters"
                            type="range"
                            min="25"
                            max="1000"
                            step="25"
                            class="w-full accent-indigo-600 cursor-pointer"
                        />
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                            <span>25 m (Gedung Tunggal)</span>
                            <span>100 m (Standar)</span>
                            <span>500 m (Kampus Luas)</span>
                            <span>1000 m</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-slate-300 text-slate-700 rounded-xl text-xs font-semibold hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="locationForm.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors disabled:opacity-50"
                        >
                            {{ locationForm.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Lokasi') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
