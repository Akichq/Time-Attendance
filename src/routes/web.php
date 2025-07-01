<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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

Route::get('/', function () {
    return view('welcome');
});

// 認証ルート（Fortifyの代わりにカスタムコントローラーを使用）
Route::middleware('guest')->group(function () {
    // 会員登録
    Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])
        ->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

    // ログイン
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])
        ->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
});

// ログアウト
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// メール認証ルート
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('attendance.index');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', '認証メールを再送しました！');
    })->middleware(['throttle:6,1'])->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // ログイン・ログアウトはミドルウェア外
    Route::get('/login', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'create'])
        ->middleware('guest:admin')
        ->name('login');
    Route::post('/login', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'store'])
        ->middleware('guest:admin');
    Route::post('/logout', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');

    // 管理者用ページはauth:adminでグループ化
    Route::middleware('auth:admin')->group(function () {
        Route::get('/attendance/list', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('attendance.list');
        Route::get('/attendance/{attendance}', [\App\Http\Controllers\Admin\AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/attendance/staff/{id}', [\App\Http\Controllers\Admin\AttendanceController::class, 'staffAttendance'])->name('attendance.staff');
        Route::get('/attendance/staff/{id}/csv', [\App\Http\Controllers\Admin\AttendanceController::class, 'staffAttendanceCsv'])->name('attendance.staff.csv');

        Route::patch('/attendance/{attendance}', [\App\Http\Controllers\Admin\AttendanceController::class, 'update'])->name('attendance.update');
        Route::get('/stamp_correction_request/approve/{correction}', [\App\Http\Controllers\AttendanceCorrectionController::class, 'show'])->name('admin.correction.approve');
        Route::patch('/stamp_correction_request/approve/{correction}', [\App\Http\Controllers\AttendanceCorrectionController::class, 'approve'])->name('correction.approve');
        Route::patch('/correction/{correction}/reject', [\App\Http\Controllers\AttendanceCorrectionController::class, 'reject'])->name('correction.reject');
        Route::get('/staff/list', [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('staff.list');
    });
});

// 勤怠管理（一般ユーザー用）
Route::middleware(['auth', 'verified'])->group(function () {
    // 勤怠打刻画面
    Route::get('/attendance', [App\Http\Controllers\AttendanceController::class, 'index'])
        ->name('attendance.index');

    // 勤怠一覧画面（一般ユーザー・管理者共通）
    Route::get('/attendance/list', function() {
        // 一般ユーザー認証を先にチェック（一般ユーザーがアクセスしている場合）
        if (auth()->check()) {
            // 一般ユーザーの場合
            return app(\App\Http\Controllers\AttendanceController::class)->list(request());
        }
        
        // 管理者認証をチェック
        if (auth('admin')->check()) {
            // 管理者の場合
            return redirect()->route('admin.attendance.list');
        }
        
        // 未認証の場合はログイン画面へ
        return redirect('/login');
    })->name('attendance.list');

    // 勤怠打刻API
    Route::post('/attendance/clock-in', [App\Http\Controllers\AttendanceController::class, 'clockIn'])
        ->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [App\Http\Controllers\AttendanceController::class, 'clockOut'])
        ->name('attendance.clock-out');
    Route::post('/attendance/break-start', [App\Http\Controllers\AttendanceController::class, 'breakStart'])
        ->name('attendance.break-start');
    Route::post('/attendance/break-end', [App\Http\Controllers\AttendanceController::class, 'breakEnd'])
        ->name('attendance.break-end');

    Route::patch('/attendance/{attendance}', [App\Http\Controllers\AttendanceController::class, 'requestUpdate'])
        ->name('attendance.requestUpdate');

    // ログイン後のリダイレクト先を勤怠打刻画面に変更
    Route::get('/dashboard', function () {
        return redirect()->route('attendance.index');
    })->middleware(['auth'])->name('dashboard');
});

// 申請一覧画面（管理者・一般ユーザー共通）
Route::get('/stamp_correction_request/list', function() {
    // 一般ユーザー認証を先にチェック（一般ユーザーがアクセスしている場合）
    if (auth()->check()) {
        // 一般ユーザーの場合
        return app(\App\Http\Controllers\AttendanceCorrectionController::class)->list(request());
    }
    
    // 管理者認証をチェック
    if (auth('admin')->check()) {
        // 管理者の場合
        return app(\App\Http\Controllers\AttendanceCorrectionController::class)->adminList(request());
    }
    
    // 未認証の場合はログイン画面へ（管理者優先）
    return redirect('/admin/login');
})->name('correction.list');

// 修正申請承認画面（管理者・一般ユーザー共通）
Route::get('/stamp_correction_request/approve/{correction}', function($correction) {
    if (auth('admin')->check()) {
        return app(\App\Http\Controllers\AttendanceCorrectionController::class)->show($correction);
    }
    if (auth()->check()) {
        // 一般ユーザー用のshowメソッドが必要な場合はこちらに実装
        // 例: return app(\App\Http\Controllers\AttendanceCorrectionController::class)->userShow($correction);
        abort(403, '一般ユーザー用の詳細画面は未実装です');
    }
    return redirect('/admin/login');
})->name('correction.approve');

// 共通の勤怠詳細ルート（機能要件通り同じURLパス）
Route::get('/attendance/{attendance}', function($attendance) {
    // Attendanceモデルを取得
    $attendanceModel = \App\Models\Attendance::findOrFail($attendance);
    
    // 管理者認証を先にチェック
    if (auth('admin')->check()) {
        // 管理者の場合
        return app(\App\Http\Controllers\Admin\AttendanceController::class)->show($attendanceModel);
    }
    
    // 一般ユーザー認証をチェック
    if (auth()->check()) {
        // 一般ユーザーの場合
        return app(\App\Http\Controllers\AttendanceController::class)->show($attendanceModel);
    }
    
    // 未認証の場合はログイン画面へ
    return redirect('/login');
})->name('attendance.show');
