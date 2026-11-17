<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A staff member who can log in.
 *
 * The `role` column is plain text. There are only three possible values:
 *   'front_desk'       -> registers patients and opens visits
 *   'nurse'            -> registers patients, opens visits and does triage
 *   'quality_manager'  -> sees the dashboard and audit log
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Fields a form is allowed to fill. 'role' is NOT here on purpose:
    // nobody can make themselves a quality manager by adding role=... to a form.
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',   // saving a password automatically stores a bcrypt hash
        ];
    }

    /**
     * The page each role should see right after login.
     */
    public function homePage(): string
    {
        if ($this->role === 'nurse') {
            return '/triage';
        }

        if ($this->role === 'quality_manager') {
            return '/dashboard';
        }

        return '/patients';   // front_desk
    }

    /**
     * Readable role name for the screen, e.g. 'front_desk' -> 'Front Desk'.
     */
    public function roleName(): string
    {
        return ucwords(str_replace('_', ' ', $this->role));
    }
}
