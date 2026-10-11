<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// Tab Aktif: 'staff', 'student', 'parent'
const activeTab = ref('staff');

const form = useForm({
    login_type: 'staff',
    identifier: '',
    email: '',
    password: '',
    birth_date: '',
    remember: false,
});

const switchTab = (tab) => {
    activeTab.value = tab;
    form.login_type = tab;
    form.clearErrors();
};

const fillDemo = (type) => {
    form.clearErrors();
    if (type === 'superadmin') {
        switchTab('staff');
        form.identifier = 'superadmin@platform.id';
        form.password = 'password';
    } else if (type === 'admin') {
        switchTab('staff');
        form.identifier = 'admin@sekolah.id';
        form.password = 'password';
    } else if (type === 'guru') {
        switchTab('staff');
        form.identifier = 'guru@sekolah.id';
        form.password = 'password';
    } else if (type === 'siswa') {
        switchTab('student');
        form.identifier = '0051234567';
        form.password = 'password';
    } else if (type === 'ortu') {
        switchTab('parent');
        form.identifier = '0051234567';
        form.birth_date = '2008-05-15';
    }
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            if (activeTab.value !== 'parent') {
                form.reset('password');
            }
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Sistem Informasi Sekolah" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                SIAKAD & LMS Terpadu
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Pilih peran Anda untuk masuk ke sistem sekolah
            </p>
        </div>

        <!-- Tab Selector Multi-Metode Login -->
        <div class="mb-6 flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
            <button
                type="button"
                @click="switchTab('staff')"
                :class="[
                    'flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold rounded-lg transition-all',
                    activeTab === 'staff'
                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-gray-700 dark:text-indigo-400'
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Guru & Staf
            </button>

            <button
                type="button"
                @click="switchTab('student')"
                :class="[
                    'flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold rounded-lg transition-all',
                    activeTab === 'student'
                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-gray-700 dark:text-indigo-400'
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
                Siswa (NISN)
            </button>

            <button
                type="button"
                @click="switchTab('parent')"
                :class="[
                    'flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold rounded-lg transition-all',
                    activeTab === 'parent'
                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-gray-700 dark:text-indigo-400'
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Orang Tua
            </button>
        </div>

        <div v-if="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-700 border border-green-200 dark:bg-green-950 dark:border-green-800 dark:text-green-300">
            {{ status }}
        </div>

        <!-- Form Autentikasi -->
        <form @submit.prevent="submit" class="space-y-4">
            <!-- TAB 1: PEGAWAI / GURU -->
            <template v-if="activeTab === 'staff'">
                <div>
                    <InputLabel for="staff_identifier" value="Email atau Username" />
                    <TextInput
                        id="staff_identifier"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.identifier"
                        placeholder="admin@sekolah.id atau guru@sekolah.id"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.identifier" />
                </div>

                <div>
                    <InputLabel for="staff_password" value="Kata Sandi (Password)" />
                    <TextInput
                        id="staff_password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
            </template>

            <!-- TAB 2: SISWA (NISN + PASSWORD) -->
            <template v-else-if="activeTab === 'student'">
                <div>
                    <InputLabel for="student_nisn" value="NISN atau Username Siswa" />
                    <TextInput
                        id="student_nisn"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.identifier"
                        placeholder="Contoh: 0051234567"
                        required
                        autofocus
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Masukkan 10 digit NISN yang tertera di Kartu Pelajar
                    </p>
                    <InputError class="mt-2" :message="form.errors.identifier" />
                </div>

                <div>
                    <InputLabel for="student_password" value="Kata Sandi Akun Siswa" />
                    <TextInput
                        id="student_password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        placeholder="••••••••"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
            </template>

            <!-- TAB 3: ORANG TUA / WALI (NISN SISWA + TANGGAL LAHIR SISWA) -->
            <template v-else-if="activeTab === 'parent'">
                <div>
                    <InputLabel for="parent_student_nisn" value="NISN Putra / Putri Anda" />
                    <TextInput
                        id="parent_student_nisn"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.identifier"
                        placeholder="Contoh: 0051234567"
                        required
                        autofocus
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Gunakan NISN siswa untuk memverifikasi akun wali murid
                    </p>
                    <InputError class="mt-2" :message="form.errors.identifier" />
                </div>

                <div>
                    <InputLabel for="parent_birth_date" value="Tanggal Lahir Siswa" />
                    <TextInput
                        id="parent_birth_date"
                        type="date"
                        class="mt-1 block w-full"
                        v-model="form.birth_date"
                        required
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Digunakan sebagai verifikasi identitas aman orang tua
                    </p>
                    <InputError class="mt-2" :message="form.errors.birth_date" />
                </div>
            </template>

            <!-- Remember & Forgot Password -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-xs text-gray-600 dark:text-gray-400">Ingat Saya</span>
                </label>

                <Link
                    v-if="canResetPassword && activeTab === 'staff'"
                    :href="route('password.request')"
                    class="text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                >
                    Lupa password?
                </Link>
            </div>

            <!-- Tombol Masuk -->
            <div>
                <PrimaryButton
                    class="w-full justify-center py-2.5 text-sm font-semibold tracking-wide shadow-md"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memverifikasi...</span>
                    <span v-else>Masuk ke Portal Sekolah</span>
                </PrimaryButton>
            </div>
        </form>

        <!-- Quick Demo Accounts Helper -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-800">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider text-center mb-3">
                Akun Demo Cepat (1-Klik Isi)
            </p>
            <div class="flex flex-wrap gap-2 justify-center">
                <button
                    type="button"
                    @click="fillDemo('superadmin')"
                    class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 dark:bg-purple-900/40 dark:text-purple-300 transition"
                >
                    👑 Super Admin
                </button>
                <button
                    type="button"
                    @click="fillDemo('admin')"
                    class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-300 transition"
                >
                    🏢 Admin TU
                </button>
                <button
                    type="button"
                    @click="fillDemo('guru')"
                    class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 transition"
                >
                    👨‍🏫 Guru
                </button>
                <button
                    type="button"
                    @click="fillDemo('siswa')"
                    class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 transition"
                >
                    🎒 Siswa
                </button>
                <button
                    type="button"
                    @click="fillDemo('ortu')"
                    class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-rose-100 text-rose-800 hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-300 transition"
                >
                    👨‍👩‍👧 Orang Tua
                </button>
            </div>
        </div>
    </GuestLayout>
</template>
