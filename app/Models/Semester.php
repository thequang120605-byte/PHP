<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function points()
    {
        return $this->hasMany(StudentPoint::class);
    }
}
