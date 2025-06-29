<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /**
     * 会員登録画面を表示
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * 会員登録処理
     */
    public function register(RegisterRequest $request)
    {
        // バリデーションはFormRequestで自動実行される

        // ユーザー作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ログイン
        Auth::login($user);

        // ★ここで認証メール送信
        $user->sendEmailVerificationNotification();

        // メール認証が必要な場合はメール認証画面へ、そうでなければ勤怠打刻画面へ
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('attendance.index');
        } else {
            return redirect()->route('verification.notice');
        }
    }
} 