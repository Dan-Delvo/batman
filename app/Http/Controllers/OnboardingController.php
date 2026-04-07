<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding/Index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'req_hrs' => ['required', 'numeric', 'min:1'],
            'company' => ['required', 'string', 'max:255'],
            'supervisor' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $user->forceFill([
            'name' => $validated['name'],
            'req_hrs' => (string) $validated['req_hrs'],
            'company' => $validated['company'],
            'supervisor' => $validated['supervisor'],
            'hrs' => $user->hrs ?: '0',
            'onboarding_completed_at' => now(),
        ])->save();

        return redirect()->route('dashboard');
    }
}

