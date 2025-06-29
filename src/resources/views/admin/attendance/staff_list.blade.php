@extends('layouts.admin')

@section('title', $user->name . 'さんの勤怠')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-attendance-list.css') }}">
@endsection

@section('content')
<div class="staff-attendance-container">
    <h1 class="staff-attendance-title">{{ $user->name }}さんの勤怠</h1>
    <div class="staff-attendance-header-wrapper">
        <div class="staff-attendance-header">
            <form method="GET" action="" class="month-switch-form">
                <button type="submit" name="month" value="{{ $prevMonth }}" class="month-switch-btn">&#8592; 前月</button>
                <span class="current-month">{{ \Carbon\Carbon::parse($month . '-01')->format('Y/m') }}</span>
                <button type="submit" name="month" value="{{ $nextMonth }}" class="month-switch-btn">翌月 &#8594;</button>
            </form>
        </div>
    </div>
    <div class="staff-attendance-table-wrapper">
        <table class="staff-attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as $day)
                <tr>
                    <td>{{ $day['date']->format('m/d') }}({{ $day['weekday'] }})</td>
                    <td>{{ $day['attendance'] && $day['attendance']->clock_in_time ? $day['attendance']->clock_in_time->format('H:i') : '' }}</td>
                    <td>{{ $day['attendance'] && $day['attendance']->clock_out_time ? $day['attendance']->clock_out_time->format('H:i') : '' }}</td>
                    <td>{{ $day['break'] }}</td>
                    <td>{{ $day['total'] }}</td>
                    <td>
                        @if($day['attendance'])
                        <a href="{{ url('/attendance/' . $day['attendance']->id) }}" class="attendance-detail-link">詳細</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <form method="GET" action="{{ url('/admin/attendance/staff/' . $user->id . '/csv') }}" class="csv-export-form">
        <input type="hidden" name="month" value="{{ $month }}">
        <button type="submit" class="csv-export-btn">CSV出力</button>
    </form>
</div>
@endsection 