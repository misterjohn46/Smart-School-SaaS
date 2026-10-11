<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// ============================================================
// Props: daftar menu dari parent (bisa dikustomisasi per halaman)
// ============================================================
const props = defineProps({
    menus: {
        type: Array,
        default: () => [
            { label: 'Dashboard Guru', route: 'dashboard', icon: 'home' },
            { label: 'Jadwal Mengajar', route: 'academic.schedules', icon: 'calendar' },
            { label: 'Jurnal Mengajar', route: 'academic.journals.index', icon: 'document' },
            { label: 'Presensi Kelas', route: 'attendance.classroom.index', icon: 'clipboard' },
            { label: 'Tata Tertib & BK', route: 'counseling.violations.index', icon: 'clipboard-check' },
            { label: 'CBT Ujian Online', route: 'academic.cbt.index', icon: 'desktop' },
            { label: 'Penilaian Siswa', route: 'academic.grades.index', icon: 'chart' },
            { label: 'Buku Rapor & Leger', route: 'academic.reports.index', icon: 'document' },
            { label: 'Radius GPS & Shift', route: 'attendance.settings.index', icon: 'location' },
        ],
    },
    sectionTitle: {
        type: String,
        default: 'Navigasi Sistem',
    },
});

// State mobile sidebar (show/hide)
const mobileOpen = ref(false);

const closeMobile = () => {
    mobileOpen.value = false;
};

const page = usePage();

const isActive = (menu) => {
    if (menu.route && typeof route === 'function') {
        try {
            if (route().current(menu.route)) return true;
            if (route().current(`${menu.route}.*`)) return true;
        } catch (e) {
            // fallback
        }
    }
    if (menu.url && page.url && page.url.startsWith(menu.url)) {
        return true;
    }
    return false;
};

// ============================================================
// Ikon SVG Profesional
// ============================================================
const iconPaths = {
    home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    book: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    desktop: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    cog: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    logout: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
    'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    'id-card': 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2',
    location: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
    'clipboard-check': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    wallet: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    'shopping-bag': 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
    database: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'academic-cap': 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
    'rectangle-stack': 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    'book-open': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
};
</script>

<template>
    <!-- Overlay backdrop -->
    <div
        v-show="mobileOpen"
        @click="closeMobile"
        class="fixed inset-0 z-40 bg-gray-900/50 transition-opacity lg:hidden"
    ></div>

    <!-- ===== SIDEBAR UTAMA ===== -->
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-50 flex w-64 flex-col',
            'bg-slate-900 text-slate-100',
            'border-r border-slate-800',
            'transform transition-transform duration-300 ease-in-out',
            mobileOpen ? 'translate-x-0' : '-translate-x-full',
            'lg:translate-x-0',
        ]"
    >
        <!-- ===== BRAND / LOGO SEKOLAH ===== -->
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800 px-5 bg-slate-950/60">
            <Link :href="route('dashboard')" class="flex items-center gap-3" @click="closeMobile">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-amber-500 flex items-center justify-center font-black text-white shadow-md shadow-indigo-500/20">
                    S
                </div>
                <div>
                    <span class="text-sm font-bold text-white tracking-wide block leading-tight">SIAKAD & LMS</span>
                    <span class="text-[10px] text-slate-400 font-medium tracking-wider uppercase block">SMK INFORMATIKA</span>
                </div>
            </Link>
        </div>

        <!-- ===== MENU NAVIGASI ===== -->
        <nav class="flex flex-1 flex-col overflow-y-auto px-3 py-4 space-y-1">
            <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                {{ sectionTitle }}
            </div>

            <ul class="space-y-1">
                <li v-for="(menu, idx) in menus" :key="idx">
                    <!-- Header Seksi Kategori -->
                    <div
                        v-if="menu.isHeader"
                        class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-t border-slate-800/60 mt-2 first:mt-0 first:border-0 first:pt-0"
                    >
                        {{ menu.label }}
                    </div>

                    <!-- Link Eksternal / Native -->
                    <a
                        v-else-if="menu.url"
                        :href="menu.url"
                        :target="menu.external ? '_blank' : undefined"
                        @click="closeMobile"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold transition-all duration-200',
                            isActive(menu)
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30'
                                : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                        ]"
                    >
                        <svg
                            class="h-4 w-4 shrink-0 transition-colors"
                            :class="isActive(menu) ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400'"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="iconPaths[menu.icon] || iconPaths.home"
                            />
                        </svg>
                        <span class="flex-1 truncate">{{ menu.label }}</span>
                        <span v-if="menu.badge" class="px-1.5 py-0.5 text-[9px] rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold">
                            {{ menu.badge }}
                        </span>
                    </a>

                    <!-- Link Inertia SPA -->
                    <Link
                        v-else
                        :href="route(menu.route)"
                        @click="closeMobile"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold transition-all duration-200',
                            isActive(menu)
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30'
                                : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                        ]"
                    >
                        <!-- Icon -->
                        <svg
                            class="h-4 w-4 shrink-0 transition-colors"
                            :class="isActive(menu) ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400'"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="iconPaths[menu.icon] || iconPaths.home"
                            />
                        </svg>

                        <span class="flex-1 truncate">{{ menu.label }}</span>

                        <span
                            v-if="isActive(menu)"
                            class="w-1.5 h-1.5 rounded-full bg-amber-400 shadow-sm"
                        ></span>
                    </Link>
                </li>
            </ul>

            <!-- ===== BOTTOM SECTION: User Info + Logout ===== -->
            <div class="mt-auto pt-4 border-t border-slate-800/80">
                <!-- User Profile Card -->
                <div class="mb-3 rounded-xl bg-slate-800/60 p-3 border border-slate-700/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shrink-0">
                            {{ $page.props.auth.user.name.charAt(0) }}
                        </div>
                        <div class="overflow-hidden flex-1">
                            <p class="truncate text-xs font-bold text-white">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="truncate text-[10px] text-slate-400">
                                {{ $page.props.auth.user.email }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Links -->
                <ul class="space-y-1">
                    <li>
                        <Link
                            :href="route('profile.edit')"
                            @click="closeMobile"
                            class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white"
                        >
                            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths.cog" />
                            </svg>
                            Pengaturan Profil
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            @click="closeMobile"
                            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-rose-400 transition hover:bg-rose-950/40 hover:text-rose-300"
                        >
                            <svg class="h-4 w-4 shrink-0 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths.logout" />
                            </svg>
                            Keluar dari Akun
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>

    <!-- ===== MOBILE HEADER BAR ===== -->
    <header
        class="fixed top-0 left-0 right-0 z-30 flex h-14 items-center justify-between border-b border-slate-800 bg-slate-900 px-4 lg:hidden"
    >
        <button
            @click="mobileOpen = !mobileOpen"
            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
        >
            <svg v-if="!mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <span class="text-sm font-bold text-white">SIAKAD & LMS Terpadu</span>

        <div class="w-8"></div>
    </header>
</template>
