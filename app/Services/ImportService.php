<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\ImportedFile;
use App\Models\Registration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportService
{
    public function previewCsv(UploadedFile $file, Event $event): array
    {
        $h = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($h, 0, ',', '"', '');
        if (! $header) {
            $this->fail('File CSV không có tiêu đề.');
        }
        $header = array_map(fn ($x) => mb_strtolower(trim(str_replace("\xEF\xBB\xBF", '', $x))), $header);
        $map = [];
        foreach ($header as $i => $x) {
            if (in_array($x, ['mssv', 'student_code', 'mã sinh viên'])) {
                $map['code'] = $i;
            }if (in_array($x, ['source_time', 'timestamp', 'thời gian', 'time'])) {
                $map['time'] = $i;
            }
        }
        if (! isset($map['code'],$map['time'])) {
            $this->fail('Cần cột mssv và source_time, thời gian dạng YYYY-MM-DD HH:MM:SS theo giờ Việt Nam.');
        }
        $rows = [];
        $seen = [];
        $number = 1;
        while (($line = fgetcsv($h, 0, ',', '"', '')) !== false) {
            $number++;
            if (! array_filter($line)) {
                continue;
            }if (count($rows) >= 1000) {
                $this->fail('Tối đa 1000 dòng mỗi file.');
            }$code = trim($line[$map['code']] ?? '');
            $time = trim($line[$map['time']] ?? '');
            $u = User::where('student_code', $code)->first();
            $note = 'Hợp lệ';
            $stamp = null;
            try {
                $stamp = Carbon::createFromFormat('!Y-m-d H:i:s', $time, 'Asia/Ho_Chi_Minh')->utc();
                if ($stamp->copy()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s') !== $time) {
                    $stamp = null;
                }
            } catch (\Throwable) {
                $stamp = null;
            }
            if (! $u || ! $u->is_active || $u->role !== 'student') {
                $note = 'MSSV chưa có tài khoản sinh viên hoạt động; không tự tạo mật khẩu chung.';
            } elseif (isset($seen[$code]) || Registration::where('event_id', $event->id)->where('user_id', $u->id)->exists()) {
                $note = 'Đã có trong hệ thống hoặc trùng file.';
            } elseif (! $stamp || $stamp->lt($event->opens_at) || $stamp->gte($event->closes_at) || $stamp->gt(now())) {
                $note = 'Thời gian nguồn sai, ngoài hạn hoặc ở tương lai.';
            }
            $seen[$code] = true;
            $rows[] = ['row_num' => $number, 'student_code' => $code, 'student_name' => $u?->name ?? '', 'class_name' => $u?->class_name ?? '', 'email' => $u?->email ?? '', 'source_time' => $time, 'is_valid' => $note === 'Hợp lệ', 'note' => $note];
        }fclose($h);

        return ['total' => count($rows), 'valid_count' => count(array_filter($rows, fn ($r) => $r['is_valid'])), 'duplicate_count' => count(array_filter($rows, fn ($r) => ! $r['is_valid'])), 'rows' => $rows];
    }

    public function commitRegistrations(Event $event, array $ignoredRows, UploadedFile $file, User $actor): ImportedFile
    {
        abort_unless($actor->role === 'admin' && $actor->is_active, 403);
        $path = $file->store('imports', 'local');
        try {
            return DB::transaction(function () use ($event, $file, $actor, $path) {
                $e = Event::lockForUpdate()->findOrFail($event->id);
                if ($e->status !== 'published' || now()->gte($e->starts_at)) {
                    $this->fail('Chỉ nhập đăng ký trước giờ tổ chức của sự kiện đã công bố.');
                }
                $preview = $this->previewCsv($file, $e);
                $rows = array_values(array_filter($preview['rows'], fn ($r) => $r['is_valid']));
                $available = $e->capacity - $e->confirmed()->count();
                if (count($rows) > $available) {
                    $this->fail('Không đủ chỗ. Giảm số dòng hoặc điều chỉnh sức chứa có kiểm soát.');
                }
                foreach ($rows as $row) {
                    $u = User::where('student_code', $row['student_code'])->firstOrFail();
                    $snapshot = ['name' => $u->name, 'student_code' => $u->student_code, 'class_name' => $u->class_name, 'email' => $u->email, 'source' => 'csv_import', 'event_title' => $e->title, 'opens_at' => $e->opens_at->toIso8601String(), 'closes_at' => $e->closes_at->toIso8601String(), 'proposed_points' => $e->proposed_points, 'source_time' => $row['source_time'], 'imported_at' => now()->toIso8601String()];
                    Registration::create(['event_id' => $e->id, 'user_id' => $u->id, 'reference' => (string) Str::uuid(), 'qr_token' => null, 'status' => 'confirmed', 'attendance_status' => 'registered', 'submitted_at' => Carbon::createFromFormat('!Y-m-d H:i:s', $row['source_time'], 'Asia/Ho_Chi_Minh')->utc(), 'snapshot' => $snapshot]);
                }
                $imp = ImportedFile::create(['user_id' => $actor->id, 'original_name' => $file->getClientOriginalName(), 'stored_path' => $path, 'type' => 'registrations', 'event_id' => $e->id, 'total_rows' => $preview['total'], 'success_rows' => count($rows), 'duplicate_rows' => $preview['total'] - count($rows), 'imported_at' => now(), 'summary_json' => ['event_title' => $e->title, 'success' => count($rows), 'duplicate' => $preview['total'] - count($rows), 'source_time_zone' => 'Asia/Ho_Chi_Minh']]);
                AuditLog::create(['actor_id' => $actor->id, 'action' => 'import.registrations', 'entity_type' => 'event', 'entity_id' => $e->id, 'payload' => ['import_id' => $imp->id, 'success' => count($rows)]]);

                return $imp;
            }, 3);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    private function fail(string $m): never
    {
        throw ValidationException::withMessages(['import' => $m]);
    }
}
