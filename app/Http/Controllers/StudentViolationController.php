<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\StudentViolation;
use App\Models\ViolationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentViolationController extends Controller
{
    /**
     * Dashboard & Tampilan Terpadu Kedisiplinan Siswa, Tata Tertib & Konseling BK (Vue 3 SPA)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $classroomId = $request->input('classroom_id');
        $severity = $request->input('severity');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $activeYear = AcademicYear::where('is_active', true)->first();

        // 1. Tab 1: Log Pelanggaran Harian
        $query = StudentViolation::with([
            'student.classroom.department',
            'category',
            'reporter',
            'handler',
        ]);

        if ($activeYear) {
            $query->where('academic_year_id', $activeYear->id);
        }

        if ($classroomId) {
            $query->whereHas('student', fn ($q) => $q->where('classroom_id', $classroomId));
        }

        if ($severity) {
            $query->whereHas('category', fn ($q) => $q->where('severity', $severity));
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                })->orWhereHas('category', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $violations = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $violations->getCollection()->transform(function ($v) {
            return [
                'id' => $v->id,
                'date' => $v->date->format('Y-m-d'),
                'date_formatted' => $v->date->format('d M Y'),
                'time' => $v->time ? substr((string) $v->time, 0, 5) : '-',
                'student_id' => $v->student_id,
                'student_name' => $v->student?->full_name ?? 'Siswa',
                'nisn' => $v->student?->nisn ?? '-',
                'classroom_name' => $v->student?->classroom?->name ?? '-',
                'classroom_id' => $v->student?->classroom_id,
                'category_id' => $v->violation_category_id,
                'category_code' => $v->category?->code ?? '-',
                'category_name' => $v->category?->name ?? '-',
                'category_type' => $v->category?->type ?? 'pelanggaran',
                'severity' => $v->category?->severity ?? 'ringan',
                'points' => (int) $v->points,
                'notes' => $v->notes,
                'sanction' => $v->sanction ?? '-',
                'follow_up' => $v->follow_up ?? '-',
                'status' => $v->status,
                'parent_notified' => (bool) $v->parent_notified,
                'parent_notified_at' => $v->parent_notified_at?->format('d M Y H:i'),
                'reporter_name' => $v->reporter?->name ?? 'Petugas',
                'handler_name' => $v->handler?->name ?? null,
            ];
        });

        // 2. Tab 2: Radar Disiplin Siswa (Leaderboard Akumulasi Skor)
        $radarQuery = StudentProfile::with('classroom.department')
            ->whereHas('studentViolations', function ($q) use ($activeYear) {
                if ($activeYear) {
                    $q->where('academic_year_id', $activeYear->id);
                }
            })
            ->withCount(['studentViolations as total_violations' => function ($q) use ($activeYear) {
                if ($activeYear) {
                    $q->where('academic_year_id', $activeYear->id);
                }
            }])
            ->withSum(['studentViolations as total_points' => function ($q) use ($activeYear) {
                if ($activeYear) {
                    $q->where('academic_year_id', $activeYear->id);
                }
            }], 'points');

        if ($classroomId) {
            $radarQuery->where('classroom_id', $classroomId);
        }

        $radarStudents = $radarQuery->orderByDesc('total_points')
            ->limit(50)
            ->get()
            ->map(function ($s) {
                $points = (int) $s->total_points;
                $level = 'aman';
                $levelLabel = 'Aman (Teguran Lisan)';
                $levelColor = 'emerald';

                if ($points >= 75) {
                    $level = 'sp3';
                    $levelLabel = 'SP 3 (Skorsing & Sidang Kasus)';
                    $levelColor = 'rose';
                } elseif ($points >= 41) {
                    $level = 'sp2';
                    $levelLabel = 'SP 2 (Panggilan Orang Tua)';
                    $levelColor = 'amber';
                } elseif ($points >= 21) {
                    $level = 'sp1';
                    $levelLabel = 'SP 1 (Peringatan Tertulis)';
                    $levelColor = 'yellow';
                }

                return [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'nisn' => $s->nisn,
                    'classroom_name' => $s->classroom?->name ?? '-',
                    'total_points' => $points,
                    'total_violations' => (int) $s->total_violations,
                    'warning_level' => $level,
                    'warning_label' => $levelLabel,
                    'warning_color' => $levelColor,
                ];
            });

        // 3. Tab 3: Katalog Master Tata Tertib & Bobot Poin
        $categories = ViolationCategory::withCount('studentViolations')
            ->orderBy('type')
            ->orderByDesc('points')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'type' => $c->type,
                    'severity' => $c->severity,
                    'points' => (int) $c->points,
                    'action_recommendation' => $c->action_recommendation ?? '-',
                    'is_active' => (bool) $c->is_active,
                    'cases_count' => (int) $c->student_violations_count,
                ];
            });

        // KPI Ringkasan
        $stats = [
            'total_cases' => StudentViolation::count(),
            'active_cases' => StudentViolation::whereIn('status', ['tercatat', 'dalam_pembinaan', 'panggilan_orang_tua'])->count(),
            'resolved_cases' => StudentViolation::where('status', 'selesai')->count(),
            'parent_calls' => StudentViolation::where('parent_notified', true)->orWhere('status', 'panggilan_orang_tua')->count(),
            'sp_students_count' => $radarStudents->filter(fn ($s) => in_array($s['warning_level'], ['sp1', 'sp2', 'sp3']))->count(),
        ];

        $classrooms = Classroom::orderBy('name')->get(['id', 'name']);
        $categoriesList = ViolationCategory::where('is_active', true)->orderBy('name')->get([
            'id', 'code', 'name', 'type', 'severity', 'points', 'action_recommendation',
        ]);

        $students = StudentProfile::with('classroom')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nisn', 'classroom_id'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->full_name,
                'nisn' => $s->nisn,
                'classroom_id' => $s->classroom_id,
                'classroom_name' => $s->classroom?->name ?? 'Tanpa Kelas',
            ]);

        return Inertia::render('Counseling/Violations/Index', [
            'violations' => $violations,
            'radarStudents' => $radarStudents,
            'categories' => $categories,
            'categoriesList' => $categoriesList,
            'classrooms' => $classrooms,
            'students' => $students,
            'activeYear' => $activeYear ? [
                'id' => $activeYear->id,
                'name' => $activeYear->name,
                'semester' => $activeYear->semester,
            ] : null,
            'filters' => [
                'search' => $search,
                'classroom_id' => $classroomId ? (int) $classroomId : null,
                'severity' => $severity,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Catat Rekam Pelanggaran Siswa Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:student_profiles,id'],
            'violation_category_id' => ['required', 'exists:violation_categories,id'],
            'date' => ['required', 'date'],
            'time' => ['nullable'],
            'notes' => ['required', 'string'],
            'sanction' => ['nullable', 'string'],
            'follow_up' => ['nullable', 'string'],
            'status' => ['required', 'in:tercatat,dalam_pembinaan,selesai,panggilan_orang_tua'],
            'points' => ['nullable', 'integer', 'min:0'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            $activeYear = AcademicYear::firstOrCreate([
                'name' => date('Y') . '/' . (date('Y') + 1),
                'semester' => 'ganjil',
                'is_active' => true,
            ]);
        }

        $category = ViolationCategory::findOrFail($validated['violation_category_id']);

        $validated['academic_year_id'] = $activeYear->id;
        $validated['reporter_user_id'] = $request->user()->id;
        if (empty($validated['points'])) {
            $validated['points'] = $category->points;
        }

        StudentViolation::create($validated);

        return back()->with('status', 'Catatan kedisiplinan siswa berhasil disimpan!');
    }

    /**
     * Perbarui Catatan Pelanggaran & Pembinaan Siswa
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $violation = StudentViolation::findOrFail($id);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['nullable'],
            'notes' => ['required', 'string'],
            'sanction' => ['nullable', 'string'],
            'follow_up' => ['nullable', 'string'],
            'status' => ['required', 'in:tercatat,dalam_pembinaan,selesai,panggilan_orang_tua'],
            'points' => ['nullable', 'integer', 'min:0'],
        ]);

        $violation->update($validated);

        return back()->with('status', 'Catatan pelanggaran & pembinaan siswa berhasil diperbarui!');
    }

    /**
     * Hapus Catatan Pelanggaran
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $violation = StudentViolation::findOrFail($id);
        $violation->delete();

        return back()->with('status', 'Catatan kedisiplinan berhasil dihapus!');
    }

    /**
     * Tandai Pembinaan Selesai / Kasus Tuntas (1-Klik)
     */
    public function resolve(Request $request, int|string $id): RedirectResponse
    {
        $violation = StudentViolation::findOrFail($id);
        $violation->update([
            'status' => 'selesai',
            'handled_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Kasus pembinaan siswa telah ditandai selesai!');
    }

    /**
     * Tandai Surat Panggilan Orang Tua Dikirimkan (1-Klik)
     */
    public function notifyParent(Request $request, int|string $id): RedirectResponse
    {
        $violation = StudentViolation::findOrFail($id);
        $violation->update([
            'status' => 'panggilan_orang_tua',
            'parent_notified' => true,
            'parent_notified_at' => now(),
            'handled_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Surat panggilan orang tua telah tercatat dikirimkan!');
    }

    /**
     * Tambah Master Aturan Tata Tertib / Prestasi Baru
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:violation_categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:pelanggaran,prestasi'],
            'severity' => ['required', 'in:ringan,sedang,berat,sangat_berat,prestasi'],
            'points' => ['required', 'integer', 'min:1'],
            'action_recommendation' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        ViolationCategory::create($validated);

        return back()->with('status', 'Aturan tata tertib berhasil ditambahkan ke katalog!');
    }

    /**
     * Perbarui Master Aturan Tata Tertib
     */
    public function updateCategory(Request $request, int|string $id): RedirectResponse
    {
        $category = ViolationCategory::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('violation_categories', 'code')->ignore($category->id)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:pelanggaran,prestasi'],
            'severity' => ['required', 'in:ringan,sedang,berat,sangat_berat,prestasi'],
            'points' => ['required', 'integer', 'min:1'],
            'action_recommendation' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return back()->with('status', 'Aturan tata tertib berhasil diperbarui!');
    }

    /**
     * Hapus Master Aturan Tata Tertib
     */
    public function destroyCategory(int|string $id): RedirectResponse
    {
        $category = ViolationCategory::withCount('studentViolations')->findOrFail($id);

        if ($category->student_violations_count > 0) {
            return back()->withErrors([
                'error' => "Aturan tata tertib ini tidak dapat dihapus karena masih digunakan pada {$category->student_violations_count} catatan pelanggaran siswa.",
            ]);
        }

        $category->delete();

        return back()->with('status', 'Aturan tata tertib berhasil dihapus!');
    }
}
