<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // 管理者ガードの場合は管理者ログイン画面にリダイレクト
            if ($request->is('admin/*')) {
                return route('admin.login');
            }
            return route('login');
        }
    }
}
