<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = ['role' => 'student', 'is_active' => true];

    protected $fillable = ['name', 'email', 'password', 'student_code', 'class_name', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function studentPoints()
    {
        return $this->hasMany(StudentPoint::class, 'student_id');
    }

    public function staffAssignments()
    {
        return $this->hasMany(EventStaff::class);
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasEventStaffRole(int $eventId, array|string $roles): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $roles = (array) $roles;
        if (! in_array('manager', $roles)) {
            $roles[] = 'manager';
        }

        return $this->staffAssignments()
            ->where('event_id', $eventId)
            ->whereIn('role', $roles)
            ->exists();
    }
}
