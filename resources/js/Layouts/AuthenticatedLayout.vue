<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    menus: {
        type: Array,
        default: null,
    },
    sectionTitle: {
        type: String,
        default: '',
    },
});

const page = usePage();

const teacherMenus = [
    { label: 'Dashboard Guru', route: 'dashboard', icon: 'home' },
    { label: 'Jadwal Mengajar', route: 'academic.schedules', icon: 'calendar' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
    { label: 'Materi & LMS', route: 'academic.learning.index', icon: 'book-open' },
    { label: 'Penilaian Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Buku Rapor & Leger', route: 'academic.reports.index', icon: 'document' },
    { label: 'CBT Ujian Online', route: 'academic.cbt.index', icon: 'desktop' },
    { label: 'Verifikasi E-Leave', route: 'attendance.leave.index', icon: 'clipboard-check' },
    { label: 'Tata Tertib Siswa', route: 'counseling.violations.index', icon: 'clipboard-check' },
];

const adminMenus = [
    { isHeader: true, label: 'Operasional Sekolah' },
    { label: 'Command Center', route: 'dashboard', icon: 'home' },
    { label: 'Buku Nilai Siswa', route: 'academic.grades.index', icon: 'chart' },
    { label: 'Buku Rapor & Leger', route: 'academic.reports.index', icon: 'document' },
    { label: 'Tagihan & SPP Massal', route: 'finance.bills.index', icon: 'credit-card' },
    { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
    { label: 'Jadwal Pelajaran', route: 'academic.schedules', icon: 'calendar' },
    { label: 'POS Kasir SPP', route: 'finance.pos', icon: 'credit-card' },
    { label: 'Cetak Massal KTS', route: 'kts.batch', icon: 'id-card' },
    { label: 'Presensi GPS & AI', route: 'attendance.gps.index', icon: 'location' },
    { label: 'Verifikasi E-Leave', route: 'attendance.leave.index', icon: 'clipboard-check' },
    { label: 'Dompet Digital Siswa', route: 'wallet.index', icon: 'wallet' },
    { label: 'E-Kantin & Stand', route: 'canteen.index', icon: 'shopping-bag' },
    { label: 'PPDB Murid Baru', route: 'academic.ppdb.index', icon: 'users' },
    { label: 'Rapat & BAP Digital', route: 'academic.meetings.index', icon: 'document' },
    { isHeader: true, label: 'Master Data Akademik' },
    { label: 'Data Siswa & Rombel', route: 'academic.students.index', icon: 'users' },
    { label: 'Data Guru & PTK', route: 'academic.teachers.index', icon: 'academic-cap' },
    { label: 'Rombel (Kelas)', route: 'academic.classrooms.index', icon: 'rectangle-stack' },
    { label: 'Mata Pelajaran', route: 'academic.subjects.index', icon: 'book-open' },
    { label: 'Tahun Ajaran & Semester', route: 'academic.academic-years.index', icon: 'calendar' },
    { label: 'Program Keahlian', route: 'academic.departments.index', icon: 'academic-cap' },
];

const isTeacher = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    return !!(user.isTeacher || user.roles?.includes('guru') || page.props.isTeacher);
});

const resolvedMenus = computed(() => {
    // Jika login sebagai Guru, selalu pakai menu standar Guru agar seragam & tidak berubah saat navigasi
    if (isTeacher.value) {
        return teacherMenus;
    }
    if (props.menus && props.menus.length > 0) {
        return props.menus;
    }
    return adminMenus;
});

const resolvedSectionTitle = computed(() => {
    if (props.sectionTitle) return props.sectionTitle;
    return isTeacher.value ? 'Navigasi Guru' : 'Operasional Sekolah';
});
</script>

<template>
    <div class="flex min-h-screen bg-slate-50 text-slate-800">
        <!-- SIDEBAR -->
        <Sidebar :menus="resolvedMenus" :section-title="resolvedSectionTitle" />

        <!-- MAIN CONTENT AREA -->
        <div class="flex flex-1 flex-col lg:pl-64">
            <!-- Page Heading (desktop) -->
            <header
                v-if="$slots.header"
                class="hidden border-b border-slate-200 bg-white shadow-sm lg:block"
            >
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 pt-14 lg:pt-0">
                <slot />
            </main>
        </div>
    </div>
</template>
