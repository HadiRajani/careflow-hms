<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * GET /audit?mrn=CF-000042&action=patient.viewed
     * Read-only list of who viewed or changed which patient record, and when.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user', 'patient')->latest('id');

        // Filter: one patient ("who has looked at this patient's record?")
        if ($request->filled('mrn')) {
            $patient = Patient::where('mrn', strtoupper(trim($request->query('mrn'))))->first();
            $query->where('patient_id', $patient?->id ?? 0);
        }

        // Filter: one type of action
        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        $actions = ['patient.searched', 'patient.viewed', 'patient.created', 'visit.created', 'visit.viewed', 'triage.created'];

        return view('audit.index', [
            'logs' => $query->paginate(50)->withQueryString(),
            'actions' => $actions,
        ]);
    }
}
