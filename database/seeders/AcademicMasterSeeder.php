<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\AcademicYear;
use App\Models\AttendanceDevice;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\KtsTemplate;
use App\Models\SchoolGeofence;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AcademicMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan seluruh 11 Roles Spatie terdaftar
        foreach (RoleName::cases() as $roleEnum) {
            Role::firstOrCreate(['name' => $roleEnum->value]);
        }

        // 2. Tahun Ajaran (Academic Years)
        $yearGanjil = AcademicYear::firstOrCreate(
            ['name' => '2024/2025', 'semester' => 'ganjil'],
            [
                'is_active' => true,
                'start_date' => '2024-07-15',
                'end_date' => '2024-12-20',
            ]
        );

        $yearGenap = AcademicYear::firstOrCreate(
            ['name' => '2024/2025', 'semester' => 'genap'],
            [
                'is_active' => false,
                'start_date' => '2025-01-06',
                'end_date' => '2025-06-20',
            ]
        );

        // 3. Program Keahlian / Jurusan (Departments)
        $rpl = Department::firstOrCreate(
            ['code' => 'RPL'],
            [
                'name' => 'Rekayasa Perangkat Lunak',
                'description' => 'Program keahlian pengembangan aplikasi web, mobile, dan cloud architecture.',
                'is_active' => true,
            ]
        );

        $tkj = Department::firstOrCreate(
            ['code' => 'TKJ'],
            [
                'name' => 'Teknik Komputer dan Jaringan',
                'description' => 'Program keahlian infrastruktur jaringan, server, dan cybersecurity.',
                'is_active' => true,
            ]
        );

        $dkv = Department::firstOrCreate(
            ['code' => 'DKV'],
            [
                'name' => 'Desain Komunikasi Visual',
                'description' => 'Program keahlian multimedia, desain grafis, dan animasi 2D/3D.',
                'is_active' => true,
            ]
        );

        $akl = Department::firstOrCreate(
            ['code' => 'AKL'],
            [
                'name' => 'Akuntansi dan Keuangan Lembaga',
                'description' => 'Program keahlian akuntansi perbankan dan tata kelola keuangan.',
                'is_active' => true,
            ]
        );

        // 4. Tingkat Kelas (Grade Levels - SD, SMP, SMA/SMK)
        GradeLevel::firstOrCreate(['level_numeric' => 1], ['name' => 'Tingkat I']);
        GradeLevel::firstOrCreate(['level_numeric' => 2], ['name' => 'Tingkat II']);
        GradeLevel::firstOrCreate(['level_numeric' => 3], ['name' => 'Tingkat III']);
        GradeLevel::firstOrCreate(['level_numeric' => 4], ['name' => 'Tingkat IV']);
        GradeLevel::firstOrCreate(['level_numeric' => 5], ['name' => 'Tingkat V']);
        GradeLevel::firstOrCreate(['level_numeric' => 6], ['name' => 'Tingkat VI']);
        GradeLevel::firstOrCreate(['level_numeric' => 7], ['name' => 'Tingkat VII']);
        GradeLevel::firstOrCreate(['level_numeric' => 8], ['name' => 'Tingkat VIII']);
        GradeLevel::firstOrCreate(['level_numeric' => 9], ['name' => 'Tingkat IX']);
        $grade10 = GradeLevel::firstOrCreate(['level_numeric' => 10], ['name' => 'Tingkat X']);
        $grade11 = GradeLevel::firstOrCreate(['level_numeric' => 11], ['name' => 'Tingkat XI']);
        $grade12 = GradeLevel::firstOrCreate(['level_numeric' => 12], ['name' => 'Tingkat XII']);

        // 5. Pengguna (11 Roles Lengkap)
        // A. Super Admin Platform
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@platform.id'],
            [
                'name' => 'Super Administrator SaaS',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles([RoleName::SuperAdmin->value]);

        // B. Admin Sekolah (TU)
        $adminSekolah = User::updateOrCreate(
            ['email' => 'admin@sekolah.id'],
            [
                'name' => 'Administrator Sekolah (TU)',
                'username' => 'admin.sekolah',
                'phone_number' => '081234567800',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $adminSekolah->syncRoles([RoleName::Admin->value]);

        // C. Kepala Sekolah
        $kepsek = User::updateOrCreate(
            ['email' => 'kepsek@sekolah.id'],
            [
                'name' => 'Dr. H. Bambang Sudarmono, M.Pd',
                'username' => 'kepsek',
                'phone_number' => '081234567809',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $kepsek->syncRoles([RoleName::Headmaster->value]);

        // D. Guru 1 (Ahmad Fauzi)
        $guru1 = User::updateOrCreate(
            ['email' => 'guru@sekolah.id'],
            [
                'name' => 'Bpk. Ahmad Fauzi, S.Pd',
                'username' => 'ahmad.fauzi',
                'phone_number' => '081234567801',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $guru1->syncRoles([RoleName::Teacher->value]);

        TeacherProfile::updateOrCreate(
            ['user_id' => $guru1->id],
            [
                'nip' => '198507122010011005',
                'nuptk' => '4536763664200002',
                'full_name' => 'Bpk. Ahmad Fauzi, S.Pd',
                'gender' => 'L',
                'phone_wa' => '081234567801',
                'rfid_uid' => 'RFID-GURU-001',
                'address' => 'Jl. Pendidikan No. 10, Jakarta Selatan',
            ]
        );

        // E. Guru 2 (Sri Wahyuni)
        $guru2 = User::updateOrCreate(
            ['email' => 'guru2@sekolah.id'],
            [
                'name' => 'Ibu Sri Wahyuni, M.Kom',
                'username' => 'sri.wahyuni',
                'phone_number' => '081234567802',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $guru2->syncRoles([RoleName::Teacher->value]);

        TeacherProfile::updateOrCreate(
            ['user_id' => $guru2->id],
            [
                'nip' => '198803152014022003',
                'nuptk' => '8745766668210003',
                'full_name' => 'Ibu Sri Wahyuni, M.Kom',
                'gender' => 'P',
                'phone_wa' => '081234567802',
                'rfid_uid' => 'RFID-GURU-002',
                'address' => 'Jl. Merdeka Barat No. 8, Jakarta',
            ]
        );

        // F. Guru BK
        $guruBk = User::updateOrCreate(
            ['email' => 'gurubk@sekolah.id'],
            [
                'name' => 'Ibu Siti Rahma, S.Psi (Guru BK)',
                'username' => 'gurubk',
                'phone_number' => '081234567803',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $guruBk->syncRoles([RoleName::Counselor->value]);

        // G. Bendahara Sekolah
        $bendahara = User::updateOrCreate(
            ['email' => 'bendahara@sekolah.id'],
            [
                'name' => 'Ibu Ratna Dewi, S.E (Bendahara)',
                'username' => 'bendahara',
                'phone_number' => '081234567804',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $bendahara->syncRoles([RoleName::Treasurer->value]);

        // H. Staf TU
        $stafTu = User::updateOrCreate(
            ['email' => 'tu@sekolah.id'],
            [
                'name' => 'Bpk. Hendra Gunawan (Staf TU)',
                'username' => 'stafftu',
                'phone_number' => '081234567805',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $stafTu->syncRoles([RoleName::Staff->value]);

        // I. Petugas Perpustakaan
        $perpus = User::updateOrCreate(
            ['email' => 'perpus@sekolah.id'],
            [
                'name' => 'Ibu Maya Lestari, A.Md (Pustakawan)',
                'username' => 'perpus',
                'phone_number' => '081234567806',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $perpus->syncRoles([RoleName::Librarian->value]);

        // J. Pengelola Kantin (Vendor)
        $kantin = User::updateOrCreate(
            ['email' => 'kantin@sekolah.id'],
            [
                'name' => 'Bpk. Joko Susanto (Kantin Sehat)',
                'username' => 'kantin',
                'phone_number' => '081234567807',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $kantin->syncRoles([RoleName::Canteen->value]);

        // K. Orang Tua / Wali
        $ortu = User::updateOrCreate(
            ['email' => 'ortu@sekolah.id'],
            [
                'name' => 'Bpk. Surya Wijaya (Wali Murid)',
                'username' => 'orangtua1',
                'phone_number' => '081234567890',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $ortu->syncRoles([RoleName::Parent->value]);

        // 6. Rombongan Belajar (Classrooms)
        $classXRpl1 = Classroom::firstOrCreate(
            ['name' => 'X - RPL 1'],
            [
                'academic_year_id' => $yearGanjil->id,
                'department_id' => $rpl->id,
                'grade_level_id' => $grade10->id,
                'homeroom_teacher_id' => $guru1->id,
                'capacity' => 36,
            ]
        );

        $classXRpl2 = Classroom::firstOrCreate(
            ['name' => 'X - RPL 2'],
            [
                'academic_year_id' => $yearGanjil->id,
                'department_id' => $rpl->id,
                'grade_level_id' => $grade10->id,
                'capacity' => 36,
            ]
        );

        $classXiRpl1 = Classroom::firstOrCreate(
            ['name' => 'XI - RPL 1'],
            [
                'academic_year_id' => $yearGanjil->id,
                'department_id' => $rpl->id,
                'grade_level_id' => $grade11->id,
                'homeroom_teacher_id' => $guru2->id,
                'capacity' => 36,
            ]
        );

        $classXiTkj1 = Classroom::firstOrCreate(
            ['name' => 'XI - TKJ 1'],
            [
                'academic_year_id' => $yearGanjil->id,
                'department_id' => $tkj->id,
                'grade_level_id' => $grade11->id,
                'capacity' => 36,
            ]
        );

        $classXiiRpl1 = Classroom::firstOrCreate(
            ['name' => 'XII - RPL 1'],
            [
                'academic_year_id' => $yearGanjil->id,
                'department_id' => $rpl->id,
                'grade_level_id' => $grade12->id,
                'capacity' => 36,
            ]
        );

        // 7. Siswa & Profil Siswa
        // Siswa 1 (Kelas X - RPL 1)
        $siswa1 = User::updateOrCreate(
            ['email' => 'siswa@sekolah.id'],
            [
                'name' => 'Ahmad Dhani Pratama',
                'username' => 'siswa1',
                'nisn' => '0051234567',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $siswa1->syncRoles([RoleName::Student->value]);

        StudentProfile::updateOrCreate(
            ['user_id' => $siswa1->id],
            [
                'classroom_id' => $classXRpl1->id,
                'nisn' => '0051234567',
                'nik' => '3171011505080001',
                'full_name' => 'Ahmad Dhani Pratama',
                'gender' => 'L',
                'birth_place' => 'Jakarta',
                'birth_date' => '2008-05-15',
                'rfid_uid' => 'RFID-SISWA-001',
                'parent_user_id' => $ortu->id,
                'parent_name' => 'Bpk. Surya Wijaya',
                'parent_phone_wa' => '081234567890',
                'address' => 'Jl. Kenanga No. 12 RT 04/RW 02, Jakarta Selatan',
            ]
        );

        // Siswa 2 (Kelas XI - RPL 1)
        $siswa2 = User::updateOrCreate(
            ['email' => 'siswa2@sekolah.id'],
            [
                'name' => 'Nabila Putri Maharani',
                'username' => 'siswa2',
                'nisn' => '0059876543',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $siswa2->syncRoles([RoleName::Student->value]);

        StudentProfile::updateOrCreate(
            ['user_id' => $siswa2->id],
            [
                'classroom_id' => $classXiRpl1->id,
                'nisn' => '0059876543',
                'nik' => '3171012009070002',
                'full_name' => 'Nabila Putri Maharani',
                'gender' => 'P',
                'birth_place' => 'Bandung',
                'birth_date' => '2007-09-20',
                'rfid_uid' => 'RFID-SISWA-002',
                'parent_user_id' => $ortu->id,
                'parent_name' => 'Bpk. Surya Wijaya',
                'parent_phone_wa' => '081234567890',
                'address' => 'Jl. Kenanga No. 12 RT 04/RW 02, Jakarta Selatan',
            ]
        );

        // 8. Mata Pelajaran (Subjects)
        $subjects = [
            ['code' => 'RPL-01', 'name' => 'Pemrograman Berorientasi Objek', 'kkm' => 75, 'type' => 'kejuruan'],
            ['code' => 'RPL-02', 'name' => 'Basis Data & Big Data Analysis', 'kkm' => 75, 'type' => 'kejuruan'],
            ['code' => 'RPL-03', 'name' => 'Pemrograman Web & Mobile Devices', 'kkm' => 75, 'type' => 'kejuruan'],
            ['code' => 'TKJ-01', 'name' => 'Administrasi Infrastruktur Jaringan', 'kkm' => 75, 'type' => 'kejuruan'],
            ['code' => 'MAT-01', 'name' => 'Matematika Terapan & Diskrit', 'kkm' => 70, 'type' => 'umum'],
            ['code' => 'IND-01', 'name' => 'Bahasa Indonesia & Tata Tulis', 'kkm' => 75, 'type' => 'umum'],
            ['code' => 'ING-01', 'name' => 'Bahasa Inggris & Professional Comm', 'kkm' => 70, 'type' => 'umum'],
            ['code' => 'PAI-01', 'name' => 'Pendidikan Agama & Budi Pekerti', 'kkm' => 75, 'type' => 'umum'],
            ['code' => 'PJK-01', 'name' => 'Pendidikan Jasmani & Olahraga', 'kkm' => 75, 'type' => 'umum'],
        ];

        foreach ($subjects as $subj) {
            Subject::firstOrCreate(['code' => $subj['code']], $subj);
        }

        // 9. Perangkat IoT RFID Standalone (ESP32 via WiFi)
        AttendanceDevice::firstOrCreate(
            ['device_token_hash' => AttendanceDevice::hashToken('ESP32-GATE-MAIN-001')],
            [
                'device_token' => 'ESP32-GATE-MAIN-001',
                'device_name' => 'Gerbang Utama Siswa (ESP32 RFID)',
                'location' => 'Lobby Gedung Utama Depan',
                'device_type' => 'two_way',
                'is_active' => true,
            ]
        );

        // 10. Titik Geofencing GPS Sekolah
        SchoolGeofence::firstOrCreate(
            ['name' => 'Kampus Utama SMK'],
            [
                'latitude' => -6.2088000,
                'longitude' => 106.8456000,
                'radius_meters' => 100,
                'is_active' => true,
            ]
        );

        // 11. Template KTS Digital ISO CR-80
        KtsTemplate::firstOrCreate(
            ['template_name' => 'Standar ISO CR-80'],
            [
                'primary_color' => '#4f46e5',
                'secondary_color' => '#f59e0b',
                'background_pattern' => 'geometric',
                'show_rfid_chip' => true,
                'show_barcode' => true,
                'show_school_logo' => true,
                'signatory_name' => 'Dr. H. Bambang Sudarmono, M.Pd',
                'signatory_title' => 'Kepala Sekolah',
                'is_active' => true,
            ]
        );
    }
}
