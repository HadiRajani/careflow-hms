<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo account per role. Password comes from the DEMO_PASSWORD env var,
 * so no credential is hard-coded in the repository.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('DEMO_PASSWORD');

        if (blank($password)) {
            throw new \RuntimeException('Set DEMO_PASSWORD in .env before seeding demo users.');
        }

        $accounts = [
            ['Sana Front Desk', 'frontdesk@careflow.test', Role::FrontDesk],
            ['Nurse Amina', 'nurse@careflow.test', Role::Nurse],
            ['Dr. Farah (Quality)', 'quality@careflow.test', Role::QualityManager],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->name = $name;
            $user->password = $password;          // hashed automatically by the 'hashed' cast
            $user->role = $role;                  // set explicitly: role is not mass-assignable
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
