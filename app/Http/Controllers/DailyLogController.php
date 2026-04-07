<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DailyLogController extends Controller
{
    public function upsert(Request $request): RedirectResponse
    {
        $request->merge([
            'time_in' => $this->normalizeTimeValue($request->input('time_in')),
            'time_out' => $this->normalizeTimeValue($request->input('time_out')),
        ]);

        $validated = $request->validate([
            'log_date' => ['required', 'date'],
            'time_in' => ['nullable', 'date_format:H:i'],
            'time_out' => ['nullable', 'date_format:H:i', 'after:time_in'],
            'tasks_done' => ['nullable', 'string', 'max:5000'],
            'save_as_default' => ['nullable', 'boolean'],
        ]);

        $timeIn = $validated['time_in'] ?? null;
        $timeOut = $validated['time_out'] ?? null;

        DailyLog::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'log_date' => $validated['log_date'],
            ],
            [
                'time_in' => $timeIn,
                'time_out' => $timeOut,
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

        $userUpdates = [
            'hrs' => number_format($totalHours, 1, '.', ''),
        ];

        if (($validated['save_as_default'] ?? false) === true
            && Schema::hasColumns('users', ['default_time_in', 'default_time_out'])) {
            $userUpdates['default_time_in'] = $timeIn;
            $userUpdates['default_time_out'] = $timeOut;
        }

        $request->user()->forceFill($userUpdates)->save();

        return back();
    }

    private function normalizeTimeValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['H:i', 'H:i:s', 'g:i a', 'g:i A', 'h:i a', 'h:i A'] as $format) {
            try {
                $time = Carbon::createFromFormat($format, $value);
                if ($time !== false) {
                    return $time->format('H:i');
                }
            } catch (\Throwable) {
                // Keep trying other accepted formats.
            }
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            // Return original input so validator can report a clean validation error.
        }

        return $value;
    }
}
