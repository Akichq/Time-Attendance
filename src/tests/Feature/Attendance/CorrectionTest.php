<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class CorrectionTest extends TestCase
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
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_clock_in_time_after_clock_out_time_shows_error()
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

        // 出勤時間を退勤時間より後に設定して保存処理をする
        $response = $this->patch("/attendance/{$attendance->id}", [
            'clock_in_time' => '19:00', // 退勤時間より後
            'clock_out_time' => '18:00',
            'remarks' => 'テスト備考',
        ]);

        // バリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors(['clock_out_time']);
        $response->assertSessionHasErrors(['clock_out_time' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_time_after_clock_out_time_shows_error()
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

        // 休憩開始時間を退勤時間より後に設定して保存処理をする
        $response = $this->patch("/attendance/{$attendance->id}", [
            'clock_in_time' => '09:00',
            'clock_out_time' => '18:00',
            'breaks' => [
                [
                    'break_start_time' => '19:00', // 退勤時間より後
                    'break_end_time' => '20:00',
                ]
            ],
            'remarks' => 'テスト備考',
        ]);

        // バリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors(['breaks.0.break_start_time' => '休憩時間が勤務時間外です']);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_end_time_after_clock_out_time_shows_error()
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

        // 休憩終了時間を退勤時間より後に設定して保存処理をする
        $response = $this->patch("/attendance/{$attendance->id}", [
            'clock_in_time' => '09:00',
            'clock_out_time' => '18:00',
            'breaks' => [
                [
                    'break_start_time' => '12:00',
                    'break_end_time' => '19:00', // 退勤時間より後
                ]
            ],
            'remarks' => 'テスト備考',
        ]);

        // バリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors(['breaks.0.break_start_time' => '休憩時間が勤務時間外です']);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_empty_remarks_shows_error()
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

        // 備考欄を未入力のまま保存処理をする
        $response = $this->patch("/attendance/{$attendance->id}", [
            'clock_in_time' => '09:00',
            'clock_out_time' => '18:00',
            'remarks' => '', // 未入力
        ]);

        // バリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_correction_request_is_created()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 管理者を作成
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out_time' => Carbon::parse('2024-01-15 18:00:00'),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠詳細を修正し保存処理をする
        $response = $this->patch("/attendance/{$attendance->id}", [
            'clock_in_time' => '08:30',
            'clock_out_time' => '17:30',
            'remarks' => '修正申請テスト',
        ]);

        // 修正申請が作成されることを確認
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'remarks' => '修正申請テスト',
        ]);

        // 管理者ユーザーで承認画面を確認
        $this->actingAs($admin, 'admin');
        $correction = AttendanceCorrection::where('attendance_id', $attendance->id)->first();
        
        $response = $this->get("/admin/stamp_correction_request/approve/{$correction->id}");
        $response->assertStatus(200);
        $response->assertSee('修正申請テスト');
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_pending_requests_are_displayed()
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

        // 勤怠詳細を修正し保存処理をする
        $this->patch("/attendance/{$attendance->id}", [
            'clock_in_time' => '08:30',
            'clock_out_time' => '17:30',
            'remarks' => '承認待ちテスト',
        ]);

        // 申請一覧画面を確認
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('テストユーザー');
        $response->assertSee('承認待ちテスト');
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_approved_requests_are_displayed()
    {
        // 勤怠情報が登録されたユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // 管理者を作成
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out_time' => Carbon::parse('2024-01-15 18:00:00'),
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠詳細を修正し保存処理をする
        $this->patch("/attendance/{$attendance->id}", [
            'clock_in_time' => '08:30',
            'clock_out_time' => '17:30',
            'remarks' => '承認済みテスト',
        ]);

        // 管理者が承認する
        $this->actingAs($admin, 'admin');
        $correction = AttendanceCorrection::where('attendance_id', $attendance->id)->first();
        
        $this->patch("/admin/stamp_correction_request/approve/{$correction->id}", [
            'admin_remarks' => '承認しました',
        ]);

        // ユーザーとして申請一覧画面を開く
        $this->actingAs($user);
        $response = $this->get('/stamp_correction_request/list');
        
        // 承認済みに管理者が承認した申請が全て表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('テストユーザー');
        $response->assertSee('承認済みテスト');
    }

    /**
     * 各申請の「詳細」を押下すると申請詳細画面に遷移する
     */
    public function test_detail_link_redirects_to_attendance_detail()
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

        // 勤怠詳細を修正し保存処理をする
        $this->patch("/attendance/{$attendance->id}", [
            'clock_in_time' => '08:30',
            'clock_out_time' => '17:30',
            'remarks' => '詳細リンクテスト',
        ]);

        // 申請一覧画面を開く
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        
        // 「詳細」ボタンが存在することを確認
        $response->assertSee('詳細');
        
        // 詳細リンクが正しいURLを指していることを確認
        $response->assertSee("/attendance/{$attendance->id}");
    }
} 