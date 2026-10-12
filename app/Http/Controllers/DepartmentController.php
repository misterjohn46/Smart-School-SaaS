<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Department;
use App\Rules\TenantUnique;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * Tampilan Master Data Program Keahlian & Jurusan (Vue 3 SPA)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $isActive = $request->input('is_active');

        $query = Department::withCount('classrooms');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        $departments = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $departments->getCollection()->transform(function ($d) {
            return [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'description' => $d->description ?? '-',
                'is_active' => (bool) $d->is_active,
                'classrooms_count' => (int) $d->classrooms_count,
            ];
        });

        $stats = [
            'total_departments' => Department::count(),
            'active_departments' => Department::where('is_active', true)->count(),
            'inactive_departments' => Department::where('is_active', false)->count(),
            'total_classrooms' => Classroom::count(),
        ];

        return Inertia::render('Academic/Departments/Index', [
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'is_active' => $isActive,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan Program Keahlian / Jurusan Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', TenantUnique::make('departments', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        Department::create($validated);

        return back()->with('status', 'Program keahlian/jurusan berhasil ditambahkan!');
    }

    /**
     * Perbarui Data Program Keahlian / Jurusan
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', TenantUnique::make('departments', 'code', $department->id)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        $department->update($validated);

        return back()->with('status', 'Program keahlian/jurusan berhasil diperbarui!');
    }

    /**
     * Hapus Program Keahlian / Jurusan
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $department = Department::withCount('classrooms')->findOrFail($id);

        if ($department->classrooms_count > 0) {
            return back()->withErrors([
                'error' => "Tidak dapat menghapus program keahlian ini karena masih terdapat {$department->classrooms_count} rombel kelas yang terdaftar.",
            ]);
        }

        $department->delete();

        return back()->with('status', 'Program keahlian/jurusan berhasil dihapus!');
    }

    /**
     * Toggle Status Aktif / Inaktif Program Keahlian (1-Klik)
     */
    public function toggleStatus(int|string $id): RedirectResponse
    {
        $department = Department::findOrFail($id);
        $department->update(['is_active' => ! $department->is_active]);

        $statusLabel = $department->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('status', "Program keahlian {$department->name} berhasil {$statusLabel}!");
    }
}
