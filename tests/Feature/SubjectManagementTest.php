<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);

        $this->admin = User::factory()->create(['email' => 'admin@sekolah.id']);
        $this->admin->assignRole('admin');

        $this->subject = Subject::create([
            'code' => 'MTK-01',
            'name' => 'Matematika Wajib',
            'kkm' => 75,
            'type' => 'umum',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_subjects_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academic.subjects.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Subjects/Index')
            ->has('subjects.data', 1)
            ->where('subjects.data.0.code', 'MTK-01')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_subjects_by_search_and_type(): void
    {
        Subject::create([
            'code' => 'RPL-01',
            'name' => 'Pemrograman Web',
            'kkm' => 78,
            'type' => 'kejuruan',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('academic.subjects.index', [
            'type' => 'kejuruan',
            'search' => 'Pemrograman',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Subjects/Index')
            ->has('subjects.data', 1)
            ->where('subjects.data.0.code', 'RPL-01')
        );
    }

    public function test_admin_can_create_new_subject(): void
    {
        $response = $this->actingAs($this->admin)->post(route('academic.subjects.store'), [
            'code' => 'BIN-01',
            'name' => 'Bahasa Indonesia',
            'kkm' => 75,
            'type' => 'umum',
            'is_active' => true,
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('subjects', [
            'code' => 'BIN-01',
            'name' => 'Bahasa Indonesia',
            'kkm' => 75,
            'type' => 'umum',
        ]);
    }

    public function test_admin_can_update_subject(): void
    {
        $response = $this->actingAs($this->admin)->put(route('academic.subjects.update', $this->subject->id), [
            'code' => 'MTK-01',
            'name' => 'Matematika Peminatan & Terapan',
            'kkm' => 80,
            'type' => 'kejuruan',
            'is_active' => true,
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('subjects', [
            'id' => $this->subject->id,
            'name' => 'Matematika Peminatan & Terapan',
            'kkm' => 80,
            'type' => 'kejuruan',
        ]);
    }

    public function test_admin_can_toggle_subject_status(): void
    {
        $this->assertTrue((bool) $this->subject->is_active);

        $response = $this->actingAs($this->admin)->post(route('academic.subjects.toggle', $this->subject->id));

        $response->assertSessionHas('status');
        $this->subject->refresh();
        $this->assertFalse((bool) $this->subject->is_active);

        // Toggle kembali ke aktif
        $this->actingAs($this->admin)->post(route('academic.subjects.toggle', $this->subject->id));
        $this->subject->refresh();
        $this->assertTrue((bool) $this->subject->is_active);
    }

    public function test_admin_can_delete_subject(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('academic.subjects.destroy', $this->subject->id));

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('subjects', [
            'id' => $this->subject->id,
        ]);
    }
}
