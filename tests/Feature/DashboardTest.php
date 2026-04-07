<?php

use Carbon\Carbon;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create([
        'onboarding_completed_at' => now(),
        'req_hrs' => '486',
        'hrs' => '0',
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('holidaysByDate')
            ->where('holidaysByDate.'.Carbon::now()->year.'-01-01.name', "New Year's Day"),
        );
});
