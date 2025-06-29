<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'COACHTECH')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
    @yield('css')
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header__logo">
            <img src="{{ asset('logo.svg') }}" alt="COACHTECH">
        </div>
        <nav class="admin-header__nav">
            <ul>
                <li><a href="{{ route('admin.attendance.list') }}">勤怠一覧</a></li>
                <li><a href="{{ route('admin.correction.list') }}">申請一覧</a></li>
                <li><a href="{{ route('admin.staff.list') }}">スタッフ一覧</a></li>
                <li>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="admin-header__nav-button">ログアウト</button>
                    </form>
                </li>
            </ul>
        </nav>
    </header>

    <main class="admin-main">
        @yield('content')
    </main>

</body>
</html> 