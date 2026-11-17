<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndLanguageTest extends TestCase
{
    use RefreshDatabase;

    private function visitWithTriage(Patient $patient, $arrivedAt, int $waitMinutes, string $risk): void
    {
        $visit = Visit::create(['patient_id' => $patient->id, 'arrived_at' => $arrivedAt, 'status' => 'triaged', 'created_by' => $patient->created_by]);
        Triage::create([
            'visit_id' => $visit->id, 'systolic_bp' => 120, 'diastolic_bp' => 80, 'pulse' => 72, 'temperature' => 37,
            'spo2' => 98, 'respiratory_rate' => 16, 'consciousness' => 'A', 'chief_complaint' => 'Test',
            'pain_score' => 0, 'allergies' => 'NKDA', 'fall_risk' => 'low', 'news2_score' => $risk === 'low' ? 0 : 5,
            'risk_level' => $risk, 'flags' => $risk === 'low' ? [] : ['temp_high'],
            'recorded_by' => $patient->created_by, 'recorded_at' => $arrivedAt->copy()->addMinutes($waitMinutes),
        ]);
    }

    public function test_dashboard_metrics_are_computed_from_captured_data(): void
    {
        $a = Patient::factory()->create();
        $b = Patient::factory()->create();

        // Patient A: two visits 3 days apart (= one return within 7 days)
        $this->visitWithTriage($a, today()->subDays(5)->setTime(10, 0), 10, 'low');
        $this->visitWithTriage($a, today()->subDays(2)->setTime(10, 0), 30, 'medium');
        // Patient B: one visit
        $this->visitWithTriage($b, today()->subDays(1)->setTime(9, 0), 20, 'low');

        $manager = User::factory()->create(['role' => 'quality_manager']);
        $response = $this->actingAs($manager)->get('/dashboard')->assertOk();

        $response->assertViewHas('avgWait', 20);        // (10 + 30 + 20) / 3
        $response->assertViewHas('abnormalRate', 33);   // 1 of 3
        $response->assertViewHas('returnRate', 33);     // 1 return out of 3 visits
        $response->assertViewHas('within30', 100);
        $response->assertSee('What this means');
    }

    public function test_language_switch_shows_urdu_right_to_left(): void
    {
        $nurse = User::factory()->create(['role' => 'nurse']);

        $this->actingAs($nurse)->get('/language/ur')->assertRedirect();

        $this->actingAs($nurse)->get('/triage')
            ->assertSee('dir="rtl"', false)
            ->assertSee('ٹرائیج قطار');   // "Triage queue" in Urdu
    }

    public function test_unsupported_language_is_rejected(): void
    {
        $this->get('/language/fr')->assertNotFound();
    }
}
