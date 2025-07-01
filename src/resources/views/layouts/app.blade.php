<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'COACHTECH')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
</head>
<body>
    <header class="header-app">
        <div class="header-inner">
            <img src="{{ asset('logo.svg') }}" alt="COACHTECHロゴ" class="header-logo-app">
            <nav class="header-nav">
                @if (isset($attendanceStatus) && $attendanceStatus === 'after_work')
                    <a href="{{ route('attendance.list') }}">今月の出勤一覧</a>
                    <a href="{{ route('correction.list') }}">申請一覧</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="header-nav-button">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('attendance.index') }}">勤怠</a>
                    <a href="{{ route('attendance.list') }}">勤怠一覧</a>
                    <a href="{{ route('correction.list') }}">申請</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="header-nav-button">ログアウト</button>
                    </form>
                @endif
            </nav>
        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html> 