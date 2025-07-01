@extends('layouts.auth')

@section('title', 'メール認証 | COACHTECH')
@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-container">
    <p class="verify-message-main">登録していただいたメールアドレスに認証メールを送付しました。</p>
    <p class="verify-message-main">メール認証を完了してください。</p>
    <form method="GET" action="/email/verify">
        <button type="submit" class="verify-btn">認証はこちらから</button>
    </form>
    <div class="verify-link">
        <form method="POST" action="{{ route('verification.send') }}" style="display:inline;">
            @csrf
            <button type="submit" class="resend-link">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection