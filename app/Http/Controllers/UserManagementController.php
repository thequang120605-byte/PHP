<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

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

        $oldStatus = $user->is_active;
        $newStatus = ! $oldStatus;
        $user->update(['is_active' => $newStatus]);

        AuditLog::create([
            'actor_id' => $r->user()->id,
            'action' => $newStatus ? 'user.unlocked' : 'user.locked',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'payload' => [
                'target_email' => $user->email,
                'target_student_code' => $user->student_code,
                'is_active' => $newStatus,
            ],
        ]);

        $msg = $newStatus ? "Đã mở khóa tài khoản {$user->name}." : "Đã khóa tài khoản {$user->name}.";

        return back()->with('success', $msg);
    }
}
