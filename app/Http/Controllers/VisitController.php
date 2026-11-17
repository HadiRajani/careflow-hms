<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Visit;

class VisitController extends Controller
{
    /**
     * POST /patients/{patient}/visits  Open an OPD visit (patient has arrived).
     * The arrival time starts the "wait to triage" clock used on the dashboard.
     */
    public function store(Patient $patient)
    {
        // Avoid double visits from a double click: one waiting visit per patient per day.
        // (An untriaged visit from an earlier day means the patient left; it must not block a new visit.)
        $alreadyWaiting = $patient->visits()
            ->where('status', 'waiting')
            ->whereDate('arrived_at', today())
            ->exists();

        if ($alreadyWaiting) {
            return redirect()->route('patients.show', $patient)
                ->with('success', __('This patient is already waiting for triage.'));
        }

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'arrived_at' => now(),
            'status' => 'waiting',
            'created_by' => auth()->id(),
        ]);

        AuditLog::record('visit.created', $patient->id, $visit);

        return redirect()->route('patients.show', $patient)
            ->with('success', __('OPD visit opened. The patient is now in the triage queue.'));
    }
}
