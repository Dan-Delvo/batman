<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { Clock1, CalendarClock } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { DateValue } from 'reka-ui';
import { getLocalTimeZone, today } from '@internationalized/date';
import InputError from '@/components/InputError.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import { Calendar, CalendarCellTrigger } from '@/components/ui/calendar';
import { cn } from '@/lib/utils';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';
import { Label } from '@/components/ui/label';
import Marquee from '@/components/ui/Marquee.vue';
import MarqueeItem from '@/components/ui/MarqueeItem.vue';
import MarqueeSeparator from '@/components/ui/MarqueeSeparator.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

type DailyLogItem = { log_date?: string; date?: string };
type DailyLogDetails = {
    time_in: string | null;
    time_out: string | null;
    tasks_done: string | null;
};
type DailyLogPrefill = {
    time_in: string | null;
    time_out: string | null;
    source: 'default' | 'previous' | null;
};
type HolidayDetails = {
    name: string;
    type: 'regular' | 'special_non_working' | string;
};
type DashboardStats = {
    totalHours: number;
    requiredHours: number;
    progressPercent: number;
    weeklyHours: number;
    weeklyChange: string | null;
    weeklyTrend: 'up' | 'down' | 'neutral';
    daysLeft: number;
    expectedFinishDate: string | null;
};

const page = usePage<{
    dailyLogDates?: string[];
    dailyLogs?: DailyLogItem[];
    dailyLogsByDate?: Record<string, DailyLogDetails>;
    dailyLogPrefill?: DailyLogPrefill;
    holidaysByDate?: Record<string, HolidayDetails>;
    dashboardStats?: DashboardStats;
}>();

const selectedDate = ref<DateValue | undefined>();
const selectedDateIso = ref('');
const isDialogOpen = ref(false);

const loggedDateSet = computed(() => {
    const directDates = page.props.dailyLogDates ?? [];
    const fromLogs = (page.props.dailyLogs ?? [])
        .map((item) => item.log_date ?? item.date)
        .filter((value): value is string => Boolean(value));

    return new Set([...directDates, ...fromLogs]);
});

const localLoggedDates = ref<Record<string, boolean>>(
    Array.from(loggedDateSet.value).reduce<Record<string, boolean>>((acc, date) => {
        acc[date] = true;
        return acc;
    }, {}),
);

const localDailyLogsByDate = ref<Record<string, DailyLogDetails>>({
    ...(page.props.dailyLogsByDate ?? {}),
});
const holidaysByDate = computed<Record<string, HolidayDetails>>(() => page.props.holidaysByDate ?? {});
const dailyLogPrefill = computed<DailyLogPrefill>(() => {
    return (
        page.props.dailyLogPrefill ?? {
            time_in: null,
            time_out: null,
            source: null,
        }
    );
});

const logForm = useForm<{
    log_date: string;
    time_in: string;
    time_out: string;
    tasks_done: string;
    save_as_default: boolean;
}>({
    log_date: '',
    time_in: '',
    time_out: '',
    tasks_done: '',
    save_as_default: false,
});

const stats = computed<DashboardStats>(() => {
    return (
        page.props.dashboardStats ?? {
            totalHours: 0,
            requiredHours: 0,
            progressPercent: 0,
            weeklyHours: 0,
            weeklyChange: null,
            weeklyTrend: 'neutral',
            daysLeft: 0,
            expectedFinishDate: null,
        }
    );
});

const formatShortDate = (isoDate: string | null) => {
    if (!isoDate) return 'TBD';

    const date = new Date(`${isoDate}T00:00:00`);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
};

const toIsoDate = (date: DateValue) => {
    const month = String(date.month).padStart(2, '0');
    const day = String(date.day).padStart(2, '0');
    return `${date.year}-${month}-${day}`;
};

const formatLongDate = (isoDate: string) => {
    if (!isoDate) return '';
    const date = new Date(`${isoDate}T00:00:00`);
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(date);
};

const isLoggedDate = (day: DateValue) => Boolean(localLoggedDates.value[toIsoDate(day)]);
const getHoliday = (day: DateValue) => holidaysByDate.value[toIsoDate(day)];
const selectedHoliday = computed(() => holidaysByDate.value[selectedDateIso.value] ?? null);
const getHolidayChip = (name: string) =>
    name
        .split(/\s+/)
        .map((part) => part[0])
        .join('')
        .slice(0, 3)
        .toUpperCase();

const isDateUnavailable = (day: DateValue) => {
    const dayOfWeek = new Date(day.year, day.month - 1, day.day).getDay();
    const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
    const isFuture = day.compare(today(getLocalTimeZone())) > 0;

    return isWeekend || isFuture;
};

watch(selectedDate, (value) => {
    if (!value) return;

    selectedDateIso.value = toIsoDate(value);
    const existingLog = localDailyLogsByDate.value[selectedDateIso.value];

    logForm.log_date = selectedDateIso.value;
    logForm.time_in = existingLog?.time_in ?? dailyLogPrefill.value.time_in ?? '';
    logForm.time_out = existingLog?.time_out ?? dailyLogPrefill.value.time_out ?? '';
    logForm.tasks_done = existingLog?.tasks_done ?? '';
    logForm.save_as_default = false;
    logForm.clearErrors();
    isDialogOpen.value = true;
});

const saveDailyLog = () => {
    if (!selectedDateIso.value) return;

    const isoDate = selectedDateIso.value;
    const previousLog = localDailyLogsByDate.value[isoDate]
        ? { ...localDailyLogsByDate.value[isoDate] }
        : null;
    const previousMarked = Boolean(localLoggedDates.value[isoDate]);

    const optimisticLog: DailyLogDetails = {
        time_in: logForm.time_in || null,
        time_out: logForm.time_out || null,
        tasks_done: logForm.tasks_done || null,
    };

    localDailyLogsByDate.value = {
        ...localDailyLogsByDate.value,
        [isoDate]: optimisticLog,
    };

    localLoggedDates.value = {
        ...localLoggedDates.value,
        [isoDate]: true,
    };

    isDialogOpen.value = false;

    logForm.post('/daily-logs', {
        preserveScroll: true,
        onError: () => {
            if (previousLog) {
                localDailyLogsByDate.value = {
                    ...localDailyLogsByDate.value,
                    [isoDate]: previousLog,
                };
            } else {
                const nextLogs = { ...localDailyLogsByDate.value };
                delete nextLogs[isoDate];
                localDailyLogsByDate.value = nextLogs;
            }

            if (!previousMarked) {
                const nextMarks = { ...localLoggedDates.value };
                delete nextMarks[isoDate];
                localLoggedDates.value = nextMarks;
            }

            isDialogOpen.value = true;
        },
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div>
                <Marquee :bordered="false" class="bg-primary text-primary-foreground">
                    <MarqueeItem>DAYS LEFT: {{stats.daysLeft}}</MarqueeItem>
                    <MarqueeSeparator class="text-primary-foreground/50">★</MarqueeSeparator>
                    <MarqueeItem>DAYS LEFT: {{stats.daysLeft}}</MarqueeItem>
                    <MarqueeSeparator class="text-primary-foreground/50">★</MarqueeSeparator>
                </Marquee>
        </div>
        <div class="grid auto-rows-min grid-cols-1 gap-4 md:grid-cols-6">

            <div class="relative col-span-1 flex md:col-span-4">
                <StatCard
                    title="Target Hours"
                    :value="`${stats.totalHours}/${stats.requiredHours}`"
                    :icon="Clock1"
                    color="success"
                    variant="large"
                    :progress="{ value: stats.progressPercent, label: 'Target' }"
                    class="h-full w-full"
                />
            </div>
            <div class="relative col-span-1 flex md:col-span-1">
                <StatCard
                    title="This week"
                    :value="`${stats.weeklyHours} hrs`"
                    :change="stats.weeklyChange ?? undefined"
                    :trend="stats.weeklyTrend"
                    :icon="CalendarClock"
                    color="primary"
                    class="h-full w-full"
                />
            </div>
            <div class="relative col-span-1 flex md:col-span-1">
                <StatCard
                    title="Expected finish"
                    :value="formatShortDate(stats.expectedFinishDate)"
                    :icon="CalendarClock"
                    color="warning"
                    class="h-full w-full"
                />
            </div>
        </div>
        <div class="relative">
            <Card class="w-full">
                <CardHeader>
                    <CardTitle>Daily Log Calendar</CardTitle>
                </CardHeader>
                <CardContent class="p-4 md:p-6">
                <Calendar
                    v-model="selectedDate"
                    class="w-full p-0 [&_[data-slot=calendar-grid]]:border-3 [&_[data-slot=calendar-grid]]:border-foreground [&_[data-slot=calendar-grid]]:shadow-[4px_4px_0px_hsl(var(--shadow-color))] [&_[data-slot=calendar-grid-row]]:mt-0 [&_[data-slot=calendar-grid-head]]:bg-muted/40 [&_[data-slot=calendar-head-cell]]:h-10 [&_[data-slot=calendar-head-cell]]:text-center [&_[data-slot=calendar-head-cell]]:text-xs [&_[data-slot=calendar-head-cell]]:font-bold [&_[data-slot=calendar-head-cell]]:uppercase [&_[data-slot=calendar-head-cell]]:tracking-wide [&_[data-slot=calendar-head-cell]]:border-b-2 [&_[data-slot=calendar-head-cell]]:border-foreground [&_[data-slot=calendar-cell]]:h-14 [&_[data-slot=calendar-cell]]:rounded-none [&_[data-slot=calendar-cell]]:border-r-2 [&_[data-slot=calendar-cell]]:border-b-2 [&_[data-slot=calendar-cell]]:border-foreground [&_[data-slot=calendar-cell]]:bg-background [&_[data-slot=calendar-grid-row]>:last-child]:border-r-0"
                    layout="month-and-year"
                    :weekday-format="'short'"
                    :fixed-weeks="true"
                    :is-date-unavailable="isDateUnavailable"
                >
                    <template #calendar-cell="{ day, month }">
                        <CalendarCellTrigger
                            :day="day"
                            :month="month"
                            :title="getHoliday(day)?.name"
                            :class="
                                cn(
                                    'flex h-full w-full flex-col items-start justify-start rounded-none border-0 bg-transparent px-2 py-1 font-bold text-foreground shadow-none transition-colors duration-150 hover:bg-accent/40 hover:translate-x-0 hover:translate-y-0',
                                    'data-[outside-view]:text-muted-foreground data-[outside-view]:opacity-70',
                                    'data-[today]:bg-accent data-[today]:text-accent-foreground',
                                    'data-[selected]:bg-primary data-[selected]:text-primary-foreground',
                                    'data-[unavailable]:pointer-events-none data-[unavailable]:opacity-45 data-[unavailable]:bg-muted/50 data-[unavailable]:text-muted-foreground',
                                    isLoggedDate(day) && 'bg-success/25 text-success-foreground',
                                    getHoliday(day)?.type === 'regular' &&
                                        'bg-amber-200/60 text-amber-950 hover:bg-amber-200',
                                    getHoliday(day)?.type !== 'regular' &&
                                        getHoliday(day) &&
                                        'bg-sky-200/60 text-sky-950 hover:bg-sky-200',
                                    isLoggedDate(day) && getHoliday(day) && 'ring-2 ring-foreground/70 ring-inset'
                                )
                            "
                        >
                            <span class="text-sm leading-none">{{ day.day }}</span>
                            <span
                                v-if="getHoliday(day)"
                                class="mt-1 line-clamp-2 text-[10px] font-semibold uppercase leading-tight opacity-85"
                            >
                                {{ getHolidayChip(getHoliday(day)?.name ?? '') }}
                            </span>
                        </CalendarCellTrigger>
                    </template>
                </Calendar>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-xl border-3 border-foreground shadow-[8px_8px_0px_hsl(var(--shadow-color))]">
                <DialogHeader>
                    <DialogTitle class="text-xl font-bold uppercase tracking-wide">
                        {{ formatLongDate(selectedDateIso) }}
                    </DialogTitle>
                    <DialogDescription>
                        <span v-if="selectedHoliday">
                            {{ selectedHoliday.name }} • {{ selectedHoliday.type === 'regular' ? 'Regular holiday' : 'Special non-working holiday' }}
                        </span>
                        <span v-else>
                            Fill out your daily internship log.
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="saveDailyLog">
                    <input type="hidden" name="log_date" :value="selectedDateIso" />

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="time_in">Time In</Label>
                            <Input id="time_in" v-model="logForm.time_in" type="time" />
                            <InputError :message="logForm.errors.time_in" />
                        </div>

                        <div class="space-y-2">
                            <Label for="time_out">Time Out</Label>
                            <Input id="time_out" v-model="logForm.time_out" type="time" />
                            <InputError :message="logForm.errors.time_out" />
                        </div>
                    </div>

                    <p v-if="!localDailyLogsByDate[selectedDateIso] && dailyLogPrefill.source" class="text-xs text-muted-foreground">
                        {{ dailyLogPrefill.source === 'default' ? 'Autofilled from your default schedule.' : 'Autofilled from your previous log.' }}
                        You can still edit before saving.
                    </p>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="logForm.save_as_default"
                            type="checkbox"
                            class="h-4 w-4 rounded border-input"
                        />
                        Save current time in and out as my default
                    </label>
                    <InputError :message="logForm.errors.save_as_default" />

                    <div class="space-y-2">
                        <Label for="tasks_done">What did you do today?</Label>
                        <textarea
                            id="tasks_done"
                            v-model="logForm.tasks_done"
                            rows="5"
                            class="flex w-full border-3 border-input bg-background px-4 py-3 text-sm shadow-[4px_4px_0px_hsl(var(--shadow-color))] transition-all duration-200 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:translate-x-[4px] focus-visible:translate-y-[4px] focus-visible:shadow-none"
                            placeholder="Describe your tasks, progress, blockers, and output..."
                        />
                        <InputError :message="logForm.errors.tasks_done" />
                    </div>
                </form>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="isDialogOpen = false">
                        Close
                    </Button>
                    <Button type="button" :disabled="logForm.processing" @click="saveDailyLog">
                        {{ logForm.processing ? 'Saving...' : 'Save Log' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
