<?php

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\DailyLogController;
use App\Models\DailyLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('daily-logs', [DailyLogController::class, 'upsert'])->name('daily-logs.upsert');

    Route::get('dashboard', function (Request $request) {
        if (! $request->user()->hasCompletedOnboarding()) {
            return redirect()->route('onboarding.show');
        }

        $user = $request->user();

        $totalHours = (float) $user->hrs;
        $requiredHours = max((float) $user->req_hrs, 0);
        $remainingHours = max($requiredHours - $totalHours, 0);

        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        $lastWeekEnd = $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $computeHours = function ($logs): float {
            return collect($logs)->reduce(function (float $carry, DailyLog $log) {
                if (! $log->time_in || ! $log->time_out) {
                    return $carry;
                }

                $timeIn = Carbon::parse($log->time_in);
                $timeOut = Carbon::parse($log->time_out);
                $minutes = max($timeIn->diffInMinutes($timeOut, false), 0);
                $consumedMinutes = max($minutes - 60, 0); // deduct 1 hour lunch per log

                return $carry + ($consumedMinutes / 60);
            }, 0.0);
        };

        $currentWeekLogs = collect();
        $lastWeekLogs = collect();
        $dailyLogDates = [];
        $dailyLogsByDate = [];

        if (Schema::hasTable('daily_logs')) {
            $allLogs = DailyLog::query()
                ->where('user_id', $user->id)
                ->get();

            // Always derive total consumed hours from logs to keep dashboard + users.hrs in sync.
            $totalHours = round($computeHours($allLogs), 1);
            $requiredHours = max((float) $user->req_hrs, 0);
            $remainingHours = max($requiredHours - $totalHours, 0);

            if ((float) $user->hrs !== $totalHours) {
                $user->forceFill([
                    'hrs' => number_format($totalHours, 1, '.', ''),
                ])->save();
            }

            $currentWeekLogs = $allLogs
                ->filter(fn (DailyLog $log) => Carbon::parse($log->log_date)->betweenIncluded($weekStart, $weekEnd))
                ->filter(fn (DailyLog $log) => Carbon::parse($log->log_date)->isWeekday());

            $lastWeekLogs = $allLogs
                ->filter(fn (DailyLog $log) => Carbon::parse($log->log_date)->betweenIncluded($lastWeekStart, $lastWeekEnd))
                ->filter(fn (DailyLog $log) => Carbon::parse($log->log_date)->isWeekday());

            $dailyLogDates = $allLogs
                ->pluck('log_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->values()
                ->all();

            $dailyLogsByDate = $allLogs
                ->mapWithKeys(fn (DailyLog $log) => [
                    Carbon::parse($log->log_date)->toDateString() => [
                        'time_in' => $log->time_in,
                        'time_out' => $log->time_out,
                        'tasks_done' => $log->tasks_done,
                    ],
                ])
                ->all();
        }

        $weeklyHours = round($computeHours($currentWeekLogs), 1);
        $lastWeeklyHours = round($computeHours($lastWeekLogs), 1);
        $weeklyHoursDelta = round($weeklyHours - $lastWeeklyHours, 1);

        $weeklyChange = null;
        $weeklyTrend = 'neutral';
        if ($weeklyHoursDelta > 0) {
            $weeklyChange = '+'.number_format($weeklyHoursDelta, 1).' hrs';
            $weeklyTrend = 'up';
        } elseif ($weeklyHoursDelta < 0) {
            $weeklyChange = number_format($weeklyHoursDelta, 1).' hrs';
            $weeklyTrend = 'down';
        } elseif ($weeklyHours > 0 || $lastWeeklyHours > 0) {
            $weeklyChange = number_format($weeklyHoursDelta, 1).' hrs';
        }

        $progressPercent = $requiredHours > 0
            ? min((int) round(($totalHours / $requiredHours) * 100), 100)
            : 0;

        $daysLeft = (int) ceil($remainingHours / 8);

        return Inertia::render('Dashboard', [
            'dailyLogDates' => $dailyLogDates,
            'dailyLogsByDate' => $dailyLogsByDate,
            'dashboardStats' => [
                'totalHours' => round($totalHours, 1),
                'requiredHours' => round($requiredHours, 1),
                'progressPercent' => $progressPercent,
                'weeklyHours' => $weeklyHours,
                'weeklyChange' => $weeklyChange,
                'weeklyTrend' => $weeklyTrend,
                'daysLeft' => $daysLeft,
            ],
        ]);
    })->name('dashboard');
});

require __DIR__.'/settings.php';
