<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // CSRFトークンを無効にする
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_required_validation()
    {
        // ユーザーを登録する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // メールアドレス以外のユーザー情報を入力してログイン処理を行う
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_required_validation()
    {
        // ユーザーを登録する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // パスワード以外のユーザー情報を入力してログイン処理を行う
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_invalid_credentials_validation()
    {
        // ユーザーを登録する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 誤ったメールアドレスのユーザー情報を入力してログイン処理を行う
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }
} 