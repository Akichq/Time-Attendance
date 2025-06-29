@extends('layouts.admin')

@section('title', '勤怠詳細 - 管理者 - COACHTECH')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance-detail.css') }}">
@endsection

@section('content')
<main class="attendance-detail-main">
    <h1 class="attendance-detail-title">勤怠詳細</h1>
    <div class="attendance-detail-card">
        <section class="attendance-detail-table-section">
        <table class="attendance-detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>{{ $attendance->clock_in_time ? $attendance->clock_in_time->format('Y年') : '' }}　{{ $attendance->clock_in_time ? $attendance->clock_in_time->format('n月j日') : '' }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <div class="attendance-detail-time-row">
                        <span>{{ $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '-' }}</span>
                        <span class="attendance-detail-time-sep">〜</span>
                        <span>{{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '-' }}</span>
                    </div>
                </td>
            </tr>
            @foreach($breaks as $i => $break)
            <tr>
                <th>休憩{{ $i+1 }}</th>
                <td>
                    <div class="attendance-detail-time-row">
                        <span>{{ $break->break_start_time ? \Carbon\Carbon::parse($break->break_start_time)->format('H:i') : '--:--' }}</span>
                        <span class="attendance-detail-time-sep">〜</span>
                        <span>{{ $break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '--:--' }}</span>
                    </div>
                </td>
            </tr>
            @endforeach
            <tr>
                <th>備考</th>
                <td>{{ $attendance->remarks ?? '' }}</td>
            </tr>
        </table>
        </section>
    </div>
</main>
@endsection 