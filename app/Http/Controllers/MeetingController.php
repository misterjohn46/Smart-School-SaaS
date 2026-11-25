<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MeetingAttendance;
use App\Models\SchoolMeeting;
use App\Models\Tenant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    /**
     * Tampilan Daftar Agenda Rapat & Berita Acara Sekolah
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');

        $query = SchoolMeeting::with(['leaderUser', 'secretaryUser'])
            ->withCount('attendances');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('agenda', 'like', "%{$search}%")
                    ->orWhere('meeting_number', 'like', "%{$search}%");
            });
        }

        if (! empty($type) && $type !== 'all') {
            $query->where('meeting_type', $type);
        }

        if (! empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        $meetings = $query->orderBy('meeting_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(12)
            ->withQueryString();

        $teachers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['guru', 'kepala_sekolah', 'admin', 'operator', 'staff']);
        })->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);

        $stats = [
            'total' => SchoolMeeting::count(),
            'scheduled' => SchoolMeeting::where('status', 'scheduled')->count(),
            'ongoing' => SchoolMeeting::where('status', 'ongoing')->count(),
            'completed' => SchoolMeeting::where('status', 'completed')->count(),
            'this_month' => SchoolMeeting::whereMonth('meeting_date', now()->month)
                ->whereYear('meeting_date', now()->year)
                ->count(),
        ];

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
            'teachers' => $teachers,
            'filters' => [
                'search' => $search ?? '',
                'type' => $type ?? 'all',
                'status' => $status ?? 'all',
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Jadwalkan Rapat Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'meeting_type' => ['required', 'in:dewan_guru,pleno,wali_murid,komite,koordinasi_jurusan,kedisiplinan,lainnya'],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['required', 'string', 'max:10'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'location' => ['required', 'string', 'max:150'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'leader_user_id' => ['nullable', 'exists:users,id'],
            'secretary_user_id' => ['nullable', 'exists:users,id'],
            'agenda' => ['nullable', 'string', 'max:2000'],
            'target_audience' => ['nullable', 'string', 'max:150'],
            'auto_invite_teachers' => ['nullable', 'boolean'],
        ]);

        $year = date('Y');
        $monthRoman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][date('n') - 1];
        $meetingCount = SchoolMeeting::count() + 1;
        $meetingNumber = sprintf('BAP-RAPAT/%s/%s/%03d', $year, $monthRoman, $meetingCount);

        $leaderName = null;
        if (! empty($validated['leader_user_id'])) {
            $leaderName = User::find($validated['leader_user_id'])?->name;
        }

        $secretaryName = null;
        if (! empty($validated['secretary_user_id'])) {
            $secretaryName = User::find($validated['secretary_user_id'])?->name;
        }

        $meeting = DB::transaction(function () use ($validated, $meetingNumber, $leaderName, $secretaryName) {
            $m = SchoolMeeting::create([
                'title' => $validated['title'],
                'meeting_type' => $validated['meeting_type'],
                'meeting_number' => $meetingNumber,
                'meeting_date' => $validated['meeting_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'] ?? null,
                'location' => $validated['location'],
                'meeting_link' => $validated['meeting_link'] ?? null,
                'leader_user_id' => $validated['leader_user_id'] ?? null,
                'leader_name' => $leaderName,
                'secretary_user_id' => $validated['secretary_user_id'] ?? null,
                'secretary_name' => $secretaryName,
                'agenda' => $validated['agenda'] ?? null,
                'target_audience' => $validated['target_audience'] ?? 'Seluruh Dewan Guru & Staf',
                'status' => 'scheduled',
                'action_items' => [],
                'attachments' => [],
                'photos' => [],
            ]);

            // Jika diatur mengundang guru secara otomatis
            if (! empty($validated['auto_invite_teachers'])) {
                $teachers = User::whereHas('roles', function ($q) {
                    $q->whereIn('name', ['guru', 'kepala_sekolah', 'operator', 'staff']);
                })->where('is_active', true)->get();

                foreach ($teachers as $teacher) {
                    MeetingAttendance::create([
                        'school_meeting_id' => $m->id,
                        'user_id' => $teacher->id,
                        'participant_name' => $teacher->name,
                        'participant_role' => $teacher->roles->first()?->name === 'guru' ? 'Guru Pengajar' : 'Tenaga Pendidik',
                        'status' => 'hadir',
                    ]);
                }
            }

            return $m;
        });

        return redirect()->route('academic.meetings.show', $meeting->id)
            ->with('success', "Agenda rapat '{$meeting->title}' berhasil dijadwalkan!");
    }

    /**
     * Tampilan Detail Ruang Rapat, Notulen & Lembar Presensi Digital
     */
    public function show(string|int $id): Response
    {
        $meeting = $this->resolveMeeting($id)->load([
            'leaderUser',
            'secretaryUser',
            'attendances.user',
        ]);

        $currentUser = Auth::user();
        $userAttendance = $meeting->attendances->firstWhere('user_id', $currentUser->id);

        $teachers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['guru', 'kepala_sekolah', 'operator', 'staff']);
        })->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);

        $attendancesStats = [
            'total' => $meeting->attendances->count(),
            'hadir' => $meeting->attendances->where('status', 'hadir')->count(),
            'izin' => $meeting->attendances->where('status', 'izin')->count(),
            'sakit' => $meeting->attendances->where('status', 'sakit')->count(),
            'signed' => $meeting->attendances->whereNotNull('signed_at')->count(),
        ];

        return Inertia::render('Meetings/Show', [
            'meeting' => $meeting,
            'currentUser' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'role' => $currentUser->roles->first()?->name ?? 'guru',
            ],
            'userAttendance' => $userAttendance,
            'teachers' => $teachers,
            'attendancesStats' => $attendancesStats,
        ]);
    }

    /**
     * Perbarui Rincian Jadwal / Info Rapat
     */
    public function update(Request $request, string|int $id): RedirectResponse
    {
        $meeting = $this->resolveMeeting($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'meeting_type' => ['required', 'in:dewan_guru,pleno,wali_murid,komite,koordinasi_jurusan,kedisiplinan,lainnya'],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['required', 'string', 'max:10'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'location' => ['required', 'string', 'max:150'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'leader_user_id' => ['nullable', 'exists:users,id'],
            'secretary_user_id' => ['nullable', 'exists:users,id'],
            'agenda' => ['nullable', 'string', 'max:2000'],
            'target_audience' => ['nullable', 'string', 'max:150'],
        ]);

        if (! empty($validated['leader_user_id'])) {
            $validated['leader_name'] = User::find($validated['leader_user_id'])?->name;
        }

        if (! empty($validated['secretary_user_id'])) {
            $validated['secretary_name'] = User::find($validated['secretary_user_id'])?->name;
        }

        $meeting->update($validated);

        return back()->with('success', "Informasi rapat '{$meeting->title}' berhasil diperbarui.");
    }

    /**
     * Simpan Notulen Rapat, Resume Keputusan & Tindak Lanjut (Action Items)
     */
    public function updateNotes(Request $request, string|int $id): RedirectResponse
    {
        $meeting = $this->resolveMeeting($id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'decisions' => ['nullable', 'string'],
            'action_items' => ['nullable', 'array'],
            'action_items.*.task' => ['required', 'string'],
            'action_items.*.pic' => ['required', 'string'],
            'action_items.*.due_date' => ['nullable', 'string'],
            'action_items.*.is_done' => ['nullable', 'boolean'],
            'status' => ['required', 'in:scheduled,ongoing,completed,cancelled'],
        ]);

        $updateData = [
            'notes' => $validated['notes'] ?? null,
            'decisions' => $validated['decisions'] ?? null,
            'action_items' => $validated['action_items'] ?? [],
            'status' => $validated['status'],
        ];

        if ($validated['status'] === 'completed' && ! $meeting->bap_generated_at) {
            $updateData['bap_generated_at'] = Carbon::now();
        }

        $meeting->update($updateData);

        return back()->with('success', 'Notulen rapat dan resume keputusan berhasil disimpan!');
    }

    /**
     * Presensi Mandiri & Tanda Tangan Digital Guru (Canvas Signature Pad)
     */
    public function signAttendance(Request $request, string|int $id): RedirectResponse
    {
        $meeting = $this->resolveMeeting($id);
        $user = Auth::user();

        $validated = $request->validate([
            'status' => ['required', 'in:hadir,izin,sakit'],
            'notes' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'string'], // Base64 Data URL PNG
        ]);

        if ($validated['status'] === 'hadir' && empty($validated['signature'])) {
            return back()->withErrors(['signature' => 'Tanda tangan digital pada layar wajib dibubuhkan untuk presensi Hadir.']);
        }

        MeetingAttendance::updateOrCreate(
            [
                'school_meeting_id' => $meeting->id,
                'user_id' => $user->id,
            ],
            [
                'tenant_id' => $meeting->tenant_id,
                'participant_name' => $user->name,
                'participant_role' => $user->roles->first()?->name === 'guru' ? 'Guru Pengajar' : 'Tenaga Pendidik',
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'signature' => $validated['signature'] ?? null,
                'signed_at' => Carbon::now(),
            ]
        );

        return back()->with('success', 'Presensi dan tanda tangan digital Anda berhasil dicatat!');
    }

    /**
     * Tambah Peserta Rapat Manual
     */
    public function addParticipant(Request $request, string|int $id): RedirectResponse
    {
        $meeting = $this->resolveMeeting($id);

        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'participant_name' => ['required', 'string', 'max:150'],
            'participant_role' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:hadir,izin,sakit,alpa'],
        ]);

        if (! empty($validated['user_id'])) {
            $existing = MeetingAttendance::where('school_meeting_id', $meeting->id)
                ->where('user_id', $validated['user_id'])
                ->first();

            if ($existing) {
                return back()->withErrors(['user_id' => 'Peserta ini sudah terdaftar dalam lembar presensi.']);
            }
        }

        MeetingAttendance::create([
            'tenant_id' => $meeting->tenant_id,
            'school_meeting_id' => $meeting->id,
            'user_id' => $validated['user_id'] ?? null,
            'participant_name' => $validated['participant_name'],
            'participant_role' => $validated['participant_role'] ?? 'Peserta Rapat',
            'status' => $validated['status'],
        ]);

        return back()->with('success', "Peserta '{$validated['participant_name']}' berhasil ditambahkan ke daftar hadir.");
    }

    /**
     * Unggah Foto Dokumentasi Rapat
     */
    public function uploadPhoto(Request $request, string|int $id): RedirectResponse
    {
        $meeting = $this->resolveMeeting($id);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $request->file('photo');
        $path = $file->store('meetings/photos/'.$meeting->id, 'public');

        $photos = $meeting->photos ?? [];
        $photos[] = $path;

        $meeting->update(['photos' => $photos]);

        return back()->with('success', 'Foto dokumentasi rapat berhasil diunggah.');
    }

    /**
     * Unggah Berkas Materi / Lampiran Dokumen Rapat (PDF/PPT)
     */
    public function uploadAttachment(Request $request, string|int $id): RedirectResponse
    {
        $meeting = $this->resolveMeeting($id);

        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:pdf,ppt,pptx,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('meetings/attachments/'.$meeting->id, 'public');

        $attachments = $meeting->attachments ?? [];
        $attachments[] = [
            'title' => $request->input('title'),
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'size' => round($file->getSize() / 1024, 1).' KB',
            'uploaded_at' => Carbon::now()->format('d M Y, H:i'),
        ];

        $meeting->update(['attachments' => $attachments]);

        return back()->with('success', 'Berkas materi rapat berhasil dilampirkan.');
    }

    /**
     * Unduh / Cetak Berita Acara Rapat (BAP) Resmi Format PDF
     */
    public function generateBapPdf(string|int $id): HttpResponse
    {
        $meeting = $this->resolveMeeting($id)->load([
            'leaderUser',
            'secretaryUser',
            'attendances.user',
        ]);

        $tenant = (function_exists('tenant') && tenant())
            ? tenant()
            : (auth()->user()?->tenant_id ? Tenant::find(auth()->user()->tenant_id) : Tenant::first());
        $schoolName = $tenant?->school_name ?? config('app.name', 'Sistem Informasi Sekolah');

        $attendances = $meeting->attendances->sortBy('participant_name');

        $pdf = Pdf::loadView('pdf.meeting-bap', [
            'meeting' => $meeting,
            'attendances' => $attendances,
            'schoolName' => $schoolName,
            'stats' => [
                'total' => $attendances->count(),
                'hadir' => $attendances->where('status', 'hadir')->count(),
                'izin' => $attendances->where('status', 'izin')->count(),
                'sakit' => $attendances->where('status', 'sakit')->count(),
                'alpa' => $attendances->where('status', 'alpa')->count(),
            ],
        ]);

        $pdf->setPaper('A4', 'portrait');

        $fileName = 'BAP-Rapat-'.Str::slug($meeting->title).'-'.date('Ymd').'.pdf';

        return $pdf->stream($fileName);
    }

    /**
     * Dapatkan Teks Broadcast Undangan WhatsApp
     */
    public function whatsappShareText(string|int $id): JsonResponse
    {
        $meeting = $this->resolveMeeting($id);

        $tenant = (function_exists('tenant') && tenant())
            ? tenant()
            : (auth()->user()?->tenant_id ? Tenant::find(auth()->user()->tenant_id) : Tenant::first());
        $schoolName = $tenant?->school_name ?? 'Sekolah';

        $text = "*UNDANGAN RESMI RAPAT {$schoolName}*\n\n"
            ."Kepada Yth. Bapak/Ibu Dewan Guru & Staf,\n"
            ."Dengan hormat, mengundang kehadiran Bapak/Ibu dalam agenda rapat kedinasan:\n\n"
            ."📌 *Agenda:* {$meeting->title}\n"
            ."🏷️ *Jenis:* {$meeting->type_label}\n"
            .'📅 *Hari/Tanggal:* '.$meeting->meeting_date->translatedFormat('l, d F Y')."\n"
            ."⏰ *Waktu:* {$meeting->start_time} WIB ".($meeting->end_time ? "s/d {$meeting->end_time} WIB" : 's/d Selesai')."\n"
            ."📍 *Tempat:* {$meeting->location}\n"
            .($meeting->meeting_link ? "🔗 *Tautan Online:* {$meeting->meeting_link}\n" : '')
            .'👤 *Pimpinan Rapat:* '.($meeting->leader_name ?: 'Kepala Sekolah')."\n\n"
            .($meeting->agenda ? "📝 *Pokok Pembahasan:*\n{$meeting->agenda}\n\n" : '')
            ."Bapak/Ibu diharapkan hadir tepat waktu dan mengisi presensi serta tanda tangan digital melalui tautan sistem:\n"
            .route('academic.meetings.show', $meeting->id)."\n\n"
            .'Demikian undangan ini kami sampaikan, atas perhatian dan kerja samanya diucapkan terima kasih.';

        return response()->json([
            'text' => $text,
            'wa_url' => 'https://api.whatsapp.com/send?text='.urlencode($text),
        ]);
    }

    /**
     * Hapus Agenda Rapat
     */
    public function destroy(string|int $id): RedirectResponse
    {
        $meeting = $this->resolveMeeting($id);
        $title = $meeting->title;
        $meeting->delete();

        return redirect()->route('academic.meetings.index')
            ->with('success', "Agenda rapat '{$title}' berhasil dihapus.");
    }

    protected function resolveMeeting(string|int $id): SchoolMeeting
    {
        if (is_numeric($id)) {
            return SchoolMeeting::findOrFail((int) $id);
        }

        return SchoolMeeting::findByHashId((string) $id) ?? SchoolMeeting::findOrFail($id);
    }
}
