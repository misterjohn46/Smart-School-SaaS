<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    /**
     * Tampilan Master Data Mata Pelajaran (Mapel) SPA
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $isActive = $request->input('is_active');

        $query = Subject::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($type && in_array($type, ['umum', 'kejuruan', 'mulok'])) {
            $query->where('type', $type);
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        $subjects = $query->orderBy('name')->paginate(15)->withQueryString();

        $stats = [
            'total' => Subject::count(),
            'umum_count' => Subject::where('type', 'umum')->count(),
            'kejuruan_count' => Subject::where('type', 'kejuruan')->count(),
            'mulok_count' => Subject::where('type', 'mulok')->count(),
            'active_count' => Subject::where('is_active', true)->count(),
        ];

        return Inertia::render('Academic/Subjects/Index', [
            'subjects' => $subjects,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'is_active' => $isActive,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Tambah Mata Pelajaran Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:100'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'type' => ['required', 'in:umum,kejuruan,mulok'],
            'is_active' => ['boolean'],
        ]);

        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        Subject::create($validated);

        return back()->with('status', "Mata pelajaran {$validated['name']} berhasil ditambahkan!");
    }

    /**
     * Perbarui Data Mata Pelajaran
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')->ignore($subject->id)],
            'name' => ['required', 'string', 'max:100'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'type' => ['required', 'in:umum,kejuruan,mulok'],
            'is_active' => ['boolean'],
        ]);

        $subject->update($validated);

        return back()->with('status', "Mata pelajaran {$subject->name} berhasil diperbarui!");
    }

    /**
     * Toggle Status Aktif / Nonaktif
     */
    public function toggleStatus(int|string $id): RedirectResponse
    {
        $subject = Subject::findOrFail($id);
        $subject->update(['is_active' => ! $subject->is_active]);

        $statusStr = $subject->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('status', "Mata pelajaran {$subject->name} berhasil {$statusStr}!");
    }

    /**
     * Hapus Mata Pelajaran
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $subject = Subject::findOrFail($id);
        $name = $subject->name;
        $subject->delete();

        return back()->with('status', "Mata pelajaran {$name} berhasil dihapus!");
    }
}
