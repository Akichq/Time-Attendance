<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class DateTimeTest extends TestCase
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
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_datetime_display()
    {
        // ユーザーを作成（メール認証済み）
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(), // メール認証済み
        ]);

        // 現在の日時を固定（テスト用）
        $now = Carbon::create(2025, 1, 15, 14, 30, 0); // 2025年1月15日 14:30
        Carbon::setTestNow($now);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // レスポンスが成功することを確認
        $response->assertStatus(200);

        // 画面に表示されている日時情報を確認
        $response->assertSee('2025年1月15日');
        $response->assertSee('水'); // 水曜日
        $response->assertSee('14:30'); // 時刻

        // テスト用の時間設定をリセット
        Carbon::setTestNow();
    }
} 