<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Visit + triage flow through the web, including server-side validation of clinical inputs.
 */
class TriageTest extends TestCase
{
    use RefreshDatabase;

    private function nurse(): User
    {
        return User::factory()->create(['role' => 'nurse']);
    }

    private function waitingVisit(): Visit
    {
        $patient = Patient::factory()->create();

        return Visit::create(['patient_id' => $patient->id, 'arrived_at' => now()->subMinutes(20), 'status' => 'waiting', 'created_by' => $patient->created_by]);
    }

    private function form(array $changes = []): array
    {
        return array_merge([
            'systolic_bp' => 120, 'diastolic_bp' => 80, 'pulse' => 72, 'temperature' => 37.0,
            'spo2' => 98, 'respiratory_rate' => 16, 'consciousness' => 'A',
            'chief_complaint' => 'Cough for 3 days', 'pain_score' => 2, 'allergies' => 'NKDA', 'fall_risk' => 'low',
        ], $changes);
    }

    public function test_front_desk_can_open_a_visit_once_per_day(): void
    {
        $frontDesk = User::factory()->create(['role' => 'front_desk']);
        $patient = Patient::factory()->create();

        $this->actingAs($frontDesk)->post("/patients/{$patient->id}/visits")->assertRedirect();
        $this->actingAs($frontDesk)->post("/patients/{$patient->id}/visits")->assertRedirect();   // double click

        $this->assertSame(1, Visit::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.created', 'patient_id' => $patient->id]);
    }

    public function test_old_untriaged_visit_does_not_block_a_new_visit(): void
    {
        $visit = $this->waitingVisit();
        $visit->update(['arrived_at' => now()->subDays(3)]);   // patient left 3 days ago

        $this->actingAs(User::factory()->create(['role' => 'front_desk']))->post("/patients/{$visit->patient_id}/visits");

        $this->assertSame(2, Visit::count());
    }

    public function test_nurse_records_triage_and_server_computes_the_score(): void
    {
        $nurse = $this->nurse();
        $visit = $this->waitingVisit();

        // The browser tries to send its own score/risk: must be ignored.
        $this->actingAs($nurse)
            ->post("/triage/{$visit->id}", $this->form(['spo2' => 90, 'news2_score' => 0, 'risk_level' => 'low']))
            ->assertRedirect('/triage');

        $triage = Triage::first();
        $this->assertSame(3, $triage->news2_score);
        $this->assertSame('medium', $triage->risk_level);
        $this->assertSame(['spo2_low'], $triage->flags);
        $this->assertSame($nurse->id, $triage->recorded_by);   // "who recorded it" is automatic
        $this->assertNotNull($triage->recorded_at);
        $this->assertSame('triaged', $visit->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'triage.created', 'patient_id' => $visit->patient_id]);
    }

    public function test_impossible_values_are_rejected(): void
    {
        $visit = $this->waitingVisit();

        $this->actingAs($this->nurse())
            ->post("/triage/{$visit->id}", $this->form([
                'pulse' => 900,           // brief's example
                'spo2' => 140,            // brief's example
                'temperature' => 50,
                'respiratory_rate' => 0,
                'diastolic_bp' => 130,    // higher than systolic 120
                'pain_score' => 11,
                'consciousness' => 'X',
            ]))
            ->assertSessionHasErrors(['pulse', 'spo2', 'temperature', 'respiratory_rate', 'diastolic_bp', 'pain_score', 'consciousness']);

        $this->assertSame(0, Triage::count());
    }

    public function test_all_required_fields_must_be_present(): void
    {
        $visit = $this->waitingVisit();

        $this->actingAs($this->nurse())
            ->post("/triage/{$visit->id}", [])
            ->assertSessionHasErrors(['systolic_bp', 'diastolic_bp', 'pulse', 'temperature', 'spo2', 'respiratory_rate',
                'consciousness', 'chief_complaint', 'pain_score', 'allergies', 'fall_risk']);
    }

    public function test_a_visit_cannot_be_triaged_twice(): void
    {
        $nurse = $this->nurse();
        $visit = $this->waitingVisit();

        $this->actingAs($nurse)->post("/triage/{$visit->id}", $this->form());
        $this->actingAs($nurse)->post("/triage/{$visit->id}", $this->form(['pulse' => 140]));

        $this->assertSame(1, Triage::count());
        $this->assertSame(72, Triage::first()->pulse);
    }

    public function test_front_desk_cannot_submit_triage(): void
    {
        $visit = $this->waitingVisit();

        $this->actingAs(User::factory()->create(['role' => 'front_desk']))
            ->post("/triage/{$visit->id}", $this->form())
            ->assertForbidden();

        $this->assertSame(0, Triage::count());
    }

    public function test_queue_shows_todays_waiting_patients_only(): void
    {
        $today = $this->waitingVisit();
        $old = $this->waitingVisit();
        $old->update(['arrived_at' => now()->subDays(2)]);

        $this->actingAs($this->nurse())->get('/triage')
            ->assertOk()
            ->assertSee($today->patient->full_name)
            ->assertDontSee($old->patient->full_name);
    }
}
