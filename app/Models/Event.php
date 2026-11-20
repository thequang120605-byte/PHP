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
            'is_finalized' => 'boolean',
            'finalized_at' => 'datetime',
        ];
    }

    public function getPosterUrlAttribute(): string
    {
        if ($this->poster_path) {
            return asset('storage/'.$this->poster_path);
        }

        $title = mb_strtolower($this->title ?? '');

        // 1. Phân loại hình ảnh đặc thù theo đúng chủ đề / chuyên đề của hoạt động
        if (str_contains($title, 'trí tuệ nhân tạo') || str_contains($title, 'llm') || str_contains($title, 'ai &')) {
            // AI, Deep Learning, Mạng nơ-ron số
            return 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'hackathon') || str_contains($title, 'lập trình')) {
            // Lập trình viên thi đấu, code sprint
            return 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'an toàn thông tin') || str_contains($title, 'tác chiến') || str_contains($title, 'không gian mạng')) {
            // An toàn thông tin, Cyber Security Shield
            return 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'nghiên cứu khoa học') || str_contains($title, 'công bố quốc tế') || str_contains($title, 'tọa đàm')) {
            // Hội thảo nghiên cứu khoa học, giảng đường lớn
            return 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'cv') || str_contains($title, 'ats') || str_contains($title, 'hồ sơ')) {
            // Bàn làm việc tuyển dụng, chuẩn bị CV phỏng vấn
            return 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'khởi nghiệp') || str_contains($title, 'startup') || str_contains($title, 'đổi mới sáng tạo')) {
            // Thuyết trình ý tưởng khởi nghiệp sân khấu
            return 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'thuyết trình') || str_contains($title, 'sân khấu') || str_contains($title, 'kỹ năng')) {
            // Diễn thuyết trước khán phòng, micro sân khấu
            return 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'hiến máu') || str_contains($title, 'giọt hồng')) {
            // Trái tim tình nguyện, giọt máu hồng nhân ái
            return 'https://images.unsplash.com/photo-1615461066841-6116e61058f4?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'mùa hè xanh') || str_contains($title, 'tình nguyện')) {
            // Đoàn thanh niên áo xanh tình nguyện gắn kết
            return 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'chủ nhật xanh') || str_contains($title, 'môi trường') || str_contains($title, 'xanh - sạch')) {
            // Ươm mầm cây xanh, bảo vệ môi trường học đường
            return 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'bóng đá') || str_contains($title, 'cup') || str_contains($title, 'futsal')) {
            // Sân cỏ bóng đá thể thao sinh viên
            return 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'chạy') || str_contains($title, 'run') || str_contains($title, 'marathon')) {
            // Giải chạy phong trào sinh viên năng động
            return 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'âm nhạc') || str_contains($title, '5 tốt') || str_contains($title, 'văn nghệ')) {
            // Đêm hội âm nhạc ánh sáng sân khấu rực rỡ
            return 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($title, 'job fair') || str_contains($title, 'việc làm') || str_contains($title, 'doanh nghiệp')) {
            // Ngày hội việc làm và triển lãm doanh nghiệp
            return 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=800&q=80';
        }

        // 2. Bộ sưu tập ảnh phong phú quay vòng theo ID sự kiện (không bao giờ trùng nhau)
        $gallery = [
            'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1515187029135-18ee286d815b?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=800&q=80',
        ];

        return $gallery[$this->id % count($gallery)];
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
