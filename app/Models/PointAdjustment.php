<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointAdjustment extends Model
{
    protected $guarded = ['id'];

    public function studentPoint()
    {
        return $this->belongsTo(StudentPoint::class, 'student_point_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
