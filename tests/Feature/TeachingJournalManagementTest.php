<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\LessonSchedule;
use App\Models\Subject;
use App\Models\TeachingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeachingJournalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $admin;
    protected Classroom $classroom;
    protected Subject $subject;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);

        $this->admin = User::factory()->create(['name' => 'Kepala Sekolah']);
        $this->admin->assignRole('admin');

        $this->teacher = User::factory()->create(['name' => 'Budi Guru, S.Pd', 'email' => 'budi.guru@sekolah.id']);
        $this->teacher->assignRole('guru');

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $dept = Department::create(['name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);
        $grade = GradeLevel::create(['name' => 'Kelas 10', 'level_numeric' => 10]);

        $this->classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'department_id' => $dept->id,
            'grade_level_id' => $grade->id,
            'name' => 'X - RPL 1',
            'capacity' => 36,
        ]);

        $this->subject = Subject::create([
            'code' => 'RPL-01',
            'name' => 'Dasar Pemrograman Web',
            'kkm' => 75,
            'type' => 'kejuruan',
            'is_active' => true,
        ]);
    }

    public function test_teacher_can_view_teaching_journals_index_with_props(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('academic.journals.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Journals/Index')
            ->has('journals')
            ->has('todaySchedules')
            ->has('classrooms')
            ->has('subjects')
            ->has('stats')
        );
    }

    public function test_teacher_can_create_teaching_journal(): void
    {
        $payload = [
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'date' => now()->toDateString(),
            'time_slot' => 'Jam Ke 1 - 2 (07:15 - 08:45)',
            'topic_title' => 'HTML Semantics & Box Model',
            'teaching_method' => 'Praktikum / Eksperimen',
            'learning_activity' => 'Siswa mempraktikkan pembuatan layout CSS Box Model dan Flexbox.',
            'class_notes' => '3 siswa membutuhkan bimbingan intensif selektor CSS.',
            'attendance_present' => 34,
            'attendance_sick' => 1,
            'attendance_permission' => 1,
            'attendance_absent' => 0,
            'status' => 'terlaksana',
        ];

        $response = $this->actingAs($this->teacher)->post(route('academic.journals.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('teaching_journals', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'topic_title' => 'HTML Semantics & Box Model',
            'status' => 'terlaksana',
            'attendance_present' => 34,
        ]);
    }

    public function test_teacher_can_update_teaching_journal(): void
    {
        $journal = TeachingJournal::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'time_slot' => 'Jam Ke 3 - 4',
            'topic_title' => 'Algoritma Percabangan IF ELSE',
            'teaching_method' => 'Ceramah Interaktif',
            'learning_activity' => 'Penjelasan logika percabangan kondisional.',
            'status' => 'terlaksana',
        ]);

        $updatePayload = [
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'date' => now()->toDateString(),
            'time_slot' => 'Jam Ke 3 - 4',
            'topic_title' => 'Algoritma Percabangan IF ELSE & SWITCH CASE',
            'teaching_method' => 'Problem Based Learning (PBL)',
            'learning_activity' => 'Studi kasus pemecahan studi kasus kasir diskon dengan SWITCH CASE.',
            'class_notes' => 'Semua kelompok berhasil menyelesaikan mini project.',
            'attendance_present' => 36,
            'attendance_sick' => 0,
            'attendance_permission' => 0,
            'attendance_absent' => 0,
            'status' => 'terlaksana',
        ];

        $response = $this->actingAs($this->teacher)->put(
            route('academic.journals.update', $journal->id),
            $updatePayload
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('teaching_journals', [
            'id' => $journal->id,
            'topic_title' => 'Algoritma Percabangan IF ELSE & SWITCH CASE',
            'teaching_method' => 'Problem Based Learning (PBL)',
            'attendance_present' => 36,
        ]);
    }

    public function test_supervisor_can_verify_teaching_journal(): void
    {
        $journal = TeachingJournal::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'topic_title' => 'Database Relasional MySQL',
            'teaching_method' => 'Praktikum',
            'learning_activity' => 'Membuat tabel foreign key & constraints.',
            'status' => 'terlaksana',
        ]);

        $this->assertNull($journal->verified_by_user_id);

        $response = $this->actingAs($this->admin)->post(route('academic.journals.verify', $journal->id));

        $response->assertRedirect();
        $journal->refresh();
        $this->assertEquals($this->admin->id, $journal->verified_by_user_id);
        $this->assertNotNull($journal->verified_at);
    }

    public function test_teacher_can_delete_teaching_journal(): void
    {
        $journal = TeachingJournal::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'topic_title' => 'Jurnal Yang Dibatalkan',
            'teaching_method' => 'Diskusi',
            'learning_activity' => 'Dibatalkan karena upacara.',
            'status' => 'terlaksana',
        ]);

        $response = $this->actingAs($this->teacher)->delete(route('academic.journals.destroy', $journal->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('teaching_journals', [
            'id' => $journal->id,
        ]);
    }

    public function test_today_schedules_marks_already_filled_correctly(): void
    {
        $dayMap = [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
        ];
        $todayDay = $dayMap[now()->dayOfWeek] ?? 'senin';

        LessonSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => $todayDay,
            'start_time' => '07:15:00',
            'end_time' => '08:45:00',
            'order' => 1,
            'room' => 'Lab RPL 1',
            'is_active' => true,
        ]);

        // Before journal is filled -> already_filled is false
        $response = $this->actingAs($this->teacher)->get(route('academic.journals.index'));
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Journals/Index')
            ->where('todaySchedules.0.already_filled', false)
        );

        // Fill the journal for today
        TeachingJournal::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'topic_title' => 'HTML & CSS',
            'teaching_method' => 'Praktikum',
            'learning_activity' => 'Praktikum dasar web.',
            'status' => 'terlaksana',
        ]);

        // After journal is filled -> already_filled is true
        $response = $this->actingAs($this->teacher)->get(route('academic.journals.index'));
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Journals/Index')
            ->where('todaySchedules.0.already_filled', true)
        );
    }
}
