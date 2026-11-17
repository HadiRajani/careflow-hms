<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Triage;
use App\Models\Visit;
use App\Services\TriageScorer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TriageController extends Controller
{
    /** GET /triage  The nurse's queue: who is waiting (oldest first) and who was triaged today. */
    public function queue()
    {
        // Only today's arrivals. Older untriaged visits = patient left; they show on the dashboard instead.
        $waiting = Visit::with('patient')
            ->where('status', 'waiting')
            ->whereDate('arrived_at', today())
            ->orderBy('arrived_at')
            ->get();

        $triagedToday = Visit::with('patient', 'triage')
            ->where('status', 'triaged')
            ->whereDate('arrived_at', today())
            ->latest('arrived_at')
            ->limit(20)
            ->get();

        return view('triage.queue', ['waiting' => $waiting, 'triagedToday' => $triagedToday]);
    }

    /** GET /triage/{visit}  The triage form. */
    public function create(Visit $visit)
    {
        if ($visit->status !== 'waiting') {
            return redirect()->route('visits.queue')->with('success', __('This visit has already been triaged.'));
        }

        AuditLog::record('visit.viewed', $visit->patient_id, $visit);

        return view('triage.create', ['visit' => $visit->load('patient')]);
    }

    /** POST /triage/{visit}  Validate vitals, compute NEWS2 on the server, save. */
    public function store(Request $request, Visit $visit, TriageScorer $scorer)
    {
        if ($visit->status !== 'waiting') {
            return redirect()->route('visits.queue')->with('success', __('This visit has already been triaged.'));
        }

        // Server-side validation. Ranges = what is physically possible in a living patient.
        // Anything outside is a typing mistake (e.g. pulse 900, SpO2 140%) and is REJECTED.
        // Readings inside the range but abnormal are ACCEPTED and FLAGGED by the scorer.
        $data = $request->validate([
            'systolic_bp' => ['required', 'integer', 'between:50,260'],
            'diastolic_bp' => ['required', 'integer', 'between:20,160', 'lt:systolic_bp'],
            'pulse' => ['required', 'integer', 'between:20,250'],
            'temperature' => ['required', 'numeric', 'between:30,45'],
            'spo2' => ['required', 'integer', 'between:50,100'],
            'respiratory_rate' => ['required', 'integer', 'between:4,60'],
            'consciousness' => ['required', 'in:A,V,P,U'],
            'chief_complaint' => ['required', 'string', 'min:3', 'max:500'],
            'pain_score' => ['required', 'integer', 'between:0,10'],
            'allergies' => ['required', 'string', 'max:255'],
            'fall_risk' => ['required', 'in:low,medium,high'],
        ], [
            'diastolic_bp.lt' => 'Diastolic must be lower than systolic.',
        ]);

        // Score is ALWAYS computed here. Anything the browser sends as a score is ignored.
        $result = $scorer->score($data);

        $triage = DB::transaction(function () use ($data, $result, $visit) {
            $triage = Triage::create($data + [
                'visit_id' => $visit->id,
                'news2_score' => $result['score'],
                'risk_level' => $result['risk_level'],
                'flags' => $result['flags'],
                'recorded_by' => auth()->id(),   // field 11: who recorded it (automatic)
                'recorded_at' => now(),          // field 12: when (automatic, server clock)
            ]);
            $visit->update(['status' => 'triaged']);
            return $triage;
        });

        AuditLog::record('triage.created', $visit->patient_id, $triage, [
            'news2_score' => $result['score'],
            'risk_level' => $result['risk_level'],
        ]);

        $message = __('Triage saved') . ' — ' . $visit->patient->full_name . ': NEWS2 ' . $result['score']
            . ' (' . __(ucfirst($result['risk_level']) . ' risk') . ')';

        return redirect()->route('visits.queue')->with('success', $message);
    }
}
