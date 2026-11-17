<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use App\Services\TriageScorer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates SYNTHETIC OPD data: ~380 patients and ~600 visits over the last 30 days.
 *
 * Nothing here is real:
 *   - names are random combinations of common first/last names
 *   - phone numbers start with 039, a prefix not allocated to any Pakistani mobile operator
 *   - CNICs start with 00000, which no real CNIC does
 *
 * Built-in patterns so the dashboard has something to find:
 *   - most arrivals 09:00-12:00, and waits are longest at 10:00-12:00
 *   - fever cases rise in the last 7 days (like a dengue season)
 *   - ~10% of visits are patients coming back within 7 days
 *   - ~3% of past visits were never triaged (patient left)
 *
 * Run:  php artisan db:seed --class=SyntheticOpdSeeder
 */
class SyntheticOpdSeeder extends Seeder
{
    private array $femaleNames = ['Ayesha', 'Fatima', 'Zainab', 'Maryam', 'Sana', 'Hira', 'Amna', 'Khadija', 'Rabia', 'Saima', 'Nadia', 'Farah', 'Iqra', 'Mehwish', 'Bushra', 'Samina', 'Kiran', 'Noor', 'Asma', 'Shazia', 'Uzma', 'Sadia', 'Lubna', 'Rukhsana', 'Parveen'];
    private array $maleNames = ['Muhammad', 'Ali', 'Ahmed', 'Hassan', 'Hussain', 'Usman', 'Bilal', 'Imran', 'Kashif', 'Faisal', 'Tariq', 'Zubair', 'Hamza', 'Asad', 'Junaid', 'Saad', 'Waqas', 'Adnan', 'Naveed', 'Shahid', 'Rizwan', 'Arif', 'Salman', 'Owais', 'Danish'];
    private array $lastNames = ['Khan', 'Ahmed', 'Ali', 'Raza', 'Hussain', 'Malik', 'Qureshi', 'Siddiqui', 'Sheikh', 'Butt', 'Chaudhry', 'Mirza', 'Abbasi', 'Baig', 'Jafri', 'Rizvi', 'Haider', 'Shah', 'Iqbal', 'Akhtar', 'Javed', 'Memon', 'Soomro', 'Baloch', 'Yousafzai'];

    public function run(): void
    {
        if (Patient::count() > 0) {
            $this->command?->warn('Patients already exist - skipping synthetic data. Use migrate:fresh --seed to rebuild.');
            return;
        }

        mt_srand(2026);   // same data every run, so the demo is reproducible

        $frontDesk = User::where('role', 'front_desk')->firstOrFail();
        $nurse = User::where('role', 'nurse')->firstOrFail();
        $scorer = new TriageScorer();

        DB::transaction(function () use ($frontDesk, $nurse, $scorer) {
            $patients = $this->createPatients(380, $frontDesk);
            $this->createVisits($patients, $frontDesk, $nurse, $scorer);
        });

        $this->command?->info('Synthetic data: ' . Patient::count() . ' patients, ' . Visit::count() . ' visits, ' . Triage::count() . ' triages.');
    }

    private function createPatients(int $count, User $frontDesk): array
    {
        $patients = [];

        for ($i = 0; $i < $count; $i++) {
            $gender = mt_rand(0, 1) ? 'female' : 'male';
            $first = $gender === 'female' ? $this->pick($this->femaleNames) : $this->pick($this->maleNames);

            // 15% share a phone with an earlier patient (families often share one phone)
            $phone = (count($patients) > 0 && mt_rand(1, 100) <= 15)
                ? $this->pick($patients)->phone
                : '039' . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);

            $patient = new Patient([
                'full_name' => $first . ' ' . $this->pick($this->lastNames),
                'date_of_birth' => now()->subYears($this->randomAge())->subDays(mt_rand(0, 364))->format('Y-m-d'),
                'gender' => $gender,
                'phone' => $phone,
                'cnic' => mt_rand(1, 100) <= 60 ? '00000' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT) : null,
                'created_by' => $frontDesk->id,
            ]);
            $patient->created_at = now()->subDays(mt_rand(30, 90));
            $patient->save();
            $patient->mrn = 'CF-' . str_pad($patient->id, 6, '0', STR_PAD_LEFT);
            $patient->save();

            $patients[] = $patient;
        }

        return $patients;
    }

    private function createVisits(array $patients, User $frontDesk, User $nurse, TriageScorer $scorer): void
    {
        $lastVisitOf = [];   // patient_id => Carbon of their latest visit (to create 7-day returns)

        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $day = today()->subDays($daysAgo);
            $isRecentWeek = $daysAgo < 7;

            // Fewer patients on Sunday, a bit fewer on Friday
            $visitsToday = match ($day->dayOfWeek) {
                Carbon::SUNDAY => mt_rand(6, 10),
                Carbon::FRIDAY => mt_rand(14, 18),
                default => mt_rand(18, 26),
            };

            for ($n = 0; $n < $visitsToday; $n++) {
                $arrivedAt = $day->copy()->setTime($this->randomArrivalHour(), mt_rand(0, 59));

                if ($arrivedAt->isFuture()) {
                    continue;   // today: only visits that already happened
                }

                // ~10% are returns: pick a patient seen in the last 7 days
                $recent = array_filter($lastVisitOf, fn ($date) => $date->diffInDays($arrivedAt, true) <= 7 && $date->lt($arrivedAt));
                if (count($recent) > 0 && mt_rand(1, 100) <= 10) {
                    $patient = $this->findPatient($patients, array_rand($recent));
                } else {
                    // Otherwise prefer someone NOT seen in the last 7 days (avoid accidental returns).
                    $patient = $this->pick($patients);
                    for ($try = 0; $try < 10 && isset($recent[$patient->id]); $try++) {
                        $patient = $this->pick($patients);
                    }
                }

                $lastVisitOf[$patient->id] = $arrivedAt;

                // Wait time: longer at the 10:00-12:00 peak
                $wait = mt_rand(5, 18) + (in_array($arrivedAt->hour, [10, 11]) ? mt_rand(15, 35) : 0) + (mt_rand(1, 100) <= 5 ? mt_rand(20, 60) : 0);
                $triagedAt = $arrivedAt->copy()->addMinutes($wait);

                // Not triaged: arrived recently today and still waiting, or ~3% of older visits (left)
                $stillWaiting = $triagedAt->isFuture() || ($daysAgo > 0 && mt_rand(1, 100) <= 3);

                $visit = new Visit([
                    'patient_id' => $patient->id,
                    'arrived_at' => $arrivedAt,
                    'status' => $stillWaiting ? 'waiting' : 'triaged',
                    'created_by' => $frontDesk->id,
                ]);
                $visit->created_at = $arrivedAt;
                $visit->save();

                if ($stillWaiting) {
                    continue;
                }

                $vitals = $this->randomVitals($isRecentWeek, $patient->age());
                $result = $scorer->score($vitals);

                $triage = new Triage($vitals + [
                    'visit_id' => $visit->id,
                    'news2_score' => $result['score'],
                    'risk_level' => $result['risk_level'],
                    'flags' => $result['flags'],
                    'recorded_by' => $nurse->id,
                    'recorded_at' => $triagedAt,
                ]);
                $triage->created_at = $triagedAt;
                $triage->save();
            }
        }

        // A few patients waiting RIGHT NOW, so the nurse's queue is never empty in the demo.
        foreach ([38, 22, 11, 4] as $minutesAgo) {
            $visit = new Visit([
                'patient_id' => $this->pick($patients)->id,
                'arrived_at' => now()->subMinutes($minutesAgo),
                'status' => 'waiting',
                'created_by' => $frontDesk->id,
            ]);
            $visit->save();
        }
    }

    /** Vital signs for one of several typical OPD presentations. */
    private function randomVitals(bool $isRecentWeek, int $age): array
    {
        $roll = mt_rand(1, 100);
        $feverChance = $isRecentWeek ? 20 : 9;   // fever rises in the last week

        if ($roll <= $feverChance) {
            $v = $this->vitals(mt_rand(110, 135), mt_rand(70, 85), mt_rand(95, 125), mt_rand(381, 400) / 10, mt_rand(94, 98), mt_rand(18, 24));
            $v['chief_complaint'] = $this->pick(['High fever and body aches for 3 days', 'Fever with headache and joint pain', 'Fever and vomiting since yesterday', 'Fever with rash on arms']);
        } elseif ($roll <= $feverChance + 8) {
            $v = $this->vitals(mt_rand(165, 200), mt_rand(100, 125), mt_rand(75, 100), mt_rand(365, 375) / 10, mt_rand(96, 99), mt_rand(14, 18));
            $v['chief_complaint'] = $this->pick(['Severe headache, known hypertensive', 'Dizziness and blurred vision', 'Missed BP medicine for a week']);
        } elseif ($roll <= $feverChance + 13) {
            $v = $this->vitals(mt_rand(110, 130), mt_rand(70, 85), mt_rand(95, 115), mt_rand(370, 385) / 10, mt_rand(89, 94), mt_rand(22, 28));
            $v['chief_complaint'] = $this->pick(['Shortness of breath and wheeze', 'Cough with breathing difficulty for 2 days', 'Chest tightness, known asthmatic']);
        } elseif ($roll <= $feverChance + 17) {
            $v = $this->vitals(mt_rand(88, 105), mt_rand(55, 68), mt_rand(110, 135), mt_rand(368, 380) / 10, mt_rand(95, 98), mt_rand(18, 23));
            $v['chief_complaint'] = $this->pick(['Diarrhoea and vomiting since morning', 'Weakness and dizziness on standing', 'Loose motions for 2 days']);
        } elseif ($roll <= $feverChance + 18) {
            $v = $this->vitals(mt_rand(80, 95), mt_rand(45, 60), mt_rand(125, 145), mt_rand(390, 405) / 10, mt_rand(88, 92), mt_rand(26, 32));
            $v['consciousness'] = 'V';
            $v['chief_complaint'] = 'Confused and drowsy, high fever (brought by family)';
        } else {
            $v = $this->vitals(mt_rand(112, 138), mt_rand(70, 88), mt_rand(62, 88), mt_rand(362, 374) / 10, mt_rand(96, 99), mt_rand(13, 18));
            $v['chief_complaint'] = $this->pick(['Follow-up for diabetes', 'Back pain for a week', 'Sore throat', 'Stomach pain after meals', 'Skin itching', 'Knee pain', 'Routine BP check', 'Ear pain', 'Burning urination', 'Cough for 5 days']);
        }

        $v['pain_score'] = $this->pick([0, 0, 1, 2, 2, 3, 3, 4, 5, 6, 7, 8]);
        $v['allergies'] = mt_rand(1, 100) <= 85 ? 'NKDA' : $this->pick(['Penicillin', 'Sulfa drugs', 'Aspirin', 'Ibuprofen']);
        $v['fall_risk'] = $age >= 65 ? $this->pick(['medium', 'high']) : (mt_rand(1, 100) <= 5 ? 'medium' : 'low');

        return $v;
    }

    private function vitals(int $sys, int $dia, int $pulse, float $temp, int $spo2, int $rr): array
    {
        return [
            'systolic_bp' => $sys, 'diastolic_bp' => min($dia, $sys - 10), 'pulse' => $pulse,
            'temperature' => $temp, 'spo2' => $spo2, 'respiratory_rate' => $rr, 'consciousness' => 'A',
        ];
    }

    /** OPD arrivals peak in the late morning. */
    private function randomArrivalHour(): int
    {
        $weights = [8 => 6, 9 => 12, 10 => 16, 11 => 16, 12 => 12, 13 => 8, 14 => 7, 15 => 5, 16 => 4, 17 => 2];
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $hour => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $hour;
            }
        }
        return 10;
    }

    private function randomAge(): int
    {
        $roll = mt_rand(1, 100);
        if ($roll <= 10) return mt_rand(2, 15);    // children
        if ($roll <= 80) return mt_rand(16, 64);   // adults
        return mt_rand(65, 88);                    // elderly
    }

    private function findPatient(array $patients, int $id): Patient
    {
        foreach ($patients as $p) {
            if ($p->id === $id) return $p;
        }
        return $patients[0];
    }

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }
}
