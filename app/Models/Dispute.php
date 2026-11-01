<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'missing_attendance' => 'Thiếu tên điểm danh (Đã đăng ký)',
            'missing_checkout' => 'Thiếu giờ ra (Đã tham dự)',
            'missing_points' => 'Đủ điều kiện nhưng chưa nhận điểm',
            'incorrect_points' => 'Điểm bị ghi nhận sai',
            default => 'Phản hồi khác',
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'pending' => 'Chờ xử lý',
            'in_review' => 'Đang xác minh',
            'resolved' => 'Chấp nhận',
            'rejected' => 'Từ chối',
            default => $this->status,
        };
    }
}
