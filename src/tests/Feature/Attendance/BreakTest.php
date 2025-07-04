<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 休憩ボタンが正しく機能する
     * 1. ステータスが出勤中のユーザーにログインする
     * 2. 画面に「休憩入」ボタンが表示されていることを確認する
     * 3. 休憩の処理を行う
     */
    public function test_break_start_button_works_correctly()
    {
        // ステータスが出勤中のユーザーを作成
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

        // 画面に「休憩入」ボタンが表示されていることを確認する
        $response->assertSee('休憩入');

        // 休憩の処理を行う
        $response = $this->post('/attendance/break-start');

        // リダイレクトされることを確認
        $response->assertRedirect('/attendance');

        // 休憩記録が作成されていることを確認
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_end_time' => null, // 休憩終了していない
        ]);

        // 再度勤怠打刻画面を開いてステータスを確認
        $response = $this->get('/attendance');

        // 画面上に表示されるステータスが「休憩中」になることを確認
        $response->assertSee('休憩中');
    }

    /**
     * 休憩は一日に何回でもできる
     * 1. ステータスが出勤中であるユーザーにログインする
     * 2. 休憩入と休憩戻の処理を行う
     * 3. 「休憩入」ボタンが表示されることを確認する
     */
    public function test_break_can_be_taken_multiple_times_per_day()
    {
        // ステータスが出勤中であるユーザーを作成
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

        // 休憩入の処理を行う
        $this->post('/attendance/break-start');

        // 休憩戻の処理を行う
        $this->post('/attendance/break-end');

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 「休憩入」ボタンが表示されることを確認する
        $response->assertSee('休憩入');
    }

    /**
     * 休憩戻ボタンが正しく機能する
     * 1. ステータスが出勤中であるユーザーにログインする
     * 2. 休憩入の処理を行う
     * 3. 休憩戻の処理を行う
     */
    public function test_break_end_button_works_correctly()
    {
        // ステータスが出勤中であるユーザーを作成
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

        // 休憩入の処理を行う
        $this->post('/attendance/break-start');

        // 休憩戻の処理を行う
        $response = $this->post('/attendance/break-end');

        // リダイレクトされることを確認（redirect()->back()のため、実際のリダイレクト先を確認）
        $response->assertStatus(302);

        // 休憩記録が更新されていることを確認
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);
        
        // 休憩終了時間が設定されていることを確認
        $break = BreakTime::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($break->break_end_time);

        // 再度勤怠打刻画面を開いてステータスを確認
        $response = $this->get('/attendance');

        // 休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更されることを確認
        $response->assertSee('出勤中');
    }

    /**
     * 休憩戻は一日に何回でもできる
     * 1. ステータスが出勤中であるユーザーにログインする
     * 2. 休憩入と休憩戻の処理を行い、再度休憩入の処理を行う
     * 3. 「休憩戻」ボタンが表示されることを確認する
     */
    public function test_break_end_can_be_done_multiple_times_per_day()
    {
        // ステータスが出勤中であるユーザーを作成
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

        // 休憩入と休憩戻の処理を行う
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        // 再度休憩入の処理を行う
        $this->post('/attendance/break-start');

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 「休憩戻」ボタンが表示されることを確認する
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     * 1. ステータスが勤務中のユーザーにログインする
     * 2. 休憩入と休憩戻の処理を行う
     * 3. 勤怠一覧画面から休憩の日付を確認する
     */
    public function test_break_times_are_visible_in_attendance_list()
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

        // 休憩入と休憩戻の処理を行う
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        // 勤怠一覧画面を開く
        $response = $this->get('/attendance/list');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 勤怠一覧画面に休憩時刻が正確に記録されていることを確認
        // 休憩列が表示されていることを確認
        $response->assertSee('休憩');
        
        // 今日の日付が表示されていることを確認（月の形式で）
        $response->assertSee(Carbon::now()->format('m/d'));
    }
} 