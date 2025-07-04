@extends('layouts.auth')

@section('title', 'メール認証 | COACHTECH')
@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-container">
    <p class="verify-message-main">登録していただいたメールアドレスに認証メールを送付しました。</p>
    <p class="verify-message-main">メール認証を完了してください。</p>
    <a href="/email/verify/process" class="verify-btn">認証はこちらから</a>
    <div class="verify-link">
        <form method="POST" action="{{ route('verification.send') }}" class="verify-link-inline">
            @csrf
            <button type="submit" class="resend-link">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection