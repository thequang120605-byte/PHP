<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPoint extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'rule_snapshot' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function adjustments()
    {
        return $this->hasMany(PointAdjustment::class, 'student_point_id');
    }
}
