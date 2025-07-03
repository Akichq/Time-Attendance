<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockInTest extends TestCase
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
     * 出勤ボタンが正しく機能する
     */
    public function test_clock_in_button_works_correctly()
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

        // 画面に「出勤」ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');

        // 出勤の処理を行う
        $response = $this->post('/attendance/clock-in');
        $response->assertRedirect();

        // 処理後に画面上に表示されるステータスが「出勤中」になることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');

        // データベースに出勤記録が作成されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);
        
        // 今日の日付で出勤記録が作成されていることを確認
        $attendance = Attendance::where('user_id', $user->id)->first();
        $this->assertEquals(Carbon::today()->format('Y-m-d'), $attendance->clock_in_time->format('Y-m-d'));
    }

    /**
     * 出勤は一日一回のみできる
     */
    public function test_clock_in_can_only_be_done_once_per_day()
    {
        // ステータスが退勤済のユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 既に出勤・退勤済みの記録を作成
        Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::now()->subHours(8),
            'clock_out_time' => Carbon::now(),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 出勤ボタンが表示されないことを確認
        $response->assertSee('退勤済');
        
        // 出勤ボタンのフォームが非表示になっていることを確認
        $response->assertSee('出勤');
        $response->assertSee('display:none');

        // 出勤処理を試行してもエラーになることを確認
        $response = $this->post('/attendance/clock-in');
        $response->assertRedirect();
        $response->assertSessionHas('error', '本日は既に出勤打刻済みです。');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_in_time_is_displayed_in_attendance_list()
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

        // 現在の日時を固定（テスト用）
        $now = Carbon::create(2025, 1, 15, 9, 30, 0); // 2025年1月15日 9:30
        Carbon::setTestNow($now);

        // 出勤の処理を行う
        $this->post('/attendance/clock-in');

        // 勤怠一覧画面から出勤の日付を確認
        $response = $this->get('/attendance/list?month=2025-01');
        $response->assertStatus(200);

        // 勤怠一覧画面に出勤時刻が正確に記録されていることを確認
        $response->assertSee('09:30'); // 出勤時刻

        // テスト用の時間設定をリセット
        Carbon::setTestNow();
    }
} 