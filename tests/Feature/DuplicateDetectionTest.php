<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Services\DuplicatePatientChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests the duplicate detection rule directly.
 */
class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_numbers_in_different_formats_become_the_same(): void
    {
        $this->assertSame('03001234567', DuplicatePatientChecker::normalizePhone('0300-1234567'));
        $this->assertSame('03001234567', DuplicatePatientChecker::normalizePhone('+92 300 1234567'));
        $this->assertSame('03001234567', DuplicatePatientChecker::normalizePhone('923001234567'));
        $this->assertSame('03001234567', DuplicatePatientChecker::normalizePhone('3001234567'));
    }

    public function test_spelling_variants_and_word_order_count_as_similar(): void
    {
        $checker = new DuplicatePatientChecker();

        $this->assertGreaterThanOrEqual(80, $checker->nameSimilarity('Muhammad Ali', 'Mohammad Ali'));
        $this->assertGreaterThanOrEqual(80, $checker->nameSimilarity('Ayesha Khan', 'Aisha Khan'));
        $this->assertSame(100, $checker->nameSimilarity('Bilal Ahmed', 'ahmed  BILAL'));
    }

    public function test_same_phone_and_similar_name_is_flagged(): void
    {
        Patient::factory()->create(['full_name' => 'Muhammad Ali', 'phone' => '03001234567', 'date_of_birth' => '1980-05-01']);

        $matches = (new DuplicatePatientChecker())->findPossibleDuplicates('Mohammad Ali', '03001234567', '1990-01-01');

        $this->assertCount(1, $matches);
        $this->assertStringContainsString('Same phone', $matches[0]['reason']);
    }

    public function test_same_dob_and_similar_name_is_flagged_even_with_new_phone(): void
    {
        Patient::factory()->create(['full_name' => 'Ayesha Khan', 'phone' => '03001111111', 'date_of_birth' => '1995-03-10']);

        $matches = (new DuplicatePatientChecker())->findPossibleDuplicates('Aisha Khan', '03219999999', '1995-03-10');

        $this->assertCount(1, $matches);
        $this->assertStringContainsString('Same date of birth', $matches[0]['reason']);
    }

    public function test_family_member_sharing_a_phone_is_not_flagged(): void
    {
        // Mother registered; now her son with the same family phone arrives.
        Patient::factory()->create(['full_name' => 'Fatima Bibi', 'phone' => '03001234567', 'date_of_birth' => '1975-01-01']);

        $matches = (new DuplicatePatientChecker())->findPossibleDuplicates('Imran Ahmed', '03001234567', '2005-06-15');

        $this->assertCount(0, $matches);
    }

    public function test_completely_different_patient_is_not_flagged(): void
    {
        Patient::factory()->create(['full_name' => 'Zainab Raza', 'phone' => '03001234567', 'date_of_birth' => '1990-01-01']);

        $matches = (new DuplicatePatientChecker())->findPossibleDuplicates('Usman Tariq', '03451112222', '1985-07-07');

        $this->assertCount(0, $matches);
    }
}
