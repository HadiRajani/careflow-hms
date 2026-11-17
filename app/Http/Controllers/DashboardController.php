<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Services\TriageScorer;
use Illuminate\Http\Request;

/**
 * Quality & operations dashboard for the Quality Manager.
 * Every number is computed from data the app captures (visits + triages).
 *
 * Simple approach: load the period's visits and calculate in PHP.
 * Fine for one hospital (~1,000 visits/month). At larger scale these would be
 * SQL aggregates or a nightly summary table (see ROADMAP).
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Period: last 7, 14 or 30 days (default 30).
        $days = in_array((int) $request->query('days'), [7, 14, 30]) ? (int) $request->query('days') : 30;
        $from = today()->subDays($days - 1);   // includes today

        $visits = Visit::with('triage')->where('arrived_at', '>=', $from)->get();
        $triaged = $visits->filter(fn ($v) => $v->triage !== null);

        // Minutes each patient waited from arrival to triage.
        foreach ($triaged as $visit) {
            $visit->wait_minutes = (int) round($visit->arrived_at->diffInMinutes($visit->triage->recorded_at, true));
        }

        // ---- Metric 1: wait from arrival to triage ----
        $avgWait = (int) round($triaged->avg('wait_minutes') ?? 0);
        $within30 = $this->percent($triaged->where('wait_minutes', '<=', 30)->count(), $triaged->count());

        // ---- Metric 2: abnormal-flag rate (medium or high risk) ----
        $abnormal = $triaged->filter(fn ($v) => $v->triage->risk_level !== 'low');
        $abnormalRate = $this->percent($abnormal->count(), $triaged->count());
        $highRiskCount = $triaged->filter(fn ($v) => $v->triage->risk_level === 'high')->count();

        // ---- Metric 3: patients returning within 7 days ----
        $returnRate = $this->percent($this->countReturnVisits($from), $visits->count());

        // ---- Metric 4: visits never triaged (still "waiting" from a previous day) ----
        $leftWithoutTriage = $visits->filter(fn ($v) => $v->status === 'waiting' && ! $v->arrived_at->isToday())->count();

        // ---- Chart: by hour of day (OPD hours 08:00-17:00) ----
        $byHour = [];
        for ($hour = 8; $hour <= 17; $hour++) {
            $inHour = $triaged->filter(fn ($v) => (int) $v->arrived_at->format('G') === $hour);
            $byHour[$hour] = [
                'visits' => $visits->filter(fn ($v) => (int) $v->arrived_at->format('G') === $hour)->count(),
                'avg_wait' => (int) round($inHour->avg('wait_minutes') ?? 0),
            ];
        }

        // ---- Chart: abnormal rate by day ----
        $byDay = [];
        for ($date = $from->copy(); $date->lte(today()); $date->addDay()) {
            $dayTriaged = $triaged->filter(fn ($v) => $v->arrived_at->isSameDay($date));
            $dayAbnormal = $dayTriaged->filter(fn ($v) => $v->triage->risk_level !== 'low')->count();
            $byDay[] = [
                'date' => $date->copy(),
                'triaged' => $dayTriaged->count(),
                'abnormal' => $dayAbnormal,
                'rate' => $this->percent($dayAbnormal, $dayTriaged->count()),
            ];
        }

        // ---- Most common abnormal findings ----
        $flagCounts = $triaged->flatMap(fn ($v) => $v->triage->flags)->countBy()->sortDesc()->take(6);

        $insights = $this->insights($avgWait, $within30, $byHour, $byDay, $returnRate, $flagCounts, $leftWithoutTriage);

        return view('dashboard', compact(
            'days', 'visits', 'triaged', 'avgWait', 'within30', 'abnormalRate', 'highRiskCount',
            'returnRate', 'leftWithoutTriage', 'byHour', 'byDay', 'flagCounts', 'insights'
        ));
    }

    /**
     * A "return" = a visit by a patient who already had another visit in the 7 days before it.
     */
    private function countReturnVisits($from): int
    {
        // Also load the 7 days BEFORE the period, so early visits can be compared too.
        $all = Visit::where('arrived_at', '>=', $from->copy()->subDays(7))
            ->orderBy('arrived_at')
            ->get(['id', 'patient_id', 'arrived_at'])
            ->groupBy('patient_id');

        $returns = 0;
        foreach ($all as $patientVisits) {
            for ($i = 1; $i < $patientVisits->count(); $i++) {
                $current = $patientVisits[$i];
                $previous = $patientVisits[$i - 1];
                $isInPeriod = $current->arrived_at->gte($from);
                $withinSevenDays = $previous->arrived_at->diffInDays($current->arrived_at, true) <= 7;

                if ($isInPeriod && $withinSevenDays) {
                    $returns++;
                }
            }
        }

        return $returns;
    }

    /**
     * Plain-language insights, written by simple rules (no AI, no external service).
     */
    private function insights($avgWait, $within30, $byHour, $byDay, $returnRate, $flagCounts, $leftWithoutTriage): array
    {
        $insights = [];

        // 1. Busiest wait hour
        $busy = collect($byHour)->filter(fn ($h) => $h['visits'] >= 5)->sortByDesc('avg_wait');
        $peakHour = $busy->keys()->first();
        if ($peakHour !== null && $byHour[$peakHour]['avg_wait'] > $avgWait * 1.3) {
            $insights[] = sprintf(
                'Patients arriving around %02d:00 wait longest for triage (%d min on average vs %d min overall). Consider adding a second triage nurse between %02d:00 and %02d:00.',
                $peakHour, $byHour[$peakHour]['avg_wait'], $avgWait, $peakHour - 1, $peakHour + 1
            );
        }

        // 2. Triage within 30 minutes
        if ($within30 < 80) {
            $insights[] = "Only {$within30}% of patients were triaged within 30 minutes of arrival.";
        } else {
            $insights[] = "{$within30}% of patients were triaged within 30 minutes of arrival — on target.";
        }

        // 3. Abnormal-rate trend: last 7 days vs the 7 days before
        $recent = collect(array_slice($byDay, -7));
        $earlier = collect(array_slice($byDay, -14, 7));
        if ($earlier->sum('triaged') > 0 && $recent->sum('triaged') > 0) {
            $recentRate = $this->percent($recent->sum('abnormal'), $recent->sum('triaged'));
            $earlierRate = $this->percent($earlier->sum('abnormal'), $earlier->sum('triaged'));
            if (abs($recentRate - $earlierRate) >= 5) {
                $direction = $recentRate > $earlierRate ? 'rose' : 'fell';
                $insights[] = "The share of patients with abnormal vital signs {$direction} from {$earlierRate}% to {$recentRate}% in the last 7 days.";
            } else {
                $insights[] = "The share of patients with abnormal vital signs is stable at about {$recentRate}%.";
            }
        }

        // 4. Most common finding
        if ($flagCounts->isNotEmpty()) {
            $topFlag = $flagCounts->keys()->first();
            $insights[] = TriageScorer::flagLabel($topFlag) . " was the most common abnormal finding ({$flagCounts->first()} patients).";
        }

        // 5. Returns within 7 days
        if ($returnRate >= 5) {
            $insights[] = "{$returnRate}% of visits were patients returning within 7 days. Review these for missed diagnoses or unclear follow-up advice.";
        }

        // 6. Never triaged
        if ($leftWithoutTriage > 0) {
            $insights[] = "{$leftWithoutTriage} visits from previous days were never triaged — patients may have left without being seen.";
        }

        return $insights;
    }

    private function percent(int $part, int $whole): int
    {
        return $whole > 0 ? (int) round($part * 100 / $whole) : 0;
    }
}
