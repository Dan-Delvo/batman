<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Table from '@/components/ui/Table.vue';
import TableBody from '@/components/ui/TableBody.vue';
import TableCell from '@/components/ui/TableCell.vue';
import TableHead from '@/components/ui/TableHead.vue';
import TableHeader from '@/components/ui/TableHeader.vue';
import TableRow from '@/components/ui/TableRow.vue';

type WeekDayStatus = {
    date: string;
    label: string;
    is_complete: boolean;
};

type WeekStatus = {
    week_start: string;
    week_end: string;
    completed_weekdays: number;
    required_weekdays: number;
    is_complete: boolean;
    days: WeekDayStatus[];
};

const page = usePage<{
    weeks?: WeekStatus[];
}>();

const weeks = page.props.weeks ?? [];
const isExportDialogOpen = ref(false);
const selectedWeek = ref<WeekStatus | null>(null);
const exportFormat = ref<'docx' | 'pdf'>('docx');

const formatDate = (isoDate: string) => {
    const date = new Date(`${isoDate}T00:00:00`);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
};

const openExportDialog = (week: WeekStatus) => {
    selectedWeek.value = week;
    exportFormat.value = 'docx';
    isExportDialogOpen.value = true;
};

const exportReport = () => {
    if (!selectedWeek.value) return;

    const params = new URLSearchParams({
        week_start: selectedWeek.value.week_start,
        format: exportFormat.value,
    });

    window.open(`/weekly-reports/export?${params.toString()}`, '_blank');
    isExportDialogOpen.value = false;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Weekly Reports',
                href: '/weekly-reports',
            },
        ],
    },
});
</script>

<template>
    <Head title="Weekly Reports" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <Card>
            <CardHeader>
                <CardTitle>Weekly Report Generation</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    A week is ready when all weekdays (Monday to Friday) have complete logs.
                </p>

                <div v-if="weeks.length === 0" class="rounded border-2 border-dashed border-input p-6 text-sm text-muted-foreground">
                    No weekly logs found yet.
                </div>

                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Week</TableHead>
                            <TableHead>Weekdays</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="week in weeks" :key="week.week_start">
                            <TableCell class="font-medium">
                                {{ formatDate(week.week_start) }} - {{ formatDate(week.week_end) }}
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="day in week.days"
                                        :key="day.date"
                                        :variant="day.is_complete ? 'success' : 'outline'"
                                        class="min-w-14 justify-center"
                                    >
                                        {{ day.label }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Badge :variant="week.is_complete ? 'success' : 'warning'">
                                        {{ week.is_complete ? 'Ready' : 'Incomplete' }}
                                    </Badge>
                                    <span class="text-xs text-muted-foreground">
                                        {{ week.completed_weekdays }}/{{ week.required_weekdays }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button
                                    type="button"
                                    size="sm"
                                    :variant="week.is_complete ? 'default' : 'outline'"
                                    :disabled="!week.is_complete"
                                    @click="openExportDialog(week)"
                                >
                                    Generate Report
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <Dialog v-model:open="isExportDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Export Weekly Report</DialogTitle>
                    <DialogDescription>
                        Choose a format for
                        <span v-if="selectedWeek">
                            {{ formatDate(selectedWeek.week_start) }} - {{ formatDate(selectedWeek.week_end) }}.
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3">
                    <label class="flex cursor-pointer items-center gap-2 rounded border-2 border-input px-3 py-2 text-sm">
                        <input v-model="exportFormat" type="radio" value="docx" class="h-4 w-4 border-input" />
                        Export as DOCX
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded border-2 border-input px-3 py-2 text-sm">
                        <input v-model="exportFormat" type="radio" value="pdf" class="h-4 w-4 border-input" />
                        Export as PDF
                    </label>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="isExportDialogOpen = false">
                        Cancel
                    </Button>
                    <Button type="button" @click="exportReport">
                        Download
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
