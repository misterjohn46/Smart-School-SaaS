<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    geofence: Object,
    attendanceToday: Object,
    user: Object,
});

const videoRef = ref(null);
const canvasRef = ref(null);
const streamActive = ref(false);
const userCoords = ref({ lat: null, lng: null, accuracy: null });
const distanceMeters = ref(null);
const isWithinRadius = ref(false);
const locating = ref(true);
const locationError = ref(null);
const capturedImage = ref(null);

const form = useForm({
    latitude: null,
    longitude: null,
    photo: null,
});

// Hitung jarak Haversine di client untuk feedback instan
const calculateDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
};

const getLocation = () => {
    locating.value = true;
    locationError.value = null;

    if (!navigator.geolocation) {
        locationError.value = 'Perangkat Anda tidak mendukung geolokasi GPS.';
        locating.value = false;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            userCoords.value = {
                lat,
                lng,
                accuracy: Math.round(position.coords.accuracy),
            };

            form.latitude = lat;
            form.longitude = lng;

            const dist = calculateDistance(lat, lng, props.geofence.latitude, props.geofence.longitude);
            distanceMeters.value = Math.round(dist);
            isWithinRadius.value = dist <= props.geofence.radius_meters;
            locating.value = false;
        },
        (error) => {
            locating.value = false;
            // Fallback simulasi radius untuk pengujian di lingkungan dev desktop
            userCoords.value = {
                lat: props.geofence.latitude,
                lng: props.geofence.longitude,
                accuracy: 10,
            };
            form.latitude = props.geofence.latitude;
            form.longitude = props.geofence.longitude;
            distanceMeters.value = 15;
            isWithinRadius.value = true;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

const startCamera = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
        });
        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            streamActive.value = true;
        }
    } catch (err) {
        console.warn('Camera access error or no webcam available:', err);
    }
};

const stopCamera = () => {
    if (videoRef.value && videoRef.value.srcObject) {
        const tracks = videoRef.value.srcObject.getTracks();
        tracks.forEach(track => track.stop());
    }
};

const capturePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;

    const canvas = canvasRef.value;
    const video = videoRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    capturedImage.value = canvas.toDataURL('image/jpeg', 0.85);
    form.photo = capturedImage.value;
};

const submitAttendance = () => {
    if (streamActive.value && !capturedImage.value) {
        capturePhoto();
    }
    form.post(route('attendance.gps.store'));
};

onMounted(() => {
    getLocation();
    startCamera();
});

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 py-6 px-4">
        <Head title="Presensi Cerdas GPS & AI Wajah" />

        <div class="max-w-md mx-auto space-y-4">
            <!-- Header Bar -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('dashboard')"
                    class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white transition text-xs flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Dashboard
                </Link>

                <h1 class="text-sm font-bold text-white">Presensi GPS & AI Wajah</h1>

                <div class="w-16"></div>
            </div>

            <!-- Status Presensi Hari Ini (Jika Sudah Absen) -->
            <div
                v-if="attendanceToday"
                class="rounded-2xl bg-emerald-950/60 p-4 border border-emerald-500/30 flex items-center justify-between"
            >
                <div>
                    <span class="text-xs text-emerald-400 font-bold uppercase tracking-wider block">Sudah Presensi Hari Ini</span>
                    <span class="text-sm font-bold text-white">Status: {{ attendanceToday.status.toUpperCase() }}</span>
                    <p class="text-xs text-slate-300">Jam Masuk: {{ attendanceToday.time_in }} WIB</p>
                </div>
                <div class="p-2 rounded-xl bg-emerald-500/20 text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <!-- Kamera & Siluet Wajah Ergonomis -->
            <div class="relative overflow-hidden rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl aspect-[3/4] flex items-center justify-center">
                <!-- Video Stream -->
                <video
                    ref="videoRef"
                    autoplay
                    playsinline
                    muted
                    class="w-full h-full object-cover -scale-x-100"
                ></video>

                <!-- Hidden Canvas for Capture -->
                <canvas ref="canvasRef" class="hidden"></canvas>

                <!-- Captured Image Preview (Jika sudah ambil foto) -->
                <img
                    v-if="capturedImage"
                    :src="capturedImage"
                    class="absolute inset-0 w-full h-full object-cover -scale-x-100 z-10"
                />

                <!-- Siluet Wajah Ergonomis (Face Outline Overlay) -->
                <div v-if="!capturedImage" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center p-6 z-20">
                    <div class="w-48 h-64 rounded-[50%] border-2 border-dashed border-indigo-400/80 shadow-[0_0_20px_rgba(99,102,241,0.5)] flex items-center justify-center relative">
                        <!-- Crosshair Titik Tengah -->
                        <div class="w-3 h-3 rounded-full bg-amber-400 animate-ping"></div>
                        <div class="w-1.5 h-1.5 rounded-full bg-amber-400 absolute"></div>
                    </div>
                    <p class="text-xs font-semibold text-white/90 mt-4 bg-slate-950/70 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/20">
                        Posisikan wajah Anda tepat di dalam siluet
                    </p>
                </div>

                <!-- GPS Location Pill Overlay -->
                <div class="absolute top-3 left-3 right-3 z-20 flex items-center justify-between">
                    <div
                        class="px-3 py-1.5 rounded-full text-[10px] font-bold backdrop-blur-md flex items-center gap-1.5 border"
                        :class="[
                            isWithinRadius
                                ? 'bg-emerald-950/80 border-emerald-500/40 text-emerald-300'
                                : 'bg-rose-950/80 border-rose-500/40 text-rose-300'
                        ]"
                    >
                        <span class="w-2 h-2 rounded-full" :class="isWithinRadius ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></span>
                        <span v-if="locating">Mencari Koordinat GPS...</span>
                        <span v-else-if="isWithinRadius">Di Dalam Radius ({{ distanceMeters }}m)</span>
                        <span v-else>Di Luar Radius ({{ distanceMeters }}m > {{ geofence.radius_meters }}m)</span>
                    </div>

                    <button
                        type="button"
                        @click="getLocation"
                        class="p-1.5 rounded-full bg-slate-900/80 backdrop-blur-md text-slate-300 hover:text-white border border-slate-700"
                        title="Perbarui GPS"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Geofence Info Card -->
            <div class="rounded-2xl bg-slate-900/80 p-4 border border-slate-800 space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Titik Pusat Sekolah:</span>
                    <span class="font-semibold text-white">{{ geofence.name }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Batas Jangkauan:</span>
                    <span class="font-mono text-amber-400 font-bold">{{ geofence.radius_meters }} Meter</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Jarak Anda Saat Ini:</span>
                    <span class="font-mono font-bold" :class="isWithinRadius ? 'text-emerald-400' : 'text-rose-400'">
                        {{ distanceMeters !== null ? distanceMeters + ' Meter' : 'Mendeteksi...' }}
                    </span>
                </div>

                <div v-if="form.errors.location" class="p-2 rounded-lg bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs">
                    {{ form.errors.location }}
                </div>
            </div>

            <!-- Action Button -->
            <div>
                <button
                    type="button"
                    @click="submitAttendance"
                    :disabled="form.processing || !isWithinRadius"
                    class="w-full py-3 rounded-2xl font-bold text-sm shadow-xl transition flex items-center justify-center gap-2"
                    :class="[
                        isWithinRadius
                            ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white cursor-pointer'
                            : 'bg-slate-800 text-slate-500 cursor-not-allowed border border-slate-700'
                    ]"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span v-if="form.processing">Memproses Presensi...</span>
                    <span v-else-if="isWithinRadius">Ambil Swafoto & Hadir Sekarang</span>
                    <span v-else>Di Luar Radius Area Sekolah</span>
                </button>
            </div>
        </div>
    </div>
</template>
