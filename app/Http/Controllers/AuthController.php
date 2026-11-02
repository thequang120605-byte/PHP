<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:200']);
        $key = Str::lower($d['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Vui lòng chờ '.RateLimiter::availableIn($key).' giây.']);
        }if (! Auth::attempt(['email' => $d['email'], 'password' => $d['password'], 'is_active' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Sai thông tin đăng nhập hoặc tài khoản đã khóa.']);
        }RateLimiter::clear($key);
        $r->session()->regenerate();

        return redirect()->intended('/');
    }

    public function register(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:100', 'student_code' => ['required', 'regex:/^[0-9]{8,15}$/', 'unique:users,student_code'], 'class_name' => 'required|string|max:50', 'email' => 'required|email|max:255|unique:users,email', 'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()]]);
        $d['role'] = 'student';
        $d['is_active'] = true;
        $u = User::create($d);
        Auth::login($u);
        $r->session()->regenerate();

        return redirect('/')->with('success', 'Đã tạo tài khoản sinh viên.');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }

    public function profile(Request $r)
    {
        $u = $r->user();
        $d = $r->validate([
            'name' => 'required|string|max:100',
            'class_name' => 'required|string|max:50',
        ]);
        $u->update($d);

        return back()->with('success', 'Đã cập nhật hồ sơ (Họ tên và Lớp). MSSV là định danh cố định không thể tự ý sửa đổi.');
    }
}
