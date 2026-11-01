<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaperAttendance extends Model
{
    protected $guarded = ['id'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'paper_sheet_id');
    }
}
