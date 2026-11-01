<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'dispute_closes_at' => 'datetime',
            'capacity' => 'integer',
            'proposed_points' => 'integer',
            'min_minutes_between' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function confirmed()
    {
        return $this->registrations()->where('status', 'confirmed');
    }

    public function waitlist()
    {
        return $this->registrations()->where('status', 'waitlist')->orderBy('waitlist_position', 'asc');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function paperAttendances()
    {
        return $this->hasMany(PaperAttendance::class);
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }

    public function staffs()
    {
        return $this->hasMany(EventStaff::class);
    }

    public function studentPoints()
    {
        return $this->hasMany(StudentPoint::class);
    }

    public function isOpen(): bool
    {
        $n = now();

        return $this->status === 'published' && $n->gte($this->opens_at) && $n->lt($this->closes_at);
    }

    public function isDisputeOpen(): bool
    {
        if (! $this->dispute_closes_at) {
            // Mặc định cho phép phản hồi trong vòng 7 ngày sau khi kết thúc sự kiện
            return now()->lte($this->ends_at->copy()->addDays(7));
        }

        return now()->lte($this->dispute_closes_at);
    }

    public function hasStaffRole(User $user, array|string $roles): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $roles = (array) $roles;
        // Manager có toàn quyền
        if (! in_array('manager', $roles)) {
            $roles[] = 'manager';
        }

        return $this->staffs()->where('user_id', $user->id)->whereIn('role', $roles)->exists();
    }

    public function label(): string
    {
        return match (true) {
            $this->status === 'draft' => 'Bản nháp',
            $this->status === 'cancelled' => 'Đã hủy',
            now()->lt($this->opens_at) => 'Sắp mở',
            now()->gte($this->closes_at) => 'Đã đóng',
            default => 'Đang mở đăng ký'
        };
    }
}
