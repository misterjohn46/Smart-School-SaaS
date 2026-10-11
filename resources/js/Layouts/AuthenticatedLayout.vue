<script setup>
import Sidebar from '@/Components/Sidebar.vue';

// Props: menus dan sectionTitle dikirim dari halaman
defineProps({
    menus: {
        type: Array,
        default: () => [
            { label: 'Dashboard', route: 'dashboard', icon: 'home' },
        ],
    },
    sectionTitle: {
        type: String,
        default: 'Navigasi Sistem',
    },
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- SIDEBAR -->
        <Sidebar :menus="menus" :section-title="sectionTitle" />

        <!-- MAIN CONTENT AREA -->
        <div class="flex flex-1 flex-col lg:pl-64">
            <!-- Page Heading (desktop) -->
            <header
                v-if="$slots.header"
                class="hidden border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:block"
            >
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 pt-16 lg:pt-0">
                <slot />
            </main>
        </div>
    </div>
</template>
