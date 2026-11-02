<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventStaffController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\StaffWorkspaceController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('active')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('home');
    Route::get('/events/{event}', [EventController::class, 'show']);

    Route::middleware('guest')->group(function () {
        Route::view('/login', 'login')->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:15,1');
        Route::view('/register', 'register');
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::view('/profile', 'profile');
        Route::put('/profile', [AuthController::class, 'profile']);

        // Đăng ký và vé QR
        Route::get('/my-registrations', [EventController::class, 'mine']);
        Route::post('/events/{event}/register', [EventController::class, 'register'])->middleware('throttle:20,1');
        Route::get('/registrations/{registration}', [EventController::class, 'receipt']);
        Route::post('/registrations/{registration}/cancel', [EventController::class, 'cancel'])->middleware('throttle:20,1');

        // Điểm rèn luyện của sinh viên
        Route::get('/my-points', [PointController::class, 'myPoints']);

        // Phản hồi sai sót & khiếu nại (Sinh viên)
        Route::get('/disputes/{dispute}/evidence', [DisputeController::class, 'evidence']);
        Route::get('/my-disputes', [DisputeController::class, 'myDisputes']);
        Route::get('/events/{event}/disputes/create', [DisputeController::class, 'create']);
        Route::post('/events/{event}/disputes', [DisputeController::class, 'store']);

        // Điểm danh (Staff hoặc Admin)
        Route::get('/events/{event}/attendance/scan', [AttendanceController::class, 'showScanPage']);
        Route::post('/events/{event}/attendance/open', [AttendanceController::class, 'open'])->middleware('throttle:10,1');
        Route::post('/events/{event}/attendance/close', [AttendanceController::class, 'close'])->middleware('throttle:10,1');
        Route::get('/events/{event}/attendance/qr', [AttendanceController::class, 'qr'])->middleware('throttle:90,1');
        Route::get('/events/{event}/attendance/confirm', [AttendanceController::class, 'confirm']);
        Route::post('/events/{event}/attendance/complete', [AttendanceController::class, 'complete'])->middleware('throttle:end-attendance');
        Route::get('/attendance/paper/{paper}/file', [AttendanceController::class, 'paperFile']);
        Route::get('/events/{event}/attendance/paper', [AttendanceController::class, 'showPaperForm']);
        Route::post('/events/{event}/attendance/paper', [AttendanceController::class, 'storePaper']);
        Route::post('/events/{event}/attendance/reconcile', [AttendanceController::class, 'reconcile']);

        // Duyệt điểm rèn luyện (Approver hoặc Admin)
        Route::get('/events/{event}/points/review', [PointController::class, 'showReviewPage']);
        Route::post('/events/{event}/points/approve', [PointController::class, 'approvePoints']);
        Route::post('/points/{point}/adjust', [PointController::class, 'adjustPoint']);

        // Không gian làm việc BTC & Chốt kết quả sự kiện
        Route::get('/staff/events', [StaffWorkspaceController::class, 'index']);
        Route::post('/events/{event}/finalize', [StaffWorkspaceController::class, 'finalize']);

        // Trung tâm thông báo nghiệp vụ
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    // Quản trị dành cho Admin
    Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [AdminController::class, 'index']);

        // Quản lý Sự kiện
        Route::get('/events/create', [AdminController::class, 'form']);
        Route::post('/events', [AdminController::class, 'save']);
        Route::get('/events/{event}/edit', [AdminController::class, 'form']);
        Route::put('/events/{event}', [AdminController::class, 'save']);
        Route::delete('/events/{event}', [AdminController::class, 'delete']);
        Route::get('/events/{event}/participants', [AdminController::class, 'participants']);
        Route::get('/events/{event}/export', [AdminController::class, 'export'])->middleware('throttle:6,1');

        // Danh mục
        Route::get('/categories', [AdminController::class, 'categories']);
        Route::post('/categories', [AdminController::class, 'categorySave']);
        Route::put('/categories/{category}', [AdminController::class, 'categorySave']);
        Route::delete('/categories/{category}', [AdminController::class, 'categoryDelete']);

        // Học kỳ & Tiêu chí rèn luyện
        Route::get('/semesters', [PointController::class, 'semesters']);
        Route::post('/semesters', [PointController::class, 'saveSemester']);
        Route::put('/semesters/{semester}', [PointController::class, 'saveSemester']);
        Route::get('/criterias', [PointController::class, 'criterias']);
        Route::post('/criterias', [PointController::class, 'saveCriteria']);
        Route::put('/criterias/{criteria}', [PointController::class, 'saveCriteria']);

        // Phân công nhân sự theo sự kiện
        Route::get('/events/{event}/staffs', [EventStaffController::class, 'index']);
        Route::post('/events/{event}/staffs', [EventStaffController::class, 'assign']);
        Route::delete('/event-staffs/{staff}', [EventStaffController::class, 'remove']);

        // Import Excel / CSV & Preview
        Route::get('/events/{event}/import', [ImportExportController::class, 'showImportPage']);
        Route::post('/events/{event}/import/preview', [ImportExportController::class, 'previewImport']);
        Route::post('/events/{event}/import/commit', [ImportExportController::class, 'commitImport']);
        Route::get('/events/{event}/export-reconciliation', [ImportExportController::class, 'exportReconciliation'])->middleware('throttle:6,1');

        // Phản hồi sai sót & Đối soát
        Route::get('/disputes', [DisputeController::class, 'adminIndex']);
        Route::post('/disputes/{dispute}/process', [DisputeController::class, 'process']);

        // Quản lý người dùng & Khóa tài khoản
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive']);

        // Báo cáo & Thống kê
        Route::get('/reports', [ImportExportController::class, 'reports']);
    });
});
