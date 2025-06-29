<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'COACHTECH')</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    @yield('css')
</head>
<body>
    <header class="header">
        <img src="{{ asset('logo.svg') }}" alt="COACHTECHロゴ" class="header-logo">
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html> 