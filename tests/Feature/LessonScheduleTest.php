<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\LessonSchedule;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LessonScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $schoolAdmin;

    protected User $teacher;

    protected AcademicYear $academicYear;

    protected Classroom $classroom;

    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@platform.id',
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->schoolAdmin = User::factory()->create([
            'email' => 'admin@sekolah.id',
        ]);
        $this->schoolAdmin->assignRole('admin');

        $this->teacher = User::factory()->create([
            'email' => 'guru@sekolah.id',
            'name' => 'Ahmad Fauzi, S.Kom',
        ]);
        $this->teacher->assignRole('guru');

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $department = Department::create([
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Tingkat X',
            'level_numeric' => 10,
        ]);

        $this->classroom = Classroom::create([
            'name' => 'X - RPL 1',
            'academic_year_id' => $this->academicYear->id,
            'department_id' => $department->id,
            'grade_level_id' => $gradeLevel->id,
            'homeroom_teacher_id' => $this->teacher->id,
        ]);

        $this->subject = Subject::create([
            'code' => 'RPL-01',
            'name' => 'Pemrograman Web',
            'kkm' => 75,
            'type' => 'kejuruan',
            'is_active' => true,
        ]);
    }

    public function test_school_admin_can_access_lesson_schedules(): void
    {
        $this->actingAs($this->schoolAdmin);
        $this->get('/academic/schedules')->assertOk();
    }

    public function test_teacher_can_access_lesson_schedules(): void
    {
        $this->actingAs($this->teacher);
        $response = $this->get('/academic/schedules');
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Schedules')
            ->where('canManage', false)
            ->has('schedules')
            ->has('classrooms')
        );
    }

    public function test_lesson_schedule_can_be_created_and_queried(): void
    {
        $schedule = LessonSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => 'senin',
            'start_time' => '07:15',
            'end_time' => '08:45',
            'room' => 'Lab Komputer RPL 1',
            'order' => 1,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('lesson_schedules', [
            'id' => $schedule->id,
            'room' => 'Lab Komputer RPL 1',
        ]);

        $this->assertEquals('07:15 - 08:45', $schedule->time_slot);
        $this->assertEquals('Senin', $schedule->day_label);
        $this->assertEquals('Pemrograman Web', $schedule->subject->name);
        $this->assertEquals('Ahmad Fauzi, S.Kom', $schedule->teacher->name);
    }

    public function test_user_can_access_vue_academic_schedules_matrix_page(): void
    {
        $response = $this->actingAs($this->schoolAdmin)->get(route('academic.schedules'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Schedules')
            ->has('classrooms')
            ->has('schedules')
            ->has('academicYear')
        );
    }

    public function test_teacher_sees_actual_schedule_on_dashboard_when_available(): void
    {
        Carbon::setTestNow('2026-09-14 08:00:00'); // Monday
        $todayDay = 'senin';

        LessonSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => $todayDay,
            'start_time' => '08:00',
            'end_time' => '09:30',
            'room' => 'Lab IoT',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->teacher)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('schedule')
            ->where('schedule.0.subject', 'Pemrograman Web')
            ->where('schedule.0.room', 'Lab IoT')
        );
    }

    public function test_user_can_create_schedule_inline_via_post_endpoint(): void
    {
        $response = $this->actingAs($this->schoolAdmin)->post(route('academic.schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => 'selasa',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'room' => 'Lab Software 2',
            'order' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('lesson_schedules', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'selasa',
            'room' => 'Lab Software 2',
            'order' => 2,
        ]);
    }

    public function test_user_can_delete_schedule_inline_via_delete_endpoint(): void
    {
        $schedule = LessonSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => 'rabu',
            'start_time' => '10:45',
            'end_time' => '12:15',
            'room' => 'Lab Multimedia',
            'order' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->schoolAdmin)->delete(route('academic.schedules.destroy', $schedule->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('lesson_schedules', [
            'id' => $schedule->id,
        ]);
    }

    public function test_teacher_teaching_journal_dropdown_only_shows_scheduled_classes_and_subjects(): void
    {
        $otherClassroom = Classroom::create([
            'name' => 'XII - TKJ 2',
            'academic_year_id' => $this->academicYear->id,
            'department_id' => $this->classroom->department_id,
            'grade_level_id' => $this->classroom->grade_level_id,
        ]);

        $otherSubject = Subject::create([
            'code' => 'BIO-01',
            'name' => 'Biologi Terapan',
            'kkm' => 75,
            'type' => 'umum',
            'is_active' => true,
        ]);

        LessonSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => 'senin',
            'start_time' => '07:15',
            'end_time' => '08:45',
            'is_active' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->teacher)->get(route('academic.journals.index'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Journals/Index')
            ->has('classrooms', 1)
            ->where('classrooms.0.id', $this->classroom->id)
            ->has('subjects', 1)
            ->where('subjects.0.id', $this->subject->id)
            ->where('teachingAssignments.'.$this->classroom->id, [$this->subject->id])
        );
    }

    public function test_teacher_learning_dropdown_only_shows_scheduled_classes_and_subjects(): void
    {
        $otherClassroom = Classroom::create([
            'name' => 'XI - RPL 2',
            'academic_year_id' => $this->academicYear->id,
            'department_id' => $this->classroom->department_id,
            'grade_level_id' => $this->classroom->grade_level_id,
        ]);

        $otherSubject = Subject::create([
            'code' => 'KIM-01',
            'name' => 'Kimia Industri',
            'kkm' => 75,
            'type' => 'umum',
            'is_active' => true,
        ]);

        LessonSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => 'senin',
            'start_time' => '07:15',
            'end_time' => '08:45',
            'is_active' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->teacher)->get(route('academic.learning.index'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Learning/Index')
            ->has('classrooms', 1)
            ->where('classrooms.0.id', $this->classroom->id)
            ->has('subjects', 1)
            ->where('subjects.0.id', $this->subject->id)
            ->where('teachingAssignments.'.$this->classroom->id, [$this->subject->id])
        );
    }

    public function test_teacher_cbt_bank_dropdown_only_shows_scheduled_subjects(): void
    {
        $otherSubject = Subject::create([
            'code' => 'FIS-01',
            'name' => 'Fisika Terapan',
            'kkm' => 75,
            'type' => 'umum',
            'is_active' => true,
        ]);

        LessonSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => 'senin',
            'start_time' => '07:15',
            'end_time' => '08:45',
            'is_active' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->teacher)->get(route('academic.cbt.index'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Cbt/Index')
            ->has('subjects', 1)
            ->where('subjects.0.id', $this->subject->id)
            ->has('classrooms', 1)
            ->where('classrooms.0.id', $this->classroom->id)
        );
    }
}
