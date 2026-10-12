<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\LessonAttendance;
use App\Models\LessonSchedule;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassroomAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $schoolAdmin;

    protected User $teacher;

    protected User $student;

    protected Classroom $classroom;

    protected LessonSchedule $schedule;

    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guru']);
        Role::firstOrCreate(['name' => 'siswa']);

        $this->schoolAdmin = User::factory()->create(['email' => 'admin@sekolah.id']);
        $this->schoolAdmin->assignRole('admin');

        $this->teacher = User::factory()->create(['email' => 'guru@sekolah.id']);
        $this->teacher->assignRole('guru');

        $this->student = User::factory()->create(['email' => 'siswa@sekolah.id', 'name' => 'Ahmad Fauzi']);
        $this->student->assignRole('siswa');

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025 Ganjil',
            'semester' => 'ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);

        $dept = Department::create(['name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);
        $grade = GradeLevel::create(['name' => 'Kelas 10', 'level_numeric' => 10]);

        $this->classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'department_id' => $dept->id,
            'grade_level_id' => $grade->id,
            'homeroom_teacher_id' => $this->teacher->id,
            'name' => 'X - RPL 1',
            'capacity' => 36,
        ]);

        StudentProfile::create([
            'user_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'nisn' => '0089123456',
            'full_name' => 'Ahmad Fauzi',
            'gender' => 'L',
        ]);

        $subject = Subject::create([
            'code' => 'PW-01',
            'name' => 'Pemrograman Web',
            'is_active' => true,
        ]);

        $this->schedule = LessonSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $this->teacher->id,
            'day_of_week' => 'senin',
            'start_time' => '07:15:00',
            'end_time' => '08:45:00',
            'room' => 'Lab Komputer RPL 1',
            'order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_access_classroom_attendance_page(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('attendance.classroom.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Attendance/ClassroomAttendance')
            ->has('classrooms')
            ->has('students')
            ->has('summary')
            ->has('canEdit')
        );
    }

    public function test_user_can_filter_classroom_attendance_by_class_and_date(): void
    {
        // 2026-09-14 adalah hari Senin
        $testDate = '2026-09-14';

        $response = $this->actingAs($this->schoolAdmin)->get(route('attendance.classroom.index', [
            'classroom_id' => $this->classroom->id,
            'date' => $testDate,
            'schedule_id' => $this->schedule->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Attendance/ClassroomAttendance')
            ->where('selectedClassroomId', $this->classroom->id)
            ->where('selectedDate', $testDate)
            ->where('dayOfWeek', 'Senin')
            ->where('selectedScheduleId', $this->schedule->id)
            ->has('students', 1)
            ->where('students.0.name', 'Ahmad Fauzi')
            ->where('students.0.status', 'hadir')
        );
    }

    public function test_user_can_batch_store_classroom_attendance_and_sync_daily_records(): void
    {
        $testDate = '2026-09-14';

        $payload = [
            'classroom_id' => $this->classroom->id,
            'lesson_schedule_id' => $this->schedule->id,
            'date' => $testDate,
            'attendances' => [
                [
                    'student_user_id' => $this->student->id,
                    'status' => 'sakit',
                    'notes' => 'Demam tinggi dan istirahat dokter',
                ],
            ],
        ];

        $response = $this->actingAs($this->teacher)
            ->from(route('attendance.classroom.index'))
            ->post(route('attendance.classroom.store'), $payload);

        $response->assertRedirect(route('attendance.classroom.index'));
        $response->assertSessionHas('status');

        // 1. Verifikasi tersimpan di lesson_attendances
        $this->assertDatabaseHas('lesson_attendances', [
            'classroom_id' => $this->classroom->id,
            'lesson_schedule_id' => $this->schedule->id,
            'student_user_id' => $this->student->id,
            'recorded_by_user_id' => $this->teacher->id,
            'status' => 'sakit',
            'notes' => 'Demam tinggi dan istirahat dokter',
        ]);

        $record = LessonAttendance::where('student_user_id', $this->student->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals($testDate, $record->date->toDateString());

        // 2. Verifikasi tersinkronkan ke attendances harian
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'status' => 'sakit',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_classroom_attendance(): void
    {
        $response = $this->get(route('attendance.classroom.index'));

        $response->assertRedirect(route('login'));
    }
}
