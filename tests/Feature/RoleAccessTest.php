<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Proves role checks are enforced on the SERVER: each role hitting a URL directly,
 * with no menu link involved, gets 200 or 403 as the access map says.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function accessMatrix(): array
    {
        // [role, url, expected HTTP status]
        return [
            'front desk can open patients'      => [Role::FrontDesk, '/patients', 200],
            'front desk cannot triage'          => [Role::FrontDesk, '/triage', 403],
            'front desk cannot see dashboard'   => [Role::FrontDesk, '/dashboard', 403],
            'front desk cannot see audit log'   => [Role::FrontDesk, '/audit', 403],

            'nurse can open patients'           => [Role::Nurse, '/patients', 200],
            'nurse can open triage queue'       => [Role::Nurse, '/triage', 200],
            'nurse cannot see dashboard'        => [Role::Nurse, '/dashboard', 403],
            'nurse cannot see audit log'        => [Role::Nurse, '/audit', 403],

            'quality manager sees dashboard'    => [Role::QualityManager, '/dashboard', 200],
            'quality manager sees audit log'    => [Role::QualityManager, '/audit', 200],
            'quality manager cannot triage'     => [Role::QualityManager, '/triage', 403],
            'quality manager cannot register'   => [Role::QualityManager, '/patients', 403],
        ];
    }

    #[DataProvider('accessMatrix')]
    public function test_role_access_is_enforced_server_side(Role $role, string $url, int $expected): void
    {
        $user = User::factory()->role($role)->create();

        $this->actingAs($user)->get($url)->assertStatus($expected);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/patients', '/triage', '/dashboard', '/audit'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Intruder', 'email' => 'x@y.test',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertNotFound();
    }

    public function test_each_role_lands_on_its_home_after_login(): void
    {
        $nurse = User::factory()->role(Role::Nurse)->create();

        $this->post('/login', ['email' => $nurse->email, 'password' => 'password'])
            ->assertRedirect('/triage');
    }
}
