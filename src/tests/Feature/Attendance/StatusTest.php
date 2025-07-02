<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // CSRFトークンを無効にする
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        
        // メール認証をスキップする
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);
    }

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_before_work_status_display()
    {
        // ステータスが勤務外のユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 画面に表示されているステータスを確認
        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_at_work_status_display()
    {
        // ステータスが出勤中のユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

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

        // 画面に表示されているステータスを確認
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_on_break_status_display()
    {
        // ステータスが休憩中のユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 出勤記録を作成（退勤時間なし）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::now(),
            'clock_out_time' => null, // 退勤していない
        ]);

        // 休憩記録を作成（終了時間なし）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::now(),
            'break_end_time' => null, // 休憩終了していない
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 画面に表示されているステータスを確認
        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_after_work_status_display()
    {
        // ステータスが退勤済のユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 出勤・退勤記録を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::now()->subHours(8), // 8時間前に出勤
            'clock_out_time' => Carbon::now(), // 現在退勤
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 画面に表示されているステータスを確認
        $response->assertSee('退勤済');
    }
} 