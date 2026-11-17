<?php

namespace App\Services;

/**
 * Scores triage vital signs and flags risky readings.
 *
 * SOURCE OF THRESHOLDS
 *   NEWS2 (National Early Warning Score 2), Royal College of Physicians, London, 2017.
 *   https://www.rcp.ac.uk/improving-care/resources/national-early-warning-score-news-2/
 *   Each vital sign gets 0-3 points; the points are added up.
 *
 *   Extra flag (not part of NEWS2): hypertensive crisis = systolic >= 180 or diastolic >= 120
 *   (2017 ACC/AHA blood pressure guideline). Important in an OPD where hypertension is common.
 *
 * RISK LEVEL (RCP clinical response thresholds)
 *   high    total score 7 or more
 *   medium  total score 5-6, OR any single vital sign scoring 3
 *   low     everything else
 *
 * LIMITATIONS (documented in README)
 *   - NEWS2 is validated for adults (16+), not children or pregnancy.
 *   - We assume the patient is breathing room air (no supplemental oxygen points)
 *     and use SpO2 scale 1 (not the scale for patients with chronic lung disease).
 *   - This supports clinical judgement; it never replaces it.
 */
class TriageScorer
{
    /**
     * $vitals keys: respiratory_rate, spo2, systolic_bp, diastolic_bp, pulse, temperature, consciousness
     * Returns: ['score' => 6, 'risk_level' => 'medium', 'flags' => ['spo2_low', 'pulse_high']]
     */
    public function score(array $vitals): array
    {
        $points = [
            'respiratory_rate' => $this->respiratoryRatePoints($vitals['respiratory_rate']),
            'spo2' => $this->spo2Points($vitals['spo2']),
            'systolic_bp' => $this->systolicPoints($vitals['systolic_bp']),
            'pulse' => $this->pulsePoints($vitals['pulse']),
            'consciousness' => $vitals['consciousness'] === 'A' ? 0 : 3,   // anything other than Alert = 3
            'temperature' => $this->temperaturePoints($vitals['temperature']),
        ];

        $total = array_sum($points);

        // Risk level
        if ($total >= 7) {
            $risk = 'high';
        } elseif ($total >= 5 || max($points) === 3) {
            $risk = 'medium';
        } else {
            $risk = 'low';
        }

        // Flags: one code per abnormal reading. Codes, not sentences, so they work in English and Urdu.
        $flags = [];
        if ($points['respiratory_rate'] > 0) $flags[] = $vitals['respiratory_rate'] <= 11 ? 'rr_low' : 'rr_high';
        if ($points['spo2'] > 0)             $flags[] = 'spo2_low';
        if ($points['systolic_bp'] > 0)      $flags[] = $vitals['systolic_bp'] <= 110 ? 'bp_low' : 'bp_high';
        if ($points['pulse'] > 0)            $flags[] = $vitals['pulse'] <= 50 ? 'pulse_low' : 'pulse_high';
        if ($points['consciousness'] > 0)    $flags[] = 'consciousness_altered';
        if ($points['temperature'] > 0)      $flags[] = $vitals['temperature'] <= 36.0 ? 'temp_low' : 'temp_high';

        if ($vitals['systolic_bp'] >= 180 || $vitals['diastolic_bp'] >= 120) {
            $flags[] = 'hypertensive_crisis';
            if ($risk === 'low') {
                $risk = 'medium';   // needs prompt review even if NEWS2 is low
            }
        }

        return ['score' => $total, 'risk_level' => $risk, 'flags' => $flags];
    }

    // ---- NEWS2 chart, one function per vital sign ----

    public function respiratoryRatePoints(int $rr): int
    {
        if ($rr <= 8) return 3;
        if ($rr <= 11) return 1;
        if ($rr <= 20) return 0;
        if ($rr <= 24) return 2;
        return 3;                   // 25 or more
    }

    public function spo2Points(int $spo2): int
    {
        if ($spo2 <= 91) return 3;
        if ($spo2 <= 93) return 2;
        if ($spo2 <= 95) return 1;
        return 0;                   // 96 or more
    }

    public function systolicPoints(int $systolic): int
    {
        if ($systolic <= 90) return 3;
        if ($systolic <= 100) return 2;
        if ($systolic <= 110) return 1;
        if ($systolic <= 219) return 0;
        return 3;                   // 220 or more
    }

    public function pulsePoints(int $pulse): int
    {
        if ($pulse <= 40) return 3;
        if ($pulse <= 50) return 1;
        if ($pulse <= 90) return 0;
        if ($pulse <= 110) return 1;
        if ($pulse <= 130) return 2;
        return 3;                   // 131 or more
    }

    public function temperaturePoints(float $temp): int
    {
        if ($temp <= 35.0) return 3;
        if ($temp <= 36.0) return 1;
        if ($temp <= 38.0) return 0;
        if ($temp <= 39.0) return 1;
        return 2;                   // 39.1 or more
    }

    /** Human-readable text for each flag code (translated in the views with __()). */
    public static function flagLabel(string $code): string
    {
        return [
            'rr_low' => 'Low breathing rate',
            'rr_high' => 'Fast breathing',
            'spo2_low' => 'Low oxygen (SpO2)',
            'bp_low' => 'Low blood pressure',
            'bp_high' => 'Very high blood pressure',
            'pulse_low' => 'Slow pulse',
            'pulse_high' => 'Fast pulse',
            'consciousness_altered' => 'Not fully alert',
            'temp_low' => 'Low temperature',
            'temp_high' => 'Fever',
            'hypertensive_crisis' => 'Hypertensive crisis',
        ][$code] ?? $code;
    }
}
