<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Criteria extends Model
{
    protected $table = 'criterias';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'max_points' => 'integer',
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
