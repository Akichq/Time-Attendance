<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
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
     * 会員登録後、認証メールが送信される
     */
    public function test_email_verification_sent_after_registration()
    {
        // メール通知をモックする
        Notification::fake();

        // 会員登録をする
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        // レスポンスが成功することを確認
        $response->assertStatus(302);

        // 認証メールが送信されたことを確認
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function test_verification_notice_page_has_verification_button()
    {
        // ユーザーを作成（メール未認証状態）
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null, // メール未認証
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 1. メール認証導線画面を表示する
        $response = $this->get('/email/verify');
        $response->assertStatus(200); // 正常に表示

        // 「認証はこちらから」ボタンが存在することを確認
        $response->assertSee('認証はこちらから');
        $response->assertSee('verify-btn');

        // 2. 「認証はこちらから」ボタンを押下すると実際のメール認証URLに遷移する
        $buttonResponse = $this->get('/email/verify/process');
        $buttonResponse->assertStatus(302); // リダイレクト

        // 3. 実際のメール認証URLにリダイレクトされることを確認
        $buttonResponse->assertRedirect();
        $redirectUrl = $buttonResponse->headers->get('Location');
        $this->assertStringContainsString('/email/verify/', $redirectUrl);
        $this->assertStringContainsString($user->id, $redirectUrl);
    }

    /**
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_email_verification_redirects_to_attendance_page()
    {
        // ユーザーを作成（メール未認証状態）
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null, // メール未認証
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // メール認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // メール認証を完了する
        $response = $this->get($verificationUrl);

        // 勤怠登録画面に遷移することを確認
        $response->assertRedirect('/attendance');

        // ユーザーのメール認証状態が更新されていることを確認
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }
} 