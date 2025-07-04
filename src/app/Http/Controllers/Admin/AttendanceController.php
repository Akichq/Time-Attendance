<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\User;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 日付の取得と設定
        $date = $request->input('date') ? new Carbon($request->input('date')) : Carbon::today();

        // 指定された日付の勤怠データを取得 (N+1問題対策)
        $attendances = Attendance::with(['user', 'breaks'])
            ->whereDate('clock_in_time', $date)
            ->get();

        // 各勤怠データの時間計算
        foreach ($attendances as $attendance) {
            $startTime = new Carbon($attendance->clock_in_time);
            $endTime = $attendance->clock_out_time ? new Carbon($attendance->clock_out_time) : null;

            // 休憩時間の合計
            $totalBreakSeconds = $attendance->breaks->sum(function ($break) {
                return Carbon::parse($break->break_start_time)->diffInSeconds(Carbon::parse($break->break_end_time));
            });

            // 勤務時間
            $workSeconds = $endTime ? $startTime->diffInSeconds($endTime) - $totalBreakSeconds : 0;

            // 合計時間（出勤〜退勤＋休憩）
            $totalSeconds = $endTime ? $startTime->diffInSeconds($endTime) + $totalBreakSeconds : 0;

            $attendance->total_break_time_formatted = gmdate('H:i', $totalBreakSeconds);
            $attendance->total_work_time_formatted = $endTime ? gmdate('H:i', $workSeconds) : '00:00';
            $attendance->total_time_formatted = $endTime ? gmdate('H:i', $totalSeconds) : '00:00';
        }

        // 前日と翌日の日付
        $prevDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        return view('admin.attendance.list', compact('attendances', 'date', 'prevDate', 'nextDate'));
    }

    public function show($attendance)
    {
        if ($attendance instanceof \App\Models\Attendance) {
            $attendance->load(['user', 'breaks']);
        } else {
            $attendance = Attendance::with(['user', 'breaks'])->findOrFail($attendance);
        }
        $breaks = $attendance->breaks;
        return view('admin.attendance.detail', compact('attendance', 'breaks'));
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);
        $rules = [
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => ['required', 'date_format:H:i', 'after:clock_in_time'],
            'breaks.0.start' => ['nullable', 'date_format:H:i'],
            'breaks.0.end' => ['nullable', 'date_format:H:i'],
            'breaks.1.start' => ['nullable', 'date_format:H:i'],
            'breaks.1.end' => ['nullable', 'date_format:H:i'],
            'remarks' => ['required', 'string'],
        ];
        $messages = [
            'clock_in_time.required' => '出勤時間もしくは退勤時間が不適切な値です。',
            'clock_out_time.required' => '出勤時間もしくは退勤時間が不適切な値です。',
            'clock_out_time.after' => '出勤時間もしくは退勤時間が不適切な値です。',
            'remarks.required' => '備考を記入してください。',
        ];
        $validated = $request->validate($rules, $messages);
        // 勤怠データ更新
        $clockIn = $request->input('clock_in_time');
        $clockOut = $request->input('clock_out_time');
        // 休憩時間のバリデーション（すべての休憩＋追加欄）
        $breakInputs = $request->input('breaks', []);
        foreach ($breakInputs as $i => $break) {
            $bStart = $break['start'] ?? null;
            $bEnd = $break['end'] ?? null;
            if ($bStart && ($bStart < $clockIn || $bStart > $clockOut)) {
                return back()->withErrors(["breaks.$i.start" => '休憩時間が勤務時間外です。'])->withInput();
            }
            if ($bEnd && ($bEnd < $clockIn || $bEnd > $clockOut)) {
                return back()->withErrors(["breaks.$i.end" => '休憩時間が勤務時間外です。'])->withInput();
            }
        }
        // 勤怠データ更新
        $attendance->clock_in_time = $clockIn;
        $attendance->clock_out_time = $clockOut;
        $attendance->remarks = $request->input('remarks');
        $attendance->save();
        // 休憩データ更新
        foreach ([0,1] as $i) {
            if (isset($attendance->breaks[$i])) {
                $attendance->breaks[$i]->break_start_time = $request->input("breaks.$i.start");
                $attendance->breaks[$i]->break_end_time = $request->input("breaks.$i.end");
                $attendance->breaks[$i]->save();
            }
        }
        $newIndex = count($attendance->breaks);
        $newStart = $request->input("breaks.$newIndex.start");
        $newEnd = $request->input("breaks.$newIndex.end");
        if ($newStart && $newEnd) {
            $attendance->breaks()->create([
                'break_start_time' => $newStart,
                'break_end_time' => $newEnd,
            ]);
        }
        return redirect()->route('admin.attendance.show', $attendance->id)->with('success', '勤怠情報を修正しました。');
    }

    /**
     * スタッフ別勤怠一覧画面
     */
    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);
        // 月の取得（YYYY-MM形式）
        $month = $request->input('month') ?? now()->format('Y-m');
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        // 指定月の勤怠データを取得
        $attendances = $user->attendances()
            ->whereBetween('clock_in_time', [$startOfMonth, $endOfMonth])
            ->orderBy('clock_in_time')
            ->get();

        // 日付ごとにデータを整形（1日〜末日までループ）
        $days = [];
        // 日本語曜日配列
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $attendance = $attendances->first(function($a) use ($date) {
                return $a->clock_in_time && \Carbon\Carbon::parse($a->clock_in_time)->format('Y-m-d') === $date->format('Y-m-d');
            });
            // 休憩合計
            $break = '';
            $total = '';
            if ($attendance) {
                $breakInterval = $attendance->total_break_time;
                $break = $breakInterval ? $breakInterval->format('%H:%I') : '';
                // 合計時間（出勤〜退勤＋休憩）
                if ($attendance->clock_in_time && $attendance->clock_out_time) {
                    $start = $attendance->clock_in_time;
                    $end = $attendance->clock_out_time;
                    $totalSeconds = $start->diffInSeconds($end);
                    $breakSeconds = $breakInterval ? $breakInterval->totalSeconds : 0;
                    $totalAllSeconds = $totalSeconds + $breakSeconds;
                    $total = gmdate('H:i', $totalAllSeconds);
                }
            }
            $days[] = [
                'date' => $date->copy(),
                'weekday' => $weekdays[$date->dayOfWeek],
                'attendance' => $attendance,
                'break' => $break,
                'total' => $total,
            ];
        }

        // 前月・翌月
        $prevMonth = $startOfMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $startOfMonth->copy()->addMonth()->format('Y-m');

        return view('admin.attendance.staff_list', compact('user', 'month', 'days', 'prevMonth', 'nextMonth'));
    }

    /**
     * スタッフ別勤怠一覧CSV出力
     */
    public function staffAttendanceCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $month = $request->input('month') ?? now()->format('Y-m');
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        $attendances = $user->attendances()
            ->whereBetween('clock_in_time', [$startOfMonth, $endOfMonth])
            ->orderBy('clock_in_time')
            ->get();

        // 日付ごとにデータを整形（1日〜末日までループ）
        $rows = [];
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $attendance = $attendances->first(function($a) use ($date) {
                return $a->clock_in_time && \Carbon\Carbon::parse($a->clock_in_time)->format('Y-m-d') === $date->format('Y-m-d');
            });
            $break = '';
            $total = '';
            if ($attendance) {
                $breakInterval = $attendance->total_break_time;
                $break = $breakInterval ? $breakInterval->format('%H:%I') : '';
                if ($attendance->clock_in_time && $attendance->clock_out_time) {
                    $start = $attendance->clock_in_time;
                    $end = $attendance->clock_out_time;
                    $totalSeconds = $start->diffInSeconds($end);
                    $breakSeconds = $breakInterval ? $breakInterval->totalSeconds : 0;
                    $totalAllSeconds = $totalSeconds + $breakSeconds;
                    $total = gmdate('H:i', $totalAllSeconds);
                }
            }
            $rows[] = [
                'date' => $date->format('m/d') . '(' . $weekdays[$date->dayOfWeek] . ')',
                'clock_in' => $attendance && $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '',
                'clock_out' => $attendance && $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '',
                'break' => $break,
                'total' => $total,
            ];
        }

        $filename = $user->name . '_' . $month . '_attendance.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($rows) {
            $handle = fopen('php://output', 'w');
            // ヘッダー
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row['date'], $row['clock_in'], $row['clock_out'], $row['break'], $row['total']]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
