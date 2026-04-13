<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminRequestController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\User\Auth\LoginController as UserLoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 一般ユーザー
// ログイン
Route::get('/login', [UserLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserLoginController::class, 'login']);
Route::post('/logout', [UserLoginController::class, 'logout'])->name('logout');

Route::middleware('auth', 'verified')->group(function () {
    // 画面表示
    Route::get('/attendance', [AttendanceController::class, 'index'])
    ->name('attendance.index');

    // 打刻
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('clock.in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('clock.out');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('break.start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('break.end');

    // 勤怠
    Route::get('/attendance/list', [StaffController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [StaffController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/detail/{id}', [StaffController::class, 'update'])->name('attendance.update');

    // 申請
    Route::get('/stamp_correction_request/list', [RequestController::class, 'index'])->name('request.index');

});

// 管理者
Route::prefix('admin')->name('admin.')->group(function () {

    // ログイン
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);

    Route::middleware('auth:admin')->group(function () {
        // ログアウト
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

        // 日別勤怠
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
        Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

        // スタッフ
        Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.list');
        Route::get('/attendance/staff/{id}', [AdminStaffController::class, 'show'])->name('staff.show');
        Route::get('/attendance/staff/{id}/csv', [AdminStaffController::class, 'csv'])->name('staff.csv');

        // 申請
        Route::get('/stamp_correction_request/list', [AdminRequestController::class, 'index'])->name('request.index');
        Route::get('/stamp_correction_request/approve/{id}', [AdminRequestController::class, 'show'])->name('request.show');
        Route::post('/stamp_correction_request/approve/{id}', [AdminRequestController::class, 'approve'])->name('request.approve');
    });
});

