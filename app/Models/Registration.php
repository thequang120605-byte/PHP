<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['qr_token'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_waitlist' => 'boolean',
            'waitlist_position' => 'integer',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function completion()
    {
        return $this->hasOne(EventCompletion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }

    public function attendanceLabel(): string
    {
        return match ($this->attendance_status) {
            'attended_complete' => 'Hoàn thành đủ điều kiện',
            'attended_incomplete' => 'Thiếu giờ ra (Cần xác minh)',
            'needs_verification' => 'Cần xác minh',
            'absent' => 'Vắng mặt',
            default => 'Chưa điểm danh',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'confirmed' => 'Đã xác nhận',
            'waitlist' => 'Danh sách chờ #'.$this->waitlist_position,
            'cancelled' => 'Đã hủy',
            default => $this->status,
        };
    }
}
