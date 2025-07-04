@extends('layouts.app')

@section('title', '勤怠打刻 - COACHTECH')

@section('css')
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <div class="attendance-status">
        <span class="status-label">
            @if($attendanceStatus === 'at_work')
                出勤中
            @elseif($attendanceStatus === 'on_break')
                休憩中
            @elseif($attendanceStatus === 'after_work')
                退勤済
            @else
                勤務外
            @endif
        </span>
    </div>

    <div class="attendance-datetime">
        @php
            $date = \Carbon\Carbon::now();
        @endphp
        <div class="date">{{ $date->format('Y年n月j日') }}({{ $date->jaWeekday() }})</div>
        <div class="time" id="current-time">{{ $date->format('H:i') }}</div>
    </div>

    <div class="attendance-buttons">
        <form action="{{ route('attendance.clock-in') }}" method="POST" class="{{ $attendanceStatus === 'before_work' ? '' : 'form-hidden' }}">
            @csrf
            <button type="submit" class="attendance-btn clock-in">出勤</button>
        </form>

        <form action="{{ route('attendance.clock-out') }}" method="POST" class="{{ $attendanceStatus === 'at_work' ? '' : 'form-hidden' }}">
            @csrf
            <button type="submit" class="attendance-btn clock-out">退勤</button>
        </form>

        <form action="{{ route('attendance.break-start') }}" method="POST" class="{{ $attendanceStatus === 'at_work' ? '' : 'form-hidden' }}">
            @csrf
            <button type="submit" class="attendance-btn break-start">休憩入</button>
        </form>

        <form action="{{ route('attendance.break-end') }}" method="POST" class="{{ $attendanceStatus === 'on_break' ? '' : 'form-hidden' }}">
            @csrf
            <button type="submit" class="attendance-btn break-end">休憩戻</button>
        </form>
    </div>

    <div class="completion-message {{ $attendanceStatus === 'after_work' ? '' : 'completion-message-hidden' }}">
        お疲れ様でした。
    </div>
</div>

<script>
    function updateTime() {
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            timeElement.textContent = `${hours}:${minutes}`;
        }
    }
    setInterval(updateTime, 1000 * 60);
</script>
@endsection