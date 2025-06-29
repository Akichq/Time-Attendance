<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceCorrectionController extends Controller
{
    /**
     * 申請一覧画面を表示（一般ユーザー用）
     */
    public function list(Request $request)
    {
        $user = Auth::user();
        // 承認待ち
        $pending = AttendanceCorrection::with(['attendance', 'attendance.user'])
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
        // 承認済み
        $approved = AttendanceCorrection::with(['attendance', 'attendance.user'])
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();
        return view('correction.list', compact('pending', 'approved'));
    }

    /**
     * 申請一覧画面を表示（管理者用）
     */
    public function adminList(Request $request)
    {
        // 承認待ちの申請を取得
        $pending = AttendanceCorrection::with(['attendance', 'attendance.user'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        // 承認済みの申請を取得
        $approved = AttendanceCorrection::with(['attendance', 'attendance.user'])
            ->where('status', 'approved')
            ->orderByDesc('updated_at')
            ->get();

        // 却下された申請を取得
        $rejected = AttendanceCorrection::with(['attendance', 'attendance.user'])
            ->where('status', 'rejected')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.correction.list', compact('pending', 'approved', 'rejected'));
    }

    /**
     * 承認画面を表示（管理者用）
     */
    public function show(AttendanceCorrection $attendance_correct_request)
    {
        return view('admin.correction.approve', ['correction' => $attendance_correct_request]);
    }

    /**
     * 申請を承認（管理者用）
     */
    public function approve(Request $request, AttendanceCorrection $attendance_correct_request)
    {
        try {
            DB::beginTransaction();

            // 申請のステータスを承認に変更
            $attendance_correct_request->update([
                'status' => 'approved',
                'admin_remarks' => $request->input('admin_remarks', ''),
            ]);

            // 勤怠データを更新
            $attendance = $attendance_correct_request->attendance;
            $attendance->update([
                'clock_in_time' => $attendance_correct_request->requested_clock_in_time,
                'clock_out_time' => $attendance_correct_request->requested_clock_out_time,
            ]);

            // 休憩時間を更新
            $requestedBreaks = json_decode($attendance_correct_request->requested_breaks, true);
            if (isset($requestedBreaks['existing'])) {
                // 既存の休憩時間を削除
                BreakTime::where('attendance_id', $attendance->id)->delete();
                
                // 新しい休憩時間を登録
                foreach ($requestedBreaks['existing'] as $break) {
                    if (!empty($break['break_start_time']) && !empty($break['break_end_time'])) {
                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'break_start_time' => $break['break_start_time'],
                            'break_end_time' => $break['break_end_time'],
                        ]);
                    }
                }
            }

            DB::commit();

            // JSONレスポンスを返す
            return response()->json([
                'success' => true,
                'message' => '申請を承認しました。'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            // JSONレスポンスでエラーを返す
            return response()->json([
                'success' => false,
                'message' => '承認処理中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * 申請を却下（管理者用）
     */
    public function reject(Request $request, AttendanceCorrection $correction)
    {
        try {
            $correction->update([
                'status' => 'rejected',
                'admin_remarks' => $request->input('admin_remarks', ''),
            ]);

            return redirect()->route('admin.correction.list')
                ->with('success', '申請を却下しました。');

        } catch (\Exception $e) {
            return redirect()->route('admin.correction.list')
                ->with('error', '却下処理中にエラーが発生しました。');
        }
    }
} 