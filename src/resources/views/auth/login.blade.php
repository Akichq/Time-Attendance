@extends('layouts.auth')

@section('title', 'ログイン | COACHTECH')
@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="container">
    <h1>ログイン</h1>
    @if(session('error'))
        <div class="error-message">{{ session('error') }}</div>
    @endif
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
            @error('email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password">
            @error('password')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn-submit">ログイン</button>
    </form>
    <div class="register-link">
        <a href="{{ route('register') }}">会員登録はこちら</a>
    </div>
</div>
@endsection 