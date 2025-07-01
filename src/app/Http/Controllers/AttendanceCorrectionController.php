<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceCorrectionController extends Controller
{
    /**
     * 一般ユーザー用申請一覧画面を表示
     */
    public function list(Request $request)
    {
        $user = Auth::user();
        
        // 承認待ちの申請
        $pending = AttendanceCorrection::with('attendance.user')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // 承認済みの申請
        $approved = AttendanceCorrection::with('attendance.user')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('correction.list', compact('pending', 'approved'));
    }

    /**
     * 管理者用申請一覧画面を表示
     */
    public function adminList(Request $request)
    {
        // 承認待ちの申請
        $pending = AttendanceCorrection::with(['attendance.user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // 承認済みの申請
        $approved = AttendanceCorrection::with(['attendance.user'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.correction.list', compact('pending', 'approved'));
    }

    /**
     * 申請詳細画面を表示（管理者用）
     */
    public function show(AttendanceCorrection $correction)
    {
        $correction->load(['attendance.user']);
        $requestedBreaks = json_decode($correction->requested_breaks, true);
        return view('admin.correction.approve', compact('correction', 'requestedBreaks'));
    }

    /**
     * 申請を承認する（管理者用）
     */
    public function approve(Request $request, AttendanceCorrection $correction)
    {
        // 申請を承認済みに更新
        $correction->update([
            'status' => 'approved',
            'admin_remarks' => $request->input('admin_remarks', ''),
        ]);

        // 勤怠データを更新（requested_clock_in_timeは既に「日付＋時刻」形式）
        $attendance = $correction->attendance;
        $attendance->update([
            'clock_in_time' => $correction->requested_clock_in_time,
            'clock_out_time' => $correction->requested_clock_out_time,
            'remarks' => $correction->remarks,
        ]);

        // 休憩時間の更新
        $requestedBreaks = json_decode($correction->requested_breaks, true);
        
        // 既存の休憩時間を更新
        if (isset($requestedBreaks['existing'])) {
            foreach ($requestedBreaks['existing'] as $index => $break) {
                if (isset($attendance->breaks[$index])) {
                    $attendance->breaks[$index]->update([
                        'break_start_time' => $break['break_start_time'],
                        'break_end_time' => $break['break_end_time'],
                    ]);
                }
            }
        }

        // 新しい休憩時間を追加
        if (isset($requestedBreaks['new'])) {
            foreach ($requestedBreaks['new'] as $break) {
                if (!empty($break['break_start_time']) && !empty($break['break_end_time'])) {
                    $attendance->breaks()->create([
                        'break_start_time' => $break['break_start_time'],
                        'break_end_time' => $break['break_end_time'],
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * 申請を却下する（管理者用）
     */
    public function reject(Request $request, AttendanceCorrection $correction)
    {
        $correction->update([
            'status' => 'rejected',
            'admin_remarks' => $request->input('admin_remarks', ''),
        ]);

        return redirect()->route('admin.correction.list')->with('success', '申請を却下しました。');
    }
} 