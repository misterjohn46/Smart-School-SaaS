<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    /**
     * Tampilan Master Data Tahun Ajaran & Semester (Vue 3 SPA)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $semester = $request->input('semester');
        $isActive = $request->input('is_active');

        $query = AcademicYear::withCount('classrooms');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($semester && in_array($semester, ['ganjil', 'genap'])) {
            $query->where('semester', $semester);
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        $academicYears = $query->orderByDesc('is_active')
            ->orderByDesc('name')
            ->orderBy('semester')
            ->paginate(15)
            ->withQueryString();

        $academicYears->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'semester' => $item->semester,
                'semester_label' => $item->semester === 'ganjil' ? 'Semester Ganjil' : 'Semester Genap',
                'is_active' => (bool) $item->is_active,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'start_date_formatted' => $item->start_date?->format('d M Y') ?? '-',
                'end_date_formatted' => $item->end_date?->format('d M Y') ?? '-',
                'classrooms_count' => (int) $item->classrooms_count,
            ];
        });

        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear?->id;

        $stats = [
            'total_years' => AcademicYear::count(),
            'active_year_name' => $activeYear ? "{$activeYear->name} (" . ucfirst($activeYear->semester) . ")" : 'Belum Ada',
            'active_year_id' => $activeYearId,
            'total_classrooms' => Classroom::count(),
            'active_classrooms_count' => $activeYearId ? Classroom::where('academic_year_id', $activeYearId)->count() : 0,
        ];

        return Inertia::render('Academic/AcademicYears/Index', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear ? [
                'id' => $activeYear->id,
                'name' => $activeYear->name,
                'semester' => $activeYear->semester,
                'start_date' => $activeYear->start_date?->format('d M Y'),
                'end_date' => $activeYear->end_date?->format('d M Y'),
            ] : null,
            'filters' => [
                'search' => $search,
                'semester' => $semester,
                'is_active' => $isActive,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan Tahun Ajaran Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'semester' => ['required', 'in:ganjil,genap'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = ! empty($validated['is_active']);

        if ($isActive) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        $validated['is_active'] = $isActive;

        AcademicYear::create($validated);

        return back()->with('status', 'Tahun ajaran berhasil ditambahkan!');
    }

    /**
     * Perbarui Data Tahun Ajaran
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $academicYear = AcademicYear::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'semester' => ['required', 'in:ganjil,genap'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = ! empty($validated['is_active']);

        if ($isActive) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
        }

        $validated['is_active'] = $isActive;

        $academicYear->update($validated);

        return back()->with('status', 'Tahun ajaran berhasil diperbarui!');
    }

    /**
     * Hapus Tahun Ajaran
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $academicYear = AcademicYear::withCount('classrooms')->findOrFail($id);

        if ($academicYear->classrooms_count > 0) {
            return back()->withErrors([
                'error' => "Tidak dapat menghapus tahun ajaran ini karena masih terdapat {$academicYear->classrooms_count} rombel kelas yang terdaftar.",
            ]);
        }

        if ($academicYear->is_active) {
            return back()->withErrors([
                'error' => 'Tidak dapat menghapus tahun ajaran yang sedang aktif saat ini. Silakan aktifkan tahun ajaran lain terlebih dahulu.',
            ]);
        }

        $academicYear->delete();

        return back()->with('status', 'Tahun ajaran berhasil dihapus!');
    }

    /**
     * Jadikan Tahun Ajaran Ini sebagai Tahun Ajaran Aktif Sekolah (1-Klik)
     */
    public function activate(int|string $id): RedirectResponse
    {
        $academicYear = AcademicYear::findOrFail($id);

        AcademicYear::query()->update(['is_active' => false]);
        $academicYear->update(['is_active' => true]);

        return back()->with('status', "Tahun ajaran {$academicYear->name} (" . ucfirst($academicYear->semester) . ") kini aktif sebagai tahun ajaran operasional!");
    }
}
