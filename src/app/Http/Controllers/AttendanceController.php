<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\AttendanceCorrection;

class AttendanceController extends Controller
{
    /**
     *
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 今日の最新の勤怠記録を取得
        $latestAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in_time', $today)
            ->latest('clock_in_time')
            ->first();

        $attendanceStatus = 'before_work';

        if ($latestAttendance) {
            if (!$latestAttendance->clock_out_time) {
                $latestBreak = $latestAttendance->breaks()->latest('break_start_time')->first();
                if ($latestBreak && !$latestBreak->break_end_time) {
                    $attendanceStatus = 'on_break';
                } else {
                    $attendanceStatus = 'at_work';
                }
            } else {
                $attendanceStatus = 'after_work';
            }
        }

        return view('attendance.index', compact('attendanceStatus'));
    }

    /**
     *
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clockIn(Request $request)
    {
        $user = Auth::user();

        // 既に本日出勤済みか確認
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in_time', Carbon::today())
            ->first();

        if ($existingAttendance) {
            return redirect()->back()->with('error', '本日は既に出勤打刻済みです。');
        }

        Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', '出勤しました。');
    }

    /**
     * 退勤を記録します。
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clockOut(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in_time', Carbon::today())
            ->whereNull('clock_out_time')
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', '出勤記録が見つかりません。');
        }
        // 休憩中であれば退勤できないようにする
        $latestBreak = $attendance->breaks()->latest('break_start_time')->first();
        if ($latestBreak && !$latestBreak->break_end_time) {
            return redirect()->back()->with('error', '休憩中です。退勤する前に休憩を終了してください。');
        }

        $attendance->update([
            'clock_out_time' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', '退勤しました。');
    }

    /**
     * 休憩開始を記録します。
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function breakStart(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in_time', Carbon::today())
            ->whereNull('clock_out_time')
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', '出勤記録が見つかりません。');
        }

        $existingBreak = $attendance->breaks()->whereNull('break_end_time')->first();
        if ($existingBreak) {
            return redirect()->back()->with('error', '既に休憩中です。');
        }

        $attendance->breaks()->create([
            'break_start_time' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', '休憩を開始しました。');
    }

    /**
     * 休憩終了を記録します。
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function breakEnd(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in_time', Carbon::today())
            ->whereNull('clock_out_time')
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', '出勤記録が見つかりません。');
        }

        $break = $attendance->breaks()
            ->whereNull('break_end_time')
            ->latest('break_start_time')
            ->first();

        if (!$break) {
            return redirect()->back()->with('error', '開始された休憩が見つかりません。');
        }

        $break->update([
            'break_end_time' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', '休憩を終了しました。');
    }

    /**
     * 勤怠一覧画面を表示します。
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function list(Request $request)
    {
        // クエリパラメータから月を取得、なければ現在の月
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $targetMonth = Carbon::createFromFormat('Y-m-d', $month . '-01');
        // 指定月の日数を取得
        $daysInMonth = $targetMonth->daysInMonth;
        // 1日から月末までの配列を生成
        $calendarDays = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($targetMonth->year, $targetMonth->month, $day);
            $calendarDays[] = $date;
        }

        // 前月と翌月の情報を生成
        $previousMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        // ログインユーザーの指定された月の勤怠データを取得し、日付をキーとした配列に変換
        $user = Auth::user();
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereYear('clock_in_time', $targetMonth->year)
            ->whereMonth('clock_in_time', $targetMonth->month)
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->clock_in_time->format('Y-m-d');
            });

        // 各勤怠データに合計時間（出勤〜退勤＋休憩合計）をセット
        foreach ($attendances as $attendance) {
            $clockIn = $attendance->clock_in_time ? new \Carbon\Carbon($attendance->clock_in_time) : null;
            $clockOut = $attendance->clock_out_time ? new \Carbon\Carbon($attendance->clock_out_time) : null;
            $breakSeconds = $attendance->breaks->sum(function ($break) {
                if ($break->break_start_time && $break->break_end_time) {
                    return (new \Carbon\Carbon($break->break_end_time))->diffInSeconds(new \Carbon\Carbon($break->break_start_time));
                }
                return 0;
            });
            $workSeconds = ($clockIn && $clockOut) ? $clockOut->diffInSeconds($clockIn) : 0;
            $totalSeconds = $workSeconds + $breakSeconds;
            $attendance->total_time = sprintf('%02d:%02d', floor($totalSeconds / 3600), floor(($totalSeconds % 3600) / 60));
        }

        return view('attendance.list', compact('calendarDays', 'attendances', 'targetMonth', 'previousMonth', 'nextMonth'));
    }

    /**
     * 勤怠詳細画面を表示します。
     *
     * @param Attendance $attendance
     * @return \Illuminate\View\View
     */
    public function show(Attendance $attendance)
    {
        if (auth('admin')->check()) {
            // 管理者は全て閲覧可能
            $attendance->load('user', 'breaks');
            $breaks = $attendance->breaks;
            return view('admin.attendance.detail', compact('attendance', 'breaks'));
        }
        if (Auth::id() !== $attendance->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $attendance->load('user', 'breaks');
        $breaks = $attendance->breaks;

        $pendingCorrection = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
        $isPending = !is_null($pendingCorrection);

        // 承認済みの修正申請があるかどうかを確認
        $approvedCorrection = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'approved')
            ->latest()
            ->first();
        $isApproved = !is_null($approvedCorrection);

        // 承認待ち状態の場合、修正申請データをフィールドに表示するためのデータを準備
        if ($isPending && $pendingCorrection) {
            // 元の勤怠データの日付を保持
            $originalDate = $attendance->clock_in_time->format('Y-m-d');

            // 修正申請データの時刻部分のみを取得して、元の日付と組み合わせる
            $requestedClockInTime = Carbon::parse($pendingCorrection->requested_clock_in_time);
            $requestedClockOutTime = Carbon::parse($pendingCorrection->requested_clock_out_time);

            $attendance->clock_in_time = $originalDate . ' ' . $requestedClockInTime->format('H:i:s');
            $attendance->clock_out_time = $originalDate . ' ' . $requestedClockOutTime->format('H:i:s');
            $attendance->remarks = $pendingCorrection->remarks;

            // 修正申請された休憩データを取得
            $requestedBreaks = json_decode($pendingCorrection->requested_breaks, true);

            // 既存の休憩データを修正申請データで更新
            if (isset($requestedBreaks['existing'])) {
                foreach ($requestedBreaks['existing'] as $index => $break) {
                    if (isset($breaks[$index])) {
                        $breaks[$index]->break_start_time = $break['break_start_time'];
                        $breaks[$index]->break_end_time = $break['break_end_time'];
                    }
                }
            }

            // 新しい休憩データを追加
            if (isset($requestedBreaks['new'])) {
                foreach ($requestedBreaks['new'] as $break) {
                    if (!empty($break['break_start_time']) && !empty($break['break_end_time'])) {
                        $newBreak = new \App\Models\BreakTime();
                        $newBreak->break_start_time = $break['break_start_time'];
                        $newBreak->break_end_time = $break['break_end_time'];
                        $breaks->push($newBreak);
                    }
                }
            }
        }

        return view('attendance.detail', compact('attendance', 'breaks', 'isPending', 'isApproved', 'pendingCorrection'));
    }

    /**
     * 勤怠修正申請を処理します。
     *
     * @param UpdateAttendanceRequest $request
     * @param Attendance $attendance
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestUpdate(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        if (Auth::id() !== $attendance->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // 元の勤怠データの日付を取得
        $originalDate = $attendance->clock_in_time->format('Y-m-d');

        // 日付＋時刻の形式で申請データを作成
        $requestedClockInTime = $originalDate . ' ' . $request->clock_in_time . ':00';
        $requestedClockOutTime = $originalDate . ' ' . $request->clock_out_time . ':00';

        // 修正申請データを作成
        $correction = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'user_id' => Auth::id(),
            'requested_clock_in_time' => $requestedClockInTime,
            'requested_clock_out_time' => $requestedClockOutTime,
            'requested_breaks' => json_encode(['existing' => $request->breaks, 'new' => $request->new_breaks]),
            'remarks' => $request->remarks ?? '',
            'status' => 'pending',
        ]);

        // 詳細画面にリダイレクト
        return redirect()->route('attendance.show', ['attendance' => $attendance->id]);
    }
}