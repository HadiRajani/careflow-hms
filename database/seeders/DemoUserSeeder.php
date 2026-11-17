<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates one demo login per role.
 * The password comes from DEMO_PASSWORD in .env (via config/app.php), so it is not written in the code.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('app.demo_password');   // from DEMO_PASSWORD in .env

        if (empty($password)) {
            throw new \RuntimeException('Set DEMO_PASSWORD in .env before seeding demo users.');
        }

        $accounts = [
            // name                   email                       role
            ['Sana Front Desk',     'frontdesk@careflow.test', 'front_desk'],
            ['Nurse Amina',         'nurse@careflow.test',     'nurse'],
            ['Dr. Farah (Quality)', 'quality@careflow.test',   'quality_manager'],
        ];

        foreach ($accounts as $account) {
            // Find by email, or start a new user (so running the seeder twice never duplicates).
            $user = User::firstOrNew(['email' => $account[1]]);
            $user->name = $account[0];
            $user->password = $password;     // hashed automatically
            $user->role = $account[2];       // set directly, because role is not in $fillable
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
