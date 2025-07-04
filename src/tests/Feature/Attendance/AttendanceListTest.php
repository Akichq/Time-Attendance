<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されている
     * 1. 勤怠情報が登録されたユーザーにログインする
     * 2. 勤怠一覧ページを開く
     * 3. 自分の勤怠情報がすべて表示されていることを確認する
     */
    public function test_user_can_see_all_their_attendance_records()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // メール認証をスキップするため、verifiedミドルウェアを無効化
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        // 複数の勤怠記録を作成（現在の月のデータ）
        $currentMonth = Carbon::now();
        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $currentMonth->copy()->setDay(15)->setTime(9, 0),
            'clock_out_time' => $currentMonth->copy()->setDay(15)->setTime(18, 0),
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $currentMonth->copy()->setDay(16)->setTime(9, 0),
            'clock_out_time' => $currentMonth->copy()->setDay(16)->setTime(18, 0),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 自分の勤怠情報がすべて表示されていることを確認する
        $response->assertSee('07/15'); // 7月15日の日付（現在の月）
        $response->assertSee('07/16'); // 7月16日の日付（現在の月）
        $response->assertSee('09:00'); // 出勤時間
        $response->assertSee('18:00'); // 退勤時間
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     * 1. ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     */
    public function test_current_month_is_displayed_when_accessing_attendance_list()
    {
        // ユーザーを作成
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

        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 現在の月が表示されていることを確認
        $currentMonth = Carbon::now()->format('Y/m');
        $response->assertSee($currentMonth);
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「前月」ボタンを押す
     */
    public function test_previous_month_information_is_displayed_when_clicking_previous_month()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // メール認証をスキップするため、verifiedミドルウェアを無効化
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        // 前月の勤怠記録を作成
        $previousMonth = Carbon::now()->subMonth();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $previousMonth->copy()->setDay(15)->setTime(9, 0),
            'clock_out_time' => $previousMonth->copy()->setDay(15)->setTime(18, 0),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 「前月」ボタンを押す
        $previousMonthUrl = '/attendance/list?month=' . $previousMonth->format('Y-m');
        $response = $this->get($previousMonthUrl);

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 前月の情報が表示されていることを確認
        $response->assertSee($previousMonth->format('Y/m'));
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「翌月」ボタンを押す
     */
    public function test_next_month_information_is_displayed_when_clicking_next_month()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // メール認証をスキップするため、verifiedミドルウェアを無効化
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        // 翌月の勤怠記録を作成
        $nextMonth = Carbon::now()->addMonth();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $nextMonth->copy()->setDay(15)->setTime(9, 0),
            'clock_out_time' => $nextMonth->copy()->setDay(15)->setTime(18, 0),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 「翌月」ボタンを押す
        $nextMonthUrl = '/attendance/list?month=' . $nextMonth->format('Y-m');
        $response = $this->get($nextMonthUrl);

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 翌月の情報が表示されていることを確認
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「詳細」ボタンを押下する
     */
    public function test_detail_button_redirects_to_attendance_detail_page()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // メール認証をスキップするため、verifiedミドルウェアを無効化
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        // 勤怠記録を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out_time' => Carbon::parse('2024-01-15 18:00:00'),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 「詳細」ボタンが表示されていることを確認
        $response->assertSee('詳細');

        // 「詳細」ボタンを押下する（詳細ページに直接アクセス）
        $response = $this->get("/attendance/{$attendance->id}");

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // その日の勤怠詳細画面に遷移することを確認
        $response->assertSee('勤怠詳細');
        $response->assertSee('09:00'); // 出勤時間
        $response->assertSee('18:00'); // 退勤時間
    }
} 