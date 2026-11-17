<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Services\DuplicatePatientChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    /**
     * GET /patients?q=...  Search by MRN, phone, CNIC or name.
     * No list is shown until you search: staff only see the patients they look for.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $patients = collect();

        if ($search !== '') {
            $phone = DuplicatePatientChecker::normalizePhone($search);
            $digits = preg_replace('/\D/', '', $search);

            $patients = Patient::where('mrn', strtoupper($search))
                ->orWhere('phone', $phone)
                ->orWhere('cnic', $digits ?: '-')
                ->orWhere('full_name', 'like', '%' . $search . '%')
                ->orderBy('full_name')
                ->limit(25)
                ->get();

            AuditLog::record('patient.searched', null, null, ['results' => $patients->count()]);
        }

        return view('patients.index', ['patients' => $patients, 'search' => $search]);
    }

    /** GET /patients/create  Show the registration form. */
    public function create()
    {
        return view('patients.create', ['duplicates' => []]);
    }

    /** POST /patients  Validate, check for duplicates, then save. */
    public function store(Request $request, DuplicatePatientChecker $checker)
    {
        // 1. Clean the input first, so "+92 300-1234567" and "0300 1234567" are stored the same way.
        $request->merge([
            'phone' => DuplicatePatientChecker::normalizePhone($request->input('phone')),
            'cnic' => preg_replace('/\D/', '', (string) $request->input('cnic')) ?: null,
        ]);

        // 2. Server-side validation. Same CNIC twice = hard block (unique rule).
        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:120', "regex:/^[A-Za-z\s.'-]+$/"],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'gender' => ['required', 'in:male,female,other'],
            'phone' => ['required', 'regex:/^03\d{9}$/'],
            'cnic' => ['nullable', 'digits:13', 'unique:patients,cnic'],
        ], [
            'full_name.regex' => 'Please type the name in English letters (as on the CNIC).',
            'phone.regex' => 'Enter a Pakistani mobile number, e.g. 0300-1234567.',
            'cnic.digits' => 'CNIC must be 13 digits, e.g. 42101-1234567-1.',
            'cnic.unique' => 'A patient with this CNIC is already registered. Search for them instead.',
        ]);

        // 3. Look for likely duplicates (same phone or DOB + similar name).
        $duplicates = $checker->findPossibleDuplicates($data['full_name'], $data['phone'], $data['date_of_birth']);
        $userConfirmed = $request->boolean('confirm_not_duplicate');

        // 4. Duplicates found and not yet confirmed -> show the warning, keep what was typed.
        if (count($duplicates) > 0 && ! $userConfirmed) {
            $request->flash();
            return view('patients.create', ['duplicates' => $duplicates]);
        }

        // 5. Save. MRN is built from the new id, e.g. id 42 -> CF-000042.
        $patient = DB::transaction(function () use ($data) {
            $patient = Patient::create($data + ['created_by' => auth()->id()]);
            $patient->mrn = 'CF-' . str_pad($patient->id, 6, '0', STR_PAD_LEFT);
            $patient->save();
            return $patient;
        });

        // 6. Audit. If the user overrode a duplicate warning, record that too.
        $meta = [];
        if (count($duplicates) > 0) {
            $meta = [
                'duplicate_warning_overridden' => true,
                'possible_duplicate_ids' => array_map(fn ($d) => $d['patient']->id, $duplicates),
            ];
        }
        AuditLog::record('patient.created', $patient->id, $patient, $meta);

        return redirect()->route('patients.show', $patient)
            ->with('success', __('Patient registered') . ': ' . $patient->mrn);
    }

    /** GET /patients/{id}  Patient details. Every view is written to the audit log. */
    public function show(Patient $patient)
    {
        AuditLog::record('patient.viewed', $patient->id, $patient);

        $visits = $patient->visits()->with('triage.recorder')->latest('arrived_at')->limit(10)->get();

        return view('patients.show', ['patient' => $patient, 'visits' => $visits]);
    }
}
