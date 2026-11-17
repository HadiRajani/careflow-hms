<?php

namespace App\Services;

use App\Models\Patient;

/**
 * Finds patients who are probably the same person as the one being registered.
 *
 * Rule (documented in README):
 *   - Same phone  + name at least 80% similar  -> possible duplicate
 *   - Same DOB    + name at least 80% similar  -> possible duplicate
 *   - Same phone alone is NOT enough: in Pakistan whole families often share one phone.
 *   - Same CNIC is handled separately as a hard block (validation rule in the controller).
 *
 * It only WARNS. Staff decide, because similar names can be different people (e.g. sisters).
 */
class DuplicatePatientChecker
{
    public const NAME_SIMILARITY_THRESHOLD = 80;   // percent; chosen by testing real name pairs

    /**
     * Returns a list of possible duplicates:
     *   [ ['patient' => Patient, 'reason' => 'Same phone, similar name (92%)'], ... ]
     */
    public function findPossibleDuplicates(string $name, string $phone, string $dateOfBirth): array
    {
        // Step 1: only look at patients with the same phone OR the same date of birth.
        // Both columns are indexed, so this stays fast even with millions of patients.
        $candidates = Patient::where('phone', $phone)
            ->orWhereDate('date_of_birth', $dateOfBirth)
            ->limit(50)
            ->get();

        // Step 2: keep only candidates whose name is similar enough.
        $matches = [];

        foreach ($candidates as $candidate) {
            $similarity = $this->nameSimilarity($name, $candidate->full_name);

            if ($similarity < self::NAME_SIMILARITY_THRESHOLD) {
                continue;   // different name -> probably a family member, not a duplicate
            }

            $samePhone = $candidate->phone === $phone;
            $sameDob = $candidate->date_of_birth->format('Y-m-d') === $dateOfBirth;

            if ($samePhone && $sameDob) {
                $reason = "Same phone and date of birth, similar name ({$similarity}%)";
            } elseif ($samePhone) {
                $reason = "Same phone, similar name ({$similarity}%)";
            } else {
                $reason = "Same date of birth, similar name ({$similarity}%)";
            }

            $matches[] = ['patient' => $candidate, 'reason' => $reason];
        }

        return $matches;
    }

    /**
     * How similar two names are, 0-100.
     * "Muhammad Ali" vs "Mohammad Ali" -> 92.   "Ayesha Khan" vs "Imran Khan" -> 57.
     */
    public function nameSimilarity(string $a, string $b): int
    {
        similar_text($this->normalizeName($a), $this->normalizeName($b), $percent);

        return (int) round($percent);
    }

    /**
     * "  Bilal   AHMED " -> "ahmed bilal"
     * Lowercase, remove punctuation and extra spaces, and sort the words,
     * so "Bilal Ahmed" and "Ahmed Bilal" count as the same name.
     */
    public function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z\s]/', '', $name);      // keep letters and spaces only
        $words = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        sort($words);

        return implode(' ', $words);
    }

    /**
     * Pakistani mobile numbers in any common format -> 03XXXXXXXXX
     *   "+92 300-1234567" -> "03001234567"
     *   "923001234567"    -> "03001234567"
     *   "3001234567"      -> "03001234567"
     */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);   // keep digits only

        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            return '0' . substr($digits, 2);
        }

        if (str_starts_with($digits, '3') && strlen($digits) === 10) {
            return '0' . $digits;
        }

        return $digits;
    }
}
