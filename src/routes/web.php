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

// 勤怠管理
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
        
        // 未認証の場合はログイン画面へ
        return redirect('/login');
    })->name('correction.list');

    // 修正申請承認画面（管理者・一般ユーザー共通）
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', function($correctionId) {
        if (auth('admin')->check()) {
            // 管理者の場合
            $correction = \App\Models\AttendanceCorrection::findOrFail($correctionId);
            return app(\App\Http\Controllers\AttendanceCorrectionController::class)->show($correction);
        } elseif (auth()->check()) {
            // 一般ユーザーの場合（承認機能は通常不要なのでエラー表示やリダイレクト）
            abort(403, '権限がありません');
        } else {
            // 未認証の場合はログイン画面へ
            return redirect('/login');
        }
    })->name('correction.show');

    Route::patch('/stamp_correction_request/approve/{attendance_correct_request}', function($correctionId) {
        if (auth('admin')->check()) {
            // 管理者の場合
            $correction = \App\Models\AttendanceCorrection::findOrFail($correctionId);
            return app(\App\Http\Controllers\AttendanceCorrectionController::class)->approve(request(), $correction);
        } elseif (auth()->check()) {
            // 一般ユーザーの場合（承認機能は通常不要なのでエラー表示やリダイレクト）
            abort(403, '権限がありません');
        } else {
            // 未認証の場合はログイン画面へ
            return redirect('/login');
        }
    })->name('correction.approve');
});

// 勤怠詳細画面（一般ユーザー・管理者共通）- ミドルウェアグループの外に移動
Route::get('/attendance/{attendance}', function($attendance) {
    // ルートパラメータをAttendanceモデルインスタンスに変換
    $attendanceModel = \App\Models\Attendance::findOrFail($attendance);
    
    // 一般ユーザー認証が有効な場合は必ず一般ユーザー用を優先
    if (auth()->check()) {
        return app(\App\Http\Controllers\AttendanceController::class)->show($attendanceModel);
    } elseif (auth('admin')->check()) {
        // 一般ユーザーで未ログインかつ管理者認証が有効な場合のみ管理者用
        return app(\App\Http\Controllers\Admin\AttendanceController::class)->show($attendanceModel);
    }
    // 未認証の場合はログイン画面へ
    return redirect('/login');
})->name('attendance.show');

// ログイン後のリダイレクト先を勤怠打刻画面に変更
Route::get('/dashboard', function () {
    return redirect()->route('attendance.index');
})->middleware(['auth'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'create'])
        ->middleware('guest:admin')
        ->name('login');

    Route::post('/login', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'store'])
        ->middleware('guest:admin');

    Route::post('/logout', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');

    // この下に、認証が必要な他の管理者用ルートを追加していく
    Route::middleware('auth:admin')->group(function () {
        // 勤怠一覧画面（管理者）
        Route::get('/attendance/list', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('attendance.list');

        // スタッフ別勤怠一覧（共通ルートより前に定義）
        Route::get('/attendance/staff/{id}', [\App\Http\Controllers\Admin\AttendanceController::class, 'staffAttendance'])->name('attendance.staff');

        // スタッフ別勤怠一覧CSV出力（共通ルートより前に定義）
        Route::get('/attendance/staff/{id}/csv', [\App\Http\Controllers\Admin\AttendanceController::class, 'staffAttendanceCsv'])->name('attendance.staff.csv');

        // 勤怠詳細画面（管理者） - 共通ルートで処理されるため削除
        Route::patch('/attendance/{attendance}', [\App\Http\Controllers\Admin\AttendanceController::class, 'update'])->name('attendance.update');

        // 申請承認機能
        Route::get('/correction/list', [\App\Http\Controllers\AttendanceCorrectionController::class, 'adminList'])->name('correction.list');
        Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [\App\Http\Controllers\AttendanceCorrectionController::class, 'show'])->name('correction.approve');
        Route::patch('/stamp_correction_request/approve/{attendance_correct_request}', [\App\Http\Controllers\AttendanceCorrectionController::class, 'approve'])->name('correction.approve');
        Route::patch('/correction/{correction}/reject', [\App\Http\Controllers\AttendanceCorrectionController::class, 'reject'])->name('correction.reject');

        // スタッフ一覧（本実装）
        Route::get('/staff/list', [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('staff.list');
    });
});
