<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DailyLogController extends Controller
{
    public function upsert(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'log_date' => ['required', 'date'],
            'time_in' => ['nullable', 'date_format:H:i'],
            'time_out' => ['nullable', 'date_format:H:i', 'after:time_in'],
            'tasks_done' => ['nullable', 'string', 'max:5000'],
        ]);

        DailyLog::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'log_date' => $validated['log_date'],
            ],
            [
                'time_in' => $validated['time_in'] ?? null,
                'time_out' => $validated['time_out'] ?? null,
                'tasks_done' => $validated['tasks_done'] ?? null,
            ]
        );

        // Recompute total consumed hours from all logs:
        // (time_out - time_in) - 1 hour lunch per logged day.
        $totalHours = DailyLog::query()
            ->where('user_id', $request->user()->id)
            ->get()
            ->reduce(function (float $carry, DailyLog $log) {
                if (! $log->time_in || ! $log->time_out) {
                    return $carry;
                }

                $timeIn = Carbon::parse($log->time_in);
                $timeOut = Carbon::parse($log->time_out);
                $minutes = max($timeIn->diffInMinutes($timeOut, false), 0);
                $consumedMinutes = max($minutes - 60, 0); // deduct 1 hour lunch

                return $carry + ($consumedMinutes / 60);
            }, 0.0);

        $request->user()->forceFill([
            'hrs' => number_format($totalHours, 1, '.', ''),
        ])->save();

        return back();
    }
}
