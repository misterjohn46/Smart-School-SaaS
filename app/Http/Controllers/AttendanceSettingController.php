<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\SchoolGeofence;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSettingController extends Controller
{
    /**
     * Tampilkan halaman pengaturan Geofence GPS & Jam Kerja Presensi
     */
    public function index(Request $request): Response
    {
        $geofences = SchoolGeofence::orderByDesc('is_active')->orderBy('id')->get();
        $activeGeofence = $geofences->firstWhere('is_active', true) ?? $geofences->first();

        $today = Carbon::today()->toDateString();
        $todayGpsCount = Attendance::whereDate('date', $today)
            ->where('method', 'gps_mobile')
            ->count();
        $todayTotalAttendance = Attendance::whereDate('date', $today)->count();

        return Inertia::render('Attendance/Settings', [
            'geofences' => $geofences,
            'activeGeofence' => $activeGeofence,
            'stats' => [
                'total_locations' => $geofences->count(),
                'active_location_name' => $activeGeofence?->name ?? 'Belum Diatur',
                'today_gps_count' => $todayGpsCount,
                'today_total_attendance' => $todayTotalAttendance,
            ],
        ]);
    }

    /**
     * Tambah lokasi geofence baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
            'check_in_start' => ['nullable', 'string'],
            'check_in_end' => ['nullable', 'string'],
            'late_tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'check_out_start' => ['nullable', 'string'],
            'check_out_end' => ['nullable', 'string'],
            'work_days' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active', false);

        if ($isActive) {
            SchoolGeofence::query()->update(['is_active' => false]);
        }

        SchoolGeofence::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
            'radius_meters' => (int) $validated['radius_meters'],
            'check_in_start' => $validated['check_in_start'] ?? '06:00:00',
            'check_in_end' => $validated['check_in_end'] ?? '07:15:00',
            'late_tolerance_minutes' => (int) ($validated['late_tolerance_minutes'] ?? 15),
            'check_out_start' => $validated['check_out_start'] ?? '15:00:00',
            'check_out_end' => $validated['check_out_end'] ?? '18:00:00',
            'work_days' => $validated['work_days'] ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'is_active' => $isActive || SchoolGeofence::count() === 0,
        ]);

        return back()->with('success', 'Lokasi Geofence baru berhasil ditambahkan.');
    }

    /**
     * Perbarui koordinat dan pengaturan lokasi geofence
     */
    public function update(Request $request, string|int $id): RedirectResponse
    {
        $geofence = $this->resolveGeofence($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
            'check_in_start' => ['nullable', 'string'],
            'check_in_end' => ['nullable', 'string'],
            'late_tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'check_out_start' => ['nullable', 'string'],
            'check_out_end' => ['nullable', 'string'],
            'work_days' => ['nullable', 'array'],
        ]);

        $geofence->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
            'radius_meters' => (int) $validated['radius_meters'],
            'check_in_start' => $validated['check_in_start'] ?? $geofence->check_in_start,
            'check_in_end' => $validated['check_in_end'] ?? $geofence->check_in_end,
            'late_tolerance_minutes' => isset($validated['late_tolerance_minutes']) ? (int) $validated['late_tolerance_minutes'] : $geofence->late_tolerance_minutes,
            'check_out_start' => $validated['check_out_start'] ?? $geofence->check_out_start,
            'check_out_end' => $validated['check_out_end'] ?? $geofence->check_out_end,
            'work_days' => $validated['work_days'] ?? $geofence->work_days,
        ]);

        return back()->with('success', "Pengaturan lokasi '{$geofence->name}' berhasil diperbarui.");
    }

    /**
     * Perbarui konfigurasi jam kerja presensi massal/aktif
     */
    public function updateWorkingHours(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'check_in_start' => ['required', 'string'],
            'check_in_end' => ['required', 'string'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'check_out_start' => ['required', 'string'],
            'check_out_end' => ['required', 'string'],
            'work_days' => ['required', 'array', 'min:1'],
        ]);

        SchoolGeofence::query()->update([
            'check_in_start' => $validated['check_in_start'],
            'check_in_end' => $validated['check_in_end'],
            'late_tolerance_minutes' => (int) $validated['late_tolerance_minutes'],
            'check_out_start' => $validated['check_out_start'],
            'check_out_end' => $validated['check_out_end'],
            'work_days' => $validated['work_days'],
        ]);

        return back()->with('success', 'Konfigurasi jam kerja dan batas presensi berhasil disimpan.');
    }

    /**
     * Aktifkan / nonaktifkan status lokasi
     */
    public function toggleStatus(string|int $id): RedirectResponse
    {
        $geofence = $this->resolveGeofence($id);

        if (! $geofence->is_active) {
            SchoolGeofence::query()->update(['is_active' => false]);
            $geofence->update(['is_active' => true]);
            $msg = "Lokasi '{$geofence->name}' berhasil diaktifkan sebagai geofence utama.";
        } else {
            // Cek apakah ada geofence lain
            $other = SchoolGeofence::where('id', '!=', $geofence->id)->first();
            if ($other) {
                $geofence->update(['is_active' => false]);
                $other->update(['is_active' => true]);
                $msg = "Lokasi '{$geofence->name}' dinonaktifkan. '{$other->name}' kini menjadi lokasi aktif.";
            } else {
                return back()->withErrors(['error' => 'Minimal harus ada 1 lokasi geofence yang berstatus aktif.']);
            }
        }

        return back()->with('success', $msg);
    }

    /**
     * Hapus lokasi geofence
     */
    public function destroy(string|int $id): RedirectResponse
    {
        $geofence = $this->resolveGeofence($id);

        if ($geofence->is_active) {
            $other = SchoolGeofence::where('id', '!=', $geofence->id)->first();
            if (! $other) {
                return back()->withErrors(['error' => 'Tidak dapat menghapus satu-satunya lokasi geofence aktif.']);
            }
            $other->update(['is_active' => true]);
        }

        $name = $geofence->name;
        $geofence->delete();

        return back()->with('success', "Lokasi '{$name}' berhasil dihapus.");
    }

    /**
     * Resolusi ID baik integer murni atau Obfuscated ID
     */
    protected function resolveGeofence(string|int $id): SchoolGeofence
    {
        if (is_numeric($id)) {
            return SchoolGeofence::findOrFail((int) $id);
        }

        return SchoolGeofence::findByHashId((string) $id)
            ?? SchoolGeofence::findOrFail($id);
    }
}
