<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WeeklyReportController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasCompletedOnboarding()) {
            return redirect()->route('onboarding.show');
        }

        return Inertia::render('WeeklyReports', [
            'weeks' => $this->buildWeeklyStatuses($user),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'week_start' => ['required', 'date'],
            'format' => ['required', 'in:docx,pdf'],
        ]);

        $user = $request->user();
        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekData = $this->buildWeekData($user, $weekStart);

        if (! $weekData['is_complete']) {
            abort(422, 'Weekly report is only available for complete weeks.');
        }

        $reportData = $this->buildReportTemplateData($user, $weekData['days']);
        $baseFileName = 'weekly-report-'.$weekStart;
        $templatePath = $this->resolveDocxTemplatePath();
        $tmpDir = storage_path('app/tmp');

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $docxPath = $tmpDir.'/'.$baseFileName.'.docx';
        $processor = new TemplateProcessor($templatePath);
        $processor->setMacroChars('{{', '}}');
        $this->applyTemplateValues($processor, $reportData);
        $processor->saveAs($docxPath);

        if ($validated['format'] === 'docx') {
            return response()->download($docxPath, $baseFileName.'.docx')->deleteFileAfterSend(true);
        }

        $pdfPath = $tmpDir.'/'.$baseFileName.'.pdf';
        $converted = $this->convertDocxToPdf($docxPath, $pdfPath);

        if (! $converted || ! file_exists($pdfPath)) {
            // Fallback instead of hard 500: user still gets a usable report.
            return response()->download($docxPath, $baseFileName.'.docx')->deleteFileAfterSend(true);
        }

        @unlink($docxPath);

        return response()->download($pdfPath, $baseFileName.'.pdf')->deleteFileAfterSend(true);
    }

    private function resolveDocxTemplatePath(): string
    {
        $templatePath = public_path('template/template.docx');
        if (! file_exists($templatePath)) {
            $templatePath = public_path('templates/template.docx');
        }

        if (! file_exists($templatePath)) {
            abort(404, 'Template file not found.');
        }

        return $templatePath;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildWeeklyStatuses(User $user): array
    {
        if (! Schema::hasTable('daily_logs')) {
            return [];
        }

        $logs = DailyLog::query()
            ->where('user_id', $user->id)
            ->get();

        $logsByDate = $logs
            ->mapWithKeys(fn (DailyLog $log) => [
                Carbon::parse($log->log_date)->toDateString() => $log,
            ]);

        $weekStarts = $logs
            ->pluck('log_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString())
            ->unique()
            ->sort()
            ->values();

        return $weekStarts
            ->map(fn (string $weekStartIso) => $this->buildWeekData($user, $weekStartIso, $logsByDate))
            ->sortByDesc('week_start')
            ->values()
            ->all();
    }

    /**
     * @param Collection<string, DailyLog>|null $logsByDate
     * @return array<string, mixed>
     */
    private function buildWeekData(User $user, string $weekStartIso, ?Collection $logsByDate = null): array
    {
        $weekStart = Carbon::parse($weekStartIso)->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        if ($logsByDate === null) {
            $logsByDate = DailyLog::query()
                ->where('user_id', $user->id)
                ->whereBetween('log_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->get()
                ->mapWithKeys(fn (DailyLog $log) => [
                    Carbon::parse($log->log_date)->toDateString() => $log,
                ]);
        }

        $days = collect(range(0, 4))->map(function (int $dayOffset) use ($weekStart, $logsByDate) {
            $date = $weekStart->copy()->addDays($dayOffset)->toDateString();
            /** @var DailyLog|null $log */
            $log = $logsByDate->get($date);
            $tasksDone = trim((string) ($log?->tasks_done ?? ''));
            $isAbsent = preg_match('/^absent(?:\s*:.*)?$/i', $tasksDone) === 1;
            $hasTasks = $tasksDone !== '';
            $hasTimeRange = (bool) ($log?->time_in && $log?->time_out);
            $isComplete = $log !== null && ($isAbsent ? $hasTasks : ($hasTasks && $hasTimeRange));

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->format('D'),
                'is_complete' => $isComplete,
            ];
        });

        $completedWeekdays = $days->where('is_complete', true)->count();
        $requiredWeekdays = 5;

        return [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'completed_weekdays' => $completedWeekdays,
            'required_weekdays' => $requiredWeekdays,
            'is_complete' => $completedWeekdays === $requiredWeekdays,
            'days' => $days->values()->all(),
        ];
    }

    /**
     * @param array<int, array{date: string, label: string, is_complete: bool}> $days
     * @return array<string, string>
     */
    private function buildReportTemplateData(User $user, array $days): array
    {
        $logs = DailyLog::query()
            ->where('user_id', $user->id)
            ->whereIn('log_date', collect($days)->pluck('date')->all())
            ->get()
            ->mapWithKeys(fn (DailyLog $log) => [
                Carbon::parse($log->log_date)->toDateString() => $log,
            ]);

        $dayKeys = ['mon', 'tue', 'wed', 'thur', 'fri'];
        $result = [
            'name' => (string) $user->name,
            'supervisor_name' => (string) ($user->supervisor ?? ''),
        ];

        foreach ($dayKeys as $index => $key) {
            $date = $days[$index]['date'] ?? null;
            /** @var DailyLog|null $log */
            $log = $date ? $logs->get($date) : null;
            $tasks = trim((string) ($log?->tasks_done ?? ''));
            $isAbsent = preg_match('/^absent(?:\s*:.*)?$/i', $tasks) === 1;

            $result['date_'.$key] = $date ? Carbon::parse($date)->format('F j, Y') : '';
            $result['time_in_'.$key] = $isAbsent ? '' : $this->formatTime($log?->time_in);
            $result['time_out_'.$key] = $isAbsent ? '' : $this->formatTime($log?->time_out);
            $result['tasks_'.$key] = $tasks;
        }

        return $result;
    }

    private function formatTime(?string $value): string
    {
        if (! $value) {
            return '';
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                $time = Carbon::createFromFormat($format, $value);
                if ($time !== false) {
                    return $time->format('h:i A');
                }
            } catch (\Throwable) {
                // Try other formats.
            }
        }

        try {
            return Carbon::parse($value)->format('h:i A');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function convertDocxToPdf(string $docxPath, string $pdfPath): bool
    {
        return $this->convertWithLibreOffice($docxPath, $pdfPath)
            || $this->convertWithWordCom($docxPath, $pdfPath);
    }

    private function convertWithLibreOffice(string $docxPath, string $pdfPath): bool
    {
        if (! function_exists('shell_exec') || ! function_exists('exec')) {
            return false;
        }

        $sofficePath = trim((string) @shell_exec('where soffice 2>NUL'));
        if ($sofficePath === '') {
            return false;
        }

        $outDir = dirname($pdfPath);
        $command = sprintf(
            '"%s" --headless --convert-to pdf --outdir "%s" "%s"',
            str_replace('"', '\"', $sofficePath),
            str_replace('"', '\"', $outDir),
            str_replace('"', '\"', $docxPath),
        );

        $output = [];
        $exitCode = 1;
        try {
            @exec($command, $output, $exitCode);
        } catch (\Throwable) {
            return false;
        }

        $generatedPdf = $outDir.DIRECTORY_SEPARATOR.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
        if ($exitCode !== 0 || ! file_exists($generatedPdf)) {
            return false;
        }

        if (realpath($generatedPdf) !== realpath($pdfPath)) {
            @rename($generatedPdf, $pdfPath);
        }

        return file_exists($pdfPath);
    }

    private function convertWithWordCom(string $docxPath, string $pdfPath): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $scriptPath = storage_path('app/tmp/convert-docx-to-pdf.ps1');
        if (! file_exists($scriptPath)) {
            $script = <<<'POWERSHELL'
param(
    [Parameter(Mandatory = $true)][string]$DocxPath,
    [Parameter(Mandatory = $true)][string]$PdfPath
)

$word = $null
$doc = $null

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Open($DocxPath)
    $wdFormatPDF = 17
    $doc.SaveAs([ref]$PdfPath, [ref]$wdFormatPDF)
    exit 0
} catch {
    exit 1
} finally {
    if ($doc -ne $null) {
        $doc.Close()
    }
    if ($word -ne $null) {
        $word.Quit()
    }
}
POWERSHELL;
            @file_put_contents($scriptPath, $script);
        }

        $command = sprintf(
            'powershell -NoProfile -ExecutionPolicy Bypass -File %s -DocxPath %s -PdfPath %s',
            escapeshellarg($scriptPath),
            escapeshellarg($docxPath),
            escapeshellarg($pdfPath),
        );

        $output = [];
        $exitCode = 1;
        try {
            @exec($command, $output, $exitCode);
        } catch (\Throwable) {
            return false;
        }

        return $exitCode === 0 && file_exists($pdfPath);
    }

    /**
     * @param array<string, string> $data
     */
    private function applyTemplateValues(TemplateProcessor $processor, array $data): void
    {
        foreach ($data as $key => $value) {
            // Support both `{{name}}` and `{{ name }}` styles in templates.
            $processor->setValue($key, $value);
            $processor->setValue(' '.$key.' ', $value);
        }
    }
}
