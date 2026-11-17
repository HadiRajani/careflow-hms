<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves role checks happen on the SERVER: each role opens each URL directly
 * (no menu involved) and gets 200 (allowed) or 403 (forbidden).
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_desk_access(): void
    {
        $user = User::factory()->create(['role' => 'front_desk']);

        $this->actingAs($user)->get('/patients')->assertStatus(200);
        $this->actingAs($user)->get('/triage')->assertStatus(403);
        $this->actingAs($user)->get('/dashboard')->assertStatus(403);
        $this->actingAs($user)->get('/audit')->assertStatus(403);
    }

    public function test_nurse_access(): void
    {
        $user = User::factory()->create(['role' => 'nurse']);

        $this->actingAs($user)->get('/patients')->assertStatus(200);
        $this->actingAs($user)->get('/triage')->assertStatus(200);
        $this->actingAs($user)->get('/dashboard')->assertStatus(403);
        $this->actingAs($user)->get('/audit')->assertStatus(403);
    }

    public function test_quality_manager_access(): void
    {
        $user = User::factory()->create(['role' => 'quality_manager']);

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
        $this->actingAs($user)->get('/audit')->assertStatus(200);
        $this->actingAs($user)->get('/patients')->assertStatus(403);
        $this->actingAs($user)->get('/triage')->assertStatus(403);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/patients')->assertRedirect('/login');
        $this->get('/triage')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/audit')->assertRedirect('/login');
    }

    public function test_logged_in_user_opening_login_page_goes_to_own_home_page(): void
    {
        // Bug found while reading Laravel's source: by default the 'guest' middleware
        // sent every logged-in user to /dashboard, so a nurse got a 403.
        $nurse = User::factory()->create(['role' => 'nurse']);
        $this->actingAs($nurse)->get('/login')->assertRedirect('/triage');

        $frontDesk = User::factory()->create(['role' => 'front_desk']);
        $this->actingAs($frontDesk)->get('/login')->assertRedirect('/patients');
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'Intruder', 'email' => 'x@y.test', 'password' => 'secret123'])
            ->assertNotFound();
    }

    public function test_each_role_lands_on_its_own_page_after_login(): void
    {
        $nurse = User::factory()->create(['role' => 'nurse']);
        $this->post('/login', ['email' => $nurse->email, 'password' => 'password'])->assertRedirect('/triage');
        $this->post('/logout');

        $manager = User::factory()->create(['role' => 'quality_manager']);
        $this->post('/login', ['email' => $manager->email, 'password' => 'password'])->assertRedirect('/dashboard');
    }
}
