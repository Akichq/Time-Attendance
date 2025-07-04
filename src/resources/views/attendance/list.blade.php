@extends('layouts.app')

@section('title', '勤怠一覧 - COACHTECH')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<main class="list-main">
    <h1 class="list-title">勤怠一覧</h1>

    <div class="month-navigation-wrapper">
        <nav class="month-navigation">
            <a href="{{ route('attendance.list', ['month' => $previousMonth]) }}" class="month-navigation__link--previous">&#8592; 前月</a>
            <span class="month-navigation__current-month">
                <svg class="calendar-icon calendar-icon-inline" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#222" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span class="month-navigation__current-month-text">{{ $targetMonth->format('Y/m') }}</span>
            </span>
            <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="month-navigation__link--next">翌月 &#8594;</a>
        </nav>
    </div>
    <div class="attendance-list-container">
        <table class="attendance-table">
            <thead class="attendance-table__header">
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody class="attendance-table__body">
                @foreach ($calendarDays as $date)
                <tr>
                    <td>{{ $date->format('m/d') }}({{ $date->jaWeekday() }})</td>
                    @if (isset($attendances[$date->format('Y-m-d')]))
                        @php $attendance = $attendances[$date->format('Y-m-d')]; @endphp
                        <td>{{ $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '-' }}</td>
                        <td>{{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '-' }}</td>
                        <td>{{ $attendance->total_break_time ? $attendance->total_break_time->format('%H:%I') : '00:00' }}</td>
                        <td>{{ $attendance->total_time }}</td>
                        <td><a href="{{ route('attendance.show', ['attendance' => $attendance->id]) }}">詳細</a></td>
                    @else
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection 