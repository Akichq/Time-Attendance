<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 管理者データを1件作成
        Admin::create([
            'name' => 'テスト管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // テスト用ダミーデータを作成（開発環境のみ）
        if (app()->environment('local', 'development')) {
            // テスト用一般ユーザーを作成
            $users = [
                [
                    'name' => '田中太郎',
                    'email' => 'tanaka@example.com',
                    'password' => bcrypt('password'),
                ],
                [
                    'name' => '佐藤花子',
                    'email' => 'sato@example.com',
                    'password' => bcrypt('password'),
                ],
                [
                    'name' => '鈴木一郎',
                    'email' => 'suzuki@example.com',
                    'password' => bcrypt('password'),
                ],
                [
                    'name' => '高橋美咲',
                    'email' => 'takahashi@example.com',
                    'password' => bcrypt('password'),
                ],
                [
                    'name' => '渡辺健太',
                    'email' => 'watanabe@example.com',
                    'password' => bcrypt('password'),
                ],
            ];

            foreach ($users as $userData) {
                $user = User::create($userData);
                // 前月、当月、翌月の勤怠データを作成
                $months = [
                    Carbon::now()->subMonth(), // 前月
                    Carbon::now(), // 当月
                    Carbon::now()->addMonth(), // 翌月
                ];
                foreach ($months as $month) {
                    $startOfMonth = $month->copy()->startOfMonth();
                    $endOfMonth = $month->copy()->endOfMonth();
                    // 各月の営業日（月-金）で勤怠データを作成
                    for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                        // 土日は勤怠データを作成しない
                        if ($date->isWeekend()) {
                            continue;
                        }
                        // 出勤時間（8:00-10:00の間でランダム）
                        $clockInHour = rand(8, 10);
                        $clockInMinute = rand(0, 59);
                        $clockInTime = $date->copy()->setTime($clockInHour, $clockInMinute);
                        // 退勤時間（17:00-20:00の間でランダム）
                        $clockOutHour = rand(17, 20);
                        $clockOutMinute = rand(0, 59);
                        $clockOutTime = $date->copy()->setTime($clockOutHour, $clockOutMinute);
                        // 勤怠データを作成
                        $attendance = Attendance::create([
                            'user_id' => $user->id,
                            'clock_in_time' => $clockInTime,
                            'clock_out_time' => $clockOutTime,
                            'remarks' => 'テスト用データ - ' . $date->format('Y-m-d'),
                        ]);
                        // 休憩データを作成（0-3回のランダム）
                        $breakCount = rand(0, 3);
                        for ($breakNumber = 0; $breakNumber < $breakCount; $breakNumber++) {
                            // 休憩開始時間（出勤後1-4時間後）
                            $breakStartHour = $clockInHour + rand(1, 4);
                            $breakStartMinute = rand(0, 59);
                            $breakStartTime = $date->copy()->setTime($breakStartHour, $breakStartMinute);
                            // 休憩終了時間（開始から30分-1時間後）
                            $breakEndHour = $breakStartHour + rand(0, 1);
                            $breakEndMinute = rand(0, 59);
                            $breakEndTime = $date->copy()->setTime($breakEndHour, $breakEndMinute);
                            // 休憩時間が勤務時間内かチェック
                            if ($breakStartTime < $clockOutTime && $breakEndTime <= $clockOutTime) {
                                BreakTime::create([
                                    'attendance_id' => $attendance->id,
                                    'break_start_time' => $breakStartTime,
                                    'break_end_time' => $breakEndTime,
                                ]);
                            }
                        }
                        // 修正申請データを作成（10%の確率で）
                        if (rand(1, 10) === 1) {
                            // 修正後の時間（元の時間から±30分以内でランダム）
                            $correctedClockInHour = $clockInHour + rand(-1, 1);
                            $correctedClockInMinute = rand(0, 59);
                            $correctedClockInTime = $date->copy()->setTime($correctedClockInHour, $correctedClockInMinute);
                            $correctedClockOutHour = $clockOutHour + rand(-1, 1);
                            $correctedClockOutMinute = rand(0, 59);
                            $correctedClockOutTime = $date->copy()->setTime($correctedClockOutHour, $correctedClockOutMinute);
                            // 承認状態をランダムに設定
                            $status = rand(1, 3) === 1 ? 'pending' : 'approved';
                            AttendanceCorrection::create([
                                'attendance_id' => $attendance->id,
                                'user_id' => $user->id,
                                'requested_clock_in_time' => $correctedClockInTime->format('H:i:s'),
                                'requested_clock_out_time' => $correctedClockOutTime->format('H:i:s'),
                                'requested_breaks' => null,
                                'remarks' => '修正申請テスト - ' . $date->format('Y-m-d'),
                                'status' => $status,
                                'admin_remarks' => null,
                            ]);
                        }
                    }
                }
            }
            $this->command->info('テスト用ダミーデータを作成しました。');
            $this->command->info('一般ユーザー: 5名');
            $this->command->info('勤怠データ: 前月・当月・翌月分');
            $this->command->info('修正申請: 約15件');
        }
    }
}
