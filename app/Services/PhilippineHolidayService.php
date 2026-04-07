<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PhilippineHolidayService
{
    public function getForYears(int $startYear, int $endYear): Collection
    {
        [$startYear, $endYear] = $this->normalizeYearRange($startYear, $endYear);

        if (! Schema::hasTable('holidays')) {
            return $this->buildHolidayCollection($startYear, $endYear);
        }

        $this->syncYears($startYear, $endYear);

        return Holiday::query()
            ->whereBetween('holiday_date', [
                CarbonImmutable::create($startYear, 1, 1)->toDateString(),
                CarbonImmutable::create($endYear, 12, 31)->toDateString(),
            ])
            ->orderBy('holiday_date')
            ->get()
            ->map(fn (Holiday $holiday) => [
                'date' => $holiday->holiday_date->toDateString(),
                'name' => $holiday->name,
                'type' => $holiday->type,
            ]);
    }

    public function syncYears(int $startYear, int $endYear): void
    {
        if (! Schema::hasTable('holidays')) {
            return;
        }

        [$startYear, $endYear] = $this->normalizeYearRange($startYear, $endYear);

        $rows = $this->buildHolidayCollection($startYear, $endYear)
            ->map(fn (array $holiday) => [
                'holiday_date' => $holiday['date'],
                'name' => $holiday['name'],
                'type' => $holiday['type'],
                'source' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        Holiday::query()->insertOrIgnore($rows);
    }

    private function normalizeYearRange(int $startYear, int $endYear): array
    {
        return [$startYear <= $endYear ? $startYear : $endYear, $startYear <= $endYear ? $endYear : $startYear];
    }

    private function buildHolidayCollection(int $startYear, int $endYear): Collection
    {
        return collect(range($startYear, $endYear))
            ->flatMap(fn (int $year) => $this->holidaysForYear($year))
            ->sortBy('date')
            ->values();
    }

    private function holidaysForYear(int $year): array
    {
        $easterSunday = $this->easterSunday($year);

        $holidays = [
            $this->makeHoliday($year, 1, 1, "New Year's Day", 'regular'),
            $this->makeHoliday($year, 2, 25, 'EDSA People Power Revolution Anniversary', 'special_non_working'),
            $this->makeHolidayDate($easterSunday->subDays(3), 'Maundy Thursday', 'regular'),
            $this->makeHolidayDate($easterSunday->subDays(2), 'Good Friday', 'regular'),
            $this->makeHoliday($year, 4, 9, 'Araw ng Kagitingan', 'regular'),
            $this->makeHoliday($year, 5, 1, 'Labor Day', 'regular'),
            $this->makeHoliday($year, 6, 12, 'Independence Day', 'regular'),
            $this->makeHoliday($year, 8, 21, 'Ninoy Aquino Day', 'special_non_working'),
            $this->makeHolidayDate($this->lastMondayOfAugust($year), "National Heroes Day", 'regular'),
            $this->makeHoliday($year, 11, 1, "All Saints' Day", 'special_non_working'),
            $this->makeHoliday($year, 11, 30, 'Bonifacio Day', 'regular'),
            $this->makeHoliday($year, 12, 8, 'Feast of the Immaculate Conception', 'special_non_working'),
            $this->makeHoliday($year, 12, 24, 'Christmas Eve', 'special_non_working'),
            $this->makeHoliday($year, 12, 25, 'Christmas Day', 'regular'),
            $this->makeHoliday($year, 12, 30, 'Rizal Day', 'regular'),
            $this->makeHoliday($year, 12, 31, 'Last Day of the Year', 'special_non_working'),
        ];

        return collect($holidays)
            ->unique('date')
            ->sortBy('date')
            ->values()
            ->all();
    }

    private function easterSunday(int $year): CarbonImmutable
    {
        return CarbonImmutable::create($year, 3, 21)->addDays(easter_days($year));
    }

    private function lastMondayOfAugust(int $year): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, 8, 31);

        return $date->isMonday() ? $date : $date->previous(CarbonImmutable::MONDAY);
    }

    private function makeHoliday(int $year, int $month, int $day, string $name, string $type): array
    {
        return $this->makeHolidayDate(CarbonImmutable::create($year, $month, $day), $name, $type);
    }

    private function makeHolidayDate(CarbonImmutable $date, string $name, string $type): array
    {
        return [
            'date' => $date->toDateString(),
            'name' => $name,
            'type' => $type,
        ];
    }
}
