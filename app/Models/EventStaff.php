<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventStaff extends Model
{
    protected $table = 'event_staffs';
    protected $guarded = ['id'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'scanner' => 'Điểm danh (QR/Giấy)',
            'approver' => 'Duyệt điểm rèn luyện',
            'manager' => 'Quản lý toàn diện sự kiện',
            default => $this->role,
        };
    }
}
