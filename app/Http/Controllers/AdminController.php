<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Criteria;
use App\Models\Dispute;
use App\Models\Event;
use App\Models\EventCompletion;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin', [
            'events' => Event::with(['category', 'criteria', 'semester'])->withCount('confirmed')->latest()->paginate(12),
            'students' => User::where('role', 'student')->count(),
            'registrations' => Registration::where('status', 'confirmed')->count(),
            'pendingDisputes' => Dispute::where('status', 'pending')->count(),
            'totalPoints' => StudentPoint::where('status', 'approved')->sum('points'),
            'logs' => AuditLog::with('actor')->latest('id')->limit(12)->get(),
        ]);
    }

    public function form(?Event $event = null)
    {
        return view('event-form', [
            'e' => $event ?? new Event([
                'capacity' => 100,
                'proposed_points' => 0,
                'status' => 'draft',
                'attendance_rule' => 'end_only',
                'min_minutes_between' => 0,
            ]),
            'categories' => Category::orderBy('name')->get(),
            'semesters' => Semester::orderBy('id', 'desc')->get(),
            'criterias' => Criteria::orderBy('code')->get(),
        ]);
    }

    public function save(EventRequest $r, ?Event $event = null)
    {
        $d = $r->eventData();
        $path = null;
        $old = null;
        if ($r->hasFile('poster')) {
            $d['poster_path'] = $path = $r->file('poster')->store('posters', 'public');
        }

        try {
            $e = DB::transaction(function () use ($d, $r, $event, &$old) {
                $e = $event ? Event::lockForUpdate()->findOrFail($event->id) : new Event(['created_by' => $r->user()->id]);
                $before = $e->exists ? $e->toArray() : [];
                if ($e->exists && (AttendanceSession::where('event_id', $e->id)->exists() || $e->studentPoints()->exists() || EventCompletion::where('event_id', $e->id)->exists())) {
                    foreach (['semester_id', 'criteria_id', 'proposed_points', 'ends_at', 'attendance_rule'] as $field) {
                        if (array_key_exists($field, $d) && ($field === 'ends_at' ? ! Carbon::parse($d[$field])->equalTo($e->$field) : (string) $d[$field] !== (string) $e->$field)) {
                            throw ValidationException::withMessages(['event' => 'Đã mở điểm danh hoặc ghi nhận điểm; không đổi học kỳ, tiêu chí, điểm hay giờ kết thúc.']);
                        }
                    }
                }

                $old = $e->poster_path;
                if ($e->exists && $d['capacity'] < $e->confirmed()->count()) {
                    throw ValidationException::withMessages(['capacity' => 'Không được giảm dưới số người đã được xác nhận.']);
                }
                $e->fill($d);
                $e->save();
                AuditLog::create([
                    'actor_id' => $r->user()->id,
                    'action' => $event ? 'event.updated' : 'event.created',
                    'entity_type' => 'event',
                    'entity_id' => $e->id,
                    'payload' => ['before' => $before, 'after' => $e->toArray()],
                ]);

                return $e;
            }, 3);
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $error;
        }

        if ($path && $old) {
            Storage::disk('public')->delete($old);
        }

        return redirect('/admin/events/'.$e->id.'/edit')->with('success', 'Đã lưu sự kiện và lịch sử thay đổi.');
    }

    public function delete(Event $event, Request $r)
    {
        $path = null;
        DB::transaction(function () use ($event, $r, &$path) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            if ($e->registrations()->exists()) {
                throw ValidationException::withMessages(['event' => 'Đã có lịch sử đăng ký. Hãy chuyển sự kiện sang Đã hủy.']);
            }
            $path = $e->poster_path;
            AuditLog::create([
                'actor_id' => $r->user()->id,
                'action' => 'event.deleted',
                'entity_type' => 'event',
                'entity_id' => $e->id,
                'payload' => ['before' => $e->toArray()],
            ]);
            $e->delete();
        });

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Đã xóa sự kiện.');
    }

    public function participants(Event $event)
    {
        $registrations = $event->registrations()->with(['user', 'completion'])->orderBy('submitted_at')->paginate(25);
        $recordedPoints = StudentPoint::where('event_id', $event->id)->whereIn('student_id', $registrations->pluck('user_id'))->get()->keyBy('student_id');

        return view('participants', ['event' => $event->load(['category', 'semester', 'criteria']), 'registrations' => $registrations, 'recordedPoints' => $recordedPoints]);
    }

    public function export(Event $event)
    {
        return response()->streamDownload(function () use ($event) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Mã phiếu', 'MSSV', 'Họ tên lúc đăng ký', 'Lớp', 'Email', 'Thời gian VN', 'Trạng thái', 'Điểm danh', 'Giờ vào', 'Giờ ra'], ',', '"', '');
            foreach ($event->registrations()->with('user')->orderBy('id')->lazyById(500) as $r) {
                $s = $r->snapshot;
                $row = [
                    $r->reference,
                    $s['student_code'] ?? ($r->user->student_code ?? ''),
                    $s['name'] ?? ($r->user->name ?? ''),
                    $s['class_name'] ?? ($r->user->class_name ?? ''),
                    $s['email'] ?? ($r->user->email ?? ''),
                    $r->submitted_at ? $r->submitted_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : '',
                    $r->status,
                    $r->attendance_status,
                    $r->check_in_at ? $r->check_in_at->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') : '',
                    $r->check_out_at ? $r->check_out_at->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') : '',
                ];
                $row = array_map(fn ($v) => preg_match('/^[\s]*[=+@\-]/u', (string) $v) ? "'".$v : $v, $row);
                fputcsv($f, $row, ',', '"', '');
            }
            fclose($f);
        }, 'su-kien-'.$event->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function categories()
    {
        return view('categories', ['categories' => Category::withCount('events')->get()]);
    }

    public function categorySave(Request $r, ?Category $category = null)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:100', Rule::unique('categories')->ignore($category?->id)]]);
        if ($category) {
            $category->update($d);
        } else {
            Category::create($d);
        }

        return back()->with('success', 'Đã lưu danh mục.');
    }

    public function categoryDelete(Category $category)
    {
        try {
            $category->delete();
        } catch (QueryException $e) {
            if ($category->events()->exists()) {
                throw ValidationException::withMessages(['category' => 'Danh mục đang được dùng.']);
            }
            throw $e;
        }

        return back()->with('success', 'Đã xóa danh mục.');
    }
}
