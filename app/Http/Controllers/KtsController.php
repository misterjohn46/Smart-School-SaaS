<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\KtsTemplate;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KtsController extends Controller
{
    /**
     * Preview Kartu Tanda Siswa (KTS) Digital format ISO CR-80
     */
    public function preview(Request $request, ?string $hashId = null): Response
    {
        $user = $request->user();

        // Hanya petugas kartu yang boleh melihat KTS siswa lain. Siswa & orang tua
        // dibatasi pada kartu miliknya sendiri; hash ID bukan pengganti otorisasi.
        $isCardOperator = $user->hasAnyRole(['admin', 'kepala_sekolah', 'operator']);

        if ($hashId) {
            $student = StudentProfile::with(['classroom.department', 'user'])->whereHashId($hashId)->first();

            if (! $student) {
                abort(404, 'Data siswa tidak ditemukan.');
            }

            $isOwnCard = $student->user_id === $user->id || $student->parent_user_id === $user->id;

            if (! $isCardOperator && ! $isOwnCard) {
                abort(403, 'Anda hanya dapat melihat Kartu Tanda Siswa milik sendiri.');
            }
        } else {
            $student = StudentProfile::with(['classroom.department', 'user'])
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere('parent_user_id', $user->id);
                })
                ->first();

            // Petugas kartu tanpa hash ID mendapat contoh kartu untuk pengecekan template.
            if (! $student && $isCardOperator) {
                $student = StudentProfile::with(['classroom.department', 'user'])->first();
            }

            if (! $student) {
                abort(404, 'Belum ada Kartu Tanda Siswa yang terhubung dengan akun Anda.');
            }
        }

        $template = KtsTemplate::where('is_active', true)->first() ?? (object) [
            'template_name' => 'Standar ISO CR-80',
            'primary_color' => '#4f46e5',
            'secondary_color' => '#f59e0b',
            'background_pattern' => 'geometric',
            'show_rfid_chip' => true,
            'show_barcode' => true,
            'show_school_logo' => true,
            'signatory_name' => 'Dr. H. Bambang Sudarmono, M.Pd',
            'signatory_title' => 'Kepala Sekolah',
        ];

        return Inertia::render('Attendance/KtsCardPreview', [
            'student' => [
                'id' => $student?->id,
                'hash_id' => $student?->hash_id,
                'name' => $student?->full_name ?? 'Ahmad Dhani Pratama',
                'nisn' => $student?->nisn ?? '0051234567',
                'nik' => $student?->nik ?? '3171011505080001',
                'classroom' => $student?->classroom?->name ?? 'X - RPL 1',
                'department' => $student?->classroom?->department?->name ?? 'Rekayasa Perangkat Lunak',
                'birth_place' => $student?->birth_place ?? 'Jakarta',
                'birth_date' => $student?->birth_date?->format('d F Y') ?? '15 Mei 2008',
                'rfid_uid' => $student?->rfid_uid ?? 'RFID-SISWA-001',
                'address' => $student?->address ?? 'Jl. Kenanga No. 12, Jakarta',
            ],
            'template' => $template,
            'school' => [
                'name' => 'SMK INFORMATIKA TERPADU',
                'npsn' => '20199887',
                'address' => 'Jl. Pendidikan No. 1, Jakarta Selatan',
            ],
        ]);
    }

    /**
     * Cetak Massal (Batch Print) KTS Digital format kertas A4
     */
    public function batchPrint(Request $request): Response
    {
        $classroomId = $request->input('classroom_id');

        $query = StudentProfile::with(['classroom.department', 'user']);

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        $students = $query->take(8)->get()->map(function ($s) {
            return [
                'name' => $s->full_name,
                'nisn' => $s->nisn,
                'classroom' => $s->classroom?->name ?? '-',
                'department' => $s->classroom?->department?->name ?? '-',
                'rfid_uid' => $s->rfid_uid ?? '-',
            ];
        });

        $template = KtsTemplate::where('is_active', true)->first();

        return Inertia::render('Attendance/KtsBatchPrint', [
            'students' => $students,
            'template' => $template,
            'classrooms' => Classroom::select('id', 'name')->get(),
        ]);
    }
}
