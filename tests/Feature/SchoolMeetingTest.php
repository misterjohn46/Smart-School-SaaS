<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MeetingAttendance;
use App\Models\SchoolMeeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolMeetingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected SchoolMeeting $meeting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed-central');

        $this->admin = User::where('email', 'admin@sekolah.id')->firstOrFail();
        $this->teacher = User::where('email', 'guru@sekolah.id')->firstOrFail();

        $this->meeting = SchoolMeeting::create([
            'tenant_id' => $this->admin->tenant_id,
            'title' => 'Rapat Pleno Persiapan PAS Semester Ganjil',
            'meeting_type' => 'pleno',
            'meeting_number' => 'BAP-RAPAT/2026/X/001',
            'meeting_date' => now()->toDateString(),
            'start_time' => '08:30',
            'end_time' => '11:00',
            'location' => 'Ruang Guru & Meeting Hall',
            'leader_user_id' => $this->admin->id,
            'leader_name' => $this->admin->name,
            'secretary_user_id' => $this->teacher->id,
            'secretary_name' => $this->teacher->name,
            'agenda' => "1. Pembagian jadwal pengawas ujian\n2. Penyerahan naskah soal",
            'target_audience' => 'Seluruh Dewan Guru',
            'status' => 'scheduled',
            'action_items' => [],
            'attachments' => [],
            'photos' => [],
        ]);
    }

    public function test_user_can_view_meetings_index_page(): void
    {
        $response = $this->actingAs($this->teacher)->get('/academic/meetings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Meetings/Index')
            ->has('meetings')
            ->has('stats')
            ->has('teachers')
        );
    }

    public function test_user_can_schedule_new_meeting(): void
    {
        $payload = [
            'title' => 'Rapat Koordinasi Jurusan Rekayasa Perangkat Lunak',
            'meeting_type' => 'koordinasi_jurusan',
            'meeting_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:30',
            'location' => 'Lab Komputer RPL 1',
            'leader_user_id' => $this->admin->id,
            'agenda' => 'Penyelarasan kurikulum industri dan magang siswa PKL.',
            'target_audience' => 'Guru Produktif RPL',
            'auto_invite_teachers' => true,
        ];

        $response = $this->actingAs($this->admin)->post('/academic/meetings', $payload);

        $this->assertDatabaseHas('school_meetings', [
            'title' => 'Rapat Koordinasi Jurusan Rekayasa Perangkat Lunak',
            'meeting_type' => 'koordinasi_jurusan',
            'location' => 'Lab Komputer RPL 1',
        ]);

        $created = SchoolMeeting::where('title', 'Rapat Koordinasi Jurusan Rekayasa Perangkat Lunak')->firstOrFail();
        $response->assertRedirect("/academic/meetings/{$created->id}");

        // Verifikasi dewan guru otomatis diundang ke lembar presensi
        $this->assertDatabaseHas('meeting_attendances', [
            'school_meeting_id' => $created->id,
            'user_id' => $this->teacher->id,
        ]);
    }

    public function test_user_can_view_meeting_room_details(): void
    {
        $response = $this->actingAs($this->teacher)->get("/academic/meetings/{$this->meeting->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Meetings/Show')
            ->has('meeting')
            ->has('currentUser')
            ->has('attendancesStats')
        );
    }

    public function test_user_can_update_meeting_notes_and_decisions(): void
    {
        $response = $this->actingAs($this->teacher)->put("/academic/meetings/{$this->meeting->id}/notes", [
            'notes' => 'Rapat dibuka pukul 08:35 WIB oleh Kepala Sekolah. Seluruh naskah soal telah diverifikasi.',
            'decisions' => "1. Ujian PAS dilaksanakan berbasis CBT.\n2. Siswa remedial mengikuti pendalaman materi.",
            'status' => 'completed',
            'action_items' => [
                [
                    'task' => 'Cetak kartu peserta PAS',
                    'pic' => 'Bpk. Ahmad (Operator)',
                    'due_date' => '2026-11-05',
                    'is_done' => false,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->meeting->refresh();

        $this->assertEquals('completed', $this->meeting->status);
        $this->assertNotNull($this->meeting->bap_generated_at);
        $this->assertStringContainsString('Ujian PAS dilaksanakan berbasis CBT', $this->meeting->decisions);
        $this->assertCount(1, $this->meeting->action_items);
    }

    public function test_participant_can_sign_digital_attendance(): void
    {
        // Fake data URL signature PNG
        $dummySignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($this->teacher)->post("/academic/meetings/{$this->meeting->id}/sign", [
            'status' => 'hadir',
            'signature' => $dummySignature,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('meeting_attendances', [
            'school_meeting_id' => $this->meeting->id,
            'user_id' => $this->teacher->id,
            'status' => 'hadir',
        ]);

        $attendance = MeetingAttendance::where('school_meeting_id', $this->meeting->id)
            ->where('user_id', $this->teacher->id)
            ->firstOrFail();

        $this->assertNotNull($attendance->signed_at);
        $this->assertEquals($dummySignature, $attendance->signature);
    }

    public function test_can_upload_meeting_documentation_photo(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('suasana_rapat.jpg', 800, 600);

        $response = $this->actingAs($this->admin)->post("/academic/meetings/{$this->meeting->id}/photos", [
            'photo' => $photo,
        ]);

        $response->assertRedirect();
        $this->meeting->refresh();

        $this->assertNotEmpty($this->meeting->photos);
        $this->assertCount(1, $this->meeting->photos);
    }

    public function test_can_upload_meeting_attachment_material(): void
    {
        Storage::fake('public');

        $doc = UploadedFile::fake()->create('slide_rapat.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->admin)->post("/academic/meetings/{$this->meeting->id}/attachments", [
            'title' => 'Materi Presentasi Pelaksanaan Ujian',
            'file' => $doc,
        ]);

        $response->assertRedirect();
        $this->meeting->refresh();

        $this->assertNotEmpty($this->meeting->attachments);
        $this->assertEquals('Materi Presentasi Pelaksanaan Ujian', $this->meeting->attachments[0]['title']);
    }

    public function test_can_generate_official_bap_pdf(): void
    {
        // Tambahkan presensi peserta ber-TTD
        MeetingAttendance::create([
            'tenant_id' => $this->meeting->tenant_id,
            'school_meeting_id' => $this->meeting->id,
            'user_id' => $this->teacher->id,
            'participant_name' => $this->teacher->name,
            'participant_role' => 'Guru Pengajar',
            'status' => 'hadir',
            'signed_at' => now(),
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);

        $response = $this->actingAs($this->teacher)->get("/academic/meetings/{$this->meeting->id}/bap-pdf");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_can_generate_whatsapp_invitation_text(): void
    {
        $response = $this->actingAs($this->teacher)->get("/academic/meetings/{$this->meeting->id}/wa-text");

        $response->assertOk();
        $response->assertJsonStructure(['text', 'wa_url']);
        $this->assertStringContainsString($this->meeting->title, $response->json('text'));
    }
}
