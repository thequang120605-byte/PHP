<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    public function index(Request $r)
    {
        $q = trim($r->input('q', ''));
        $role = $r->input('role');
        $status = $r->input('status');

        $query = User::latest('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('student_code', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('class_name', 'like', "%{$q}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }

        $users = $query->paginate(20);

        return view('admin.users', compact('users', 'q', 'role', 'status'));
    }

    public function toggleActive(User $user, Request $r)
    {
        if ($user->id === $r->user()->id) {
            return back()->withErrors(['user' => 'Không thể tự khóa tài khoản của chính mình.']);
        }

        $newStatus = DB::transaction(function () use ($user, $r) {
            $lockedUser = User::lockForUpdate()->findOrFail($user->id);
            if ($lockedUser->id === $r->user()->id) {
                return false;
            }

            // Hỗ trợ truyền trạng thái đích xác định (target_status) hoặc đảo trạng thái nguyên tử
            $targetStatus = $r->has('target_status')
                ? filter_var($r->input('target_status'), FILTER_VALIDATE_BOOLEAN)
                : ! $lockedUser->is_active;

            $lockedUser->update(['is_active' => $targetStatus]);

            AuditLog::create([
                'actor_id' => $r->user()->id,
                'action' => $targetStatus ? 'user.unlocked' : 'user.locked',
                'entity_type' => 'user',
                'entity_id' => $lockedUser->id,
                'payload' => [
                    'target_email' => $lockedUser->email,
                    'target_student_code' => $lockedUser->student_code,
                    'is_active' => $targetStatus,
                ],
            ]);

            return $targetStatus;
        });

        $msg = $newStatus ? "Đã mở khóa tài khoản {$user->name}." : "Đã khóa tài khoản {$user->name}.";

        return back()->with('success', $msg);
    }
}
