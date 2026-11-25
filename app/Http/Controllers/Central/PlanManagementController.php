<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanManagementController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::withCount(['subscriptions'])->get();

        return Inertia::render('Central/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:plans,slug'],
            'price_monthly' => ['required', 'integer', 'min:0'],
            'price_yearly' => ['required', 'integer', 'min:0'],
            'max_students' => ['required', 'integer', 'min:1'],
            'max_teachers' => ['nullable', 'integer', 'min:1'],
            'max_storage_mb' => ['nullable', 'integer', 'min:100'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_popular'] = $request->boolean('is_popular', false);

        Plan::create($validated);

        return redirect()->route('central.plans.index')->with('success', 'Paket langganan baru berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:plans,slug,'.$plan->id],
            'price_monthly' => ['required', 'integer', 'min:0'],
            'price_yearly' => ['required', 'integer', 'min:0'],
            'max_students' => ['required', 'integer', 'min:1'],
            'max_teachers' => ['nullable', 'integer', 'min:1'],
            'max_storage_mb' => ['nullable', 'integer', 'min:100'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_popular'] = $request->boolean('is_popular');

        $plan->update($validated);

        return redirect()->route('central.plans.index')->with('success', "Paket {$plan->name} berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $plan = Plan::withCount('subscriptions')->findOrFail($id);

        if ($plan->subscriptions_count > 0) {
            return redirect()->route('central.plans.index')->with('error', "Paket {$plan->name} tidak dapat dihapus karena masih digunakan oleh {$plan->subscriptions_count} langganan sekolah aktif.");
        }

        $plan->delete();

        return redirect()->route('central.plans.index')->with('success', "Paket {$plan->name} berhasil dihapus.");
    }
}
