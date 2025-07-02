<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class DetailTest extends TestCase
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
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_shows_user_name()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out_time' => Carbon::parse('2024-01-15 18:00:00'),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠詳細ページを開く
        $response = $this->get("/attendance/{$attendance->id}");

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 名前欄を確認する
        $response->assertSee('テストユーザー');
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_shows_correct_date()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 勤怠記録を作成（2024年1月15日）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out_time' => Carbon::parse('2024-01-15 18:00:00'),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠詳細ページを開く
        $response = $this->get("/attendance/{$attendance->id}");

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 日付欄を確認する（年、月、日が表示される）
        $response->assertSee('2024年');
        $response->assertSee('1月15日');
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_clock_in_out_times()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out_time' => Carbon::parse('2024-01-15 18:00:00'),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠詳細ページを開く
        $response = $this->get("/attendance/{$attendance->id}");

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 出勤・退勤欄を確認する（input要素のvalue属性で時間が設定されている）
        $response->assertSee('09:00'); // 出勤時間
        $response->assertSee('18:00'); // 退勤時間
    }

    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_break_times()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out_time' => Carbon::parse('2024-01-15 18:00:00'),
        ]);

        // 休憩記録を作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::parse('2024-01-15 12:00:00'),
            'break_end_time' => Carbon::parse('2024-01-15 13:00:00'),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠詳細ページを開く
        $response = $this->get("/attendance/{$attendance->id}");

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 休憩欄を確認する（input要素のvalue属性で時間が設定されている）
        $response->assertSee('12:00'); // 休憩開始時間
        $response->assertSee('13:00'); // 休憩終了時間
    }
} 