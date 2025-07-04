<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 退勤ボタンが正しく機能する
     * 1. ステータスが勤務中のユーザーにログインする
     * 2. 画面に「退勤」ボタンが表示されていることを確認する
     * 3. 退勤の処理を行う
     */
    public function test_clock_out_button_works_correctly()
    {
        // ステータスが勤務中のユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // メール認証をスキップするため、verifiedミドルウェアを無効化
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        // 出勤記録を作成（退勤時間なし）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::now(),
            'clock_out_time' => null, // 退勤していない
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 画面に「退勤」ボタンが表示されていることを確認する
        $response->assertSee('退勤');

        // 退勤の処理を行う
        $response = $this->post('/attendance/clock-out');

        // リダイレクトされることを確認
        $response->assertStatus(302);

        // 退勤記録が更新されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);
        
        // 退勤時間が設定されていることを確認
        $attendance = Attendance::where('user_id', $user->id)->first();
        $this->assertNotNull($attendance->clock_out_time);

        // 再度勤怠打刻画面を開いてステータスを確認
        $response = $this->get('/attendance');

        // 画面上に表示されるステータスが「退勤済」になることを確認
        $response->assertSee('退勤済');
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 出勤と退勤の処理を行う
     * 3. 勤怠一覧画面から退勤の日付を確認する
     */
    public function test_clock_out_time_is_visible_in_attendance_list()
    {
        // ステータスが勤務外のユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // メール認証をスキップするため、verifiedミドルウェアを無効化
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 出勤の処理を行う
        $this->post('/attendance/clock-in');

        // 退勤の処理を行う
        $this->post('/attendance/clock-out');

        // 勤怠一覧画面を開く
        $response = $this->get('/attendance/list');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 勤怠一覧画面に退勤時刻が正確に記録されていることを確認
        // 退勤列が表示されていることを確認
        $response->assertSee('退勤');
        
        // 今日の日付が表示されていることを確認（月の形式で）
        $response->assertSee(Carbon::now()->format('m/d'));
    }
} 