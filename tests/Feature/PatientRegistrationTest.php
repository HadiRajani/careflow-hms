<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests the full registration flow through the web form.
 */
class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function frontDesk(): User
    {
        return User::factory()->create(['role' => 'front_desk']);
    }

    private function validForm(array $changes = []): array
    {
        return array_merge([
            'full_name' => 'Ayesha Khan',
            'date_of_birth' => '1995-03-10',
            'gender' => 'female',
            'phone' => '+92 300-1234567',
            'cnic' => '42101-1234567-1',
        ], $changes);
    }

    public function test_new_patient_is_saved_with_clean_phone_mrn_and_audit_entry(): void
    {
        $user = $this->frontDesk();

        $this->actingAs($user)->post('/patients', $this->validForm())->assertRedirect();

        $patient = Patient::first();
        $this->assertSame('03001234567', $patient->phone);      // phone was normalised
        $this->assertSame('4210112345671', $patient->cnic);     // dashes removed
        $this->assertSame('CF-000001', $patient->mrn);
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.created', 'patient_id' => $patient->id, 'user_id' => $user->id]);
    }

    public function test_likely_duplicate_shows_warning_and_is_not_saved(): void
    {
        Patient::factory()->create(['full_name' => 'Aisha Khan', 'phone' => '03001234567']);

        $this->actingAs($this->frontDesk())
            ->post('/patients', $this->validForm(['cnic' => '']))
            ->assertOk()
            ->assertSee('This patient may already be registered');

        $this->assertSame(1, Patient::count());   // nothing new was created
    }

    public function test_user_can_override_duplicate_warning_and_override_is_audited(): void
    {
        $existing = Patient::factory()->create(['full_name' => 'Aisha Khan', 'phone' => '03001234567']);

        $this->actingAs($this->frontDesk())
            ->post('/patients', $this->validForm(['cnic' => '', 'confirm_not_duplicate' => '1']))
            ->assertRedirect();

        $this->assertSame(2, Patient::count());
        $log = AuditLog::where('action', 'patient.created')->first();
        $this->assertTrue($log->meta['duplicate_warning_overridden']);
        $this->assertSame([$existing->id], $log->meta['possible_duplicate_ids']);
    }

    public function test_same_cnic_is_blocked_even_with_override(): void
    {
        Patient::factory()->create(['cnic' => '4210112345671']);

        $this->actingAs($this->frontDesk())
            ->post('/patients', $this->validForm(['confirm_not_duplicate' => '1']))
            ->assertSessionHasErrors('cnic');

        $this->assertSame(1, Patient::count());
    }

    public function test_invalid_input_is_rejected_on_the_server(): void
    {
        $this->actingAs($this->frontDesk())
            ->post('/patients', $this->validForm([
                'phone' => '12345',                  // not a Pakistani mobile
                'date_of_birth' => '2999-01-01',     // future
                'gender' => 'unknown',
                'full_name' => 'عائشہ',              // Urdu script would break duplicate matching
            ]))
            ->assertSessionHasErrors(['phone', 'date_of_birth', 'gender', 'full_name']);
    }

    public function test_viewing_a_patient_is_written_to_the_audit_log(): void
    {
        $user = $this->frontDesk();
        $patient = Patient::factory()->create();

        $this->actingAs($user)->get("/patients/{$patient->id}")->assertOk()->assertSee($patient->full_name);

        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.viewed', 'patient_id' => $patient->id, 'user_id' => $user->id]);
    }

    public function test_audit_log_rows_cannot_be_changed(): void
    {
        $this->actingAs($this->frontDesk())->get('/patients/' . Patient::factory()->create()->id);
        $log = AuditLog::first();

        $this->expectException(\LogicException::class);
        $log->update(['action' => 'tampered']);
    }
}
