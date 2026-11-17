<?php

namespace Tests\Unit;

use App\Services\TriageScorer;
use PHPUnit\Framework\TestCase;

/**
 * Tests the triage flag rule (NEWS2, RCP 2017) without a database.
 */
class TriageScorerTest extends TestCase
{
    private function normalVitals(array $changes = []): array
    {
        return array_merge([
            'respiratory_rate' => 16, 'spo2' => 98, 'systolic_bp' => 120, 'diastolic_bp' => 80,
            'pulse' => 72, 'temperature' => 37.0, 'consciousness' => 'A',
        ], $changes);
    }

    public function test_normal_vitals_score_zero_low_risk_no_flags(): void
    {
        $result = (new TriageScorer())->score($this->normalVitals());

        $this->assertSame(0, $result['score']);
        $this->assertSame('low', $result['risk_level']);
        $this->assertSame([], $result['flags']);
    }

    public function test_news2_chart_boundaries(): void
    {
        $s = new TriageScorer();

        // Respiratory rate
        $this->assertSame(3, $s->respiratoryRatePoints(8));
        $this->assertSame(1, $s->respiratoryRatePoints(11));
        $this->assertSame(0, $s->respiratoryRatePoints(20));
        $this->assertSame(2, $s->respiratoryRatePoints(21));
        $this->assertSame(3, $s->respiratoryRatePoints(25));
        // SpO2
        $this->assertSame(3, $s->spo2Points(91));
        $this->assertSame(2, $s->spo2Points(93));
        $this->assertSame(1, $s->spo2Points(95));
        $this->assertSame(0, $s->spo2Points(96));
        // Systolic BP
        $this->assertSame(3, $s->systolicPoints(90));
        $this->assertSame(2, $s->systolicPoints(100));
        $this->assertSame(1, $s->systolicPoints(110));
        $this->assertSame(0, $s->systolicPoints(219));
        $this->assertSame(3, $s->systolicPoints(220));
        // Pulse
        $this->assertSame(3, $s->pulsePoints(40));
        $this->assertSame(1, $s->pulsePoints(50));
        $this->assertSame(0, $s->pulsePoints(90));
        $this->assertSame(1, $s->pulsePoints(110));
        $this->assertSame(2, $s->pulsePoints(130));
        $this->assertSame(3, $s->pulsePoints(131));
        // Temperature
        $this->assertSame(3, $s->temperaturePoints(35.0));
        $this->assertSame(1, $s->temperaturePoints(36.0));
        $this->assertSame(0, $s->temperaturePoints(38.0));
        $this->assertSame(1, $s->temperaturePoints(39.0));
        $this->assertSame(2, $s->temperaturePoints(39.1));
    }

    public function test_single_red_score_makes_risk_at_least_medium(): void
    {
        // SpO2 90 = 3 points on its own; total 3 but one parameter is "red"
        $result = (new TriageScorer())->score($this->normalVitals(['spo2' => 90]));

        $this->assertSame(3, $result['score']);
        $this->assertSame('medium', $result['risk_level']);
        $this->assertSame(['spo2_low'], $result['flags']);
    }

    public function test_total_five_is_medium_and_seven_is_high(): void
    {
        $s = new TriageScorer();

        // RR 22 (2) + pulse 115 (2) + temp 38.5 (1) = 5
        $medium = $s->score($this->normalVitals(['respiratory_rate' => 22, 'pulse' => 115, 'temperature' => 38.5]));
        $this->assertSame(5, $medium['score']);
        $this->assertSame('medium', $medium['risk_level']);

        // RR 26 (3) + pulse 135 (3) + temp 39.5 (2) = 8
        $high = $s->score($this->normalVitals(['respiratory_rate' => 26, 'pulse' => 135, 'temperature' => 39.5]));
        $this->assertSame(8, $high['score']);
        $this->assertSame('high', $high['risk_level']);
        $this->assertEqualsCanonicalizing(['rr_high', 'pulse_high', 'temp_high'], $high['flags']);
    }

    public function test_not_alert_scores_three(): void
    {
        $result = (new TriageScorer())->score($this->normalVitals(['consciousness' => 'V']));

        $this->assertSame(3, $result['score']);
        $this->assertContains('consciousness_altered', $result['flags']);
    }

    public function test_hypertensive_crisis_is_flagged_even_when_news2_is_low(): void
    {
        $result = (new TriageScorer())->score($this->normalVitals(['systolic_bp' => 190, 'diastolic_bp' => 125]));

        $this->assertSame(0, $result['score']);               // NEWS2 does not score high BP below 220
        $this->assertContains('hypertensive_crisis', $result['flags']);
        $this->assertSame('medium', $result['risk_level']);   // but it still needs prompt review
    }
}
