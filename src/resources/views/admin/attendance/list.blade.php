@extends('layouts.admin')

@section('title', '勤怠一覧（管理者）')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance-list.css') }}?v=2">
@endsection

@section('content')
<main class="attendance-list-container">
    <h1 class="attendance-list-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>
    <nav class="attendance-date-nav-card" aria-label="日付ナビゲーション">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="attendance-date-link nav-prev">&larr; 前日</a>
        <span class="attendance-date-current">
            <span class="attendance-date-icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="4" width="16" height="14" rx="3" fill="none" stroke="#222" stroke-width="1.5"/>
                    <path d="M2 8H18" stroke="#222" stroke-width="1.5"/>
                    <rect x="6" y="11" width="2" height="2" rx="1" fill="#222"/>
                    <rect x="9" y="11" width="2" height="2" rx="1" fill="#222"/>
                    <rect x="12" y="11" width="2" height="2" rx="1" fill="#222"/>
                </svg>
            </span>
            <span class="attendance-date-text">{{ $date->format('Y/m/d') }}</span>
        </span>
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="attendance-date-link nav-next">翌日 &rarr;</a>
    </nav>
    <section class="attendance-table-section">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i') }}</td>
                    <td>{{ $attendance->clock_out_time ? \Carbon\Carbon::parse($attendance->clock_out_time)->format('H:i') : '' }}</td>
                    <td>{{ $attendance->total_break_time_formatted }}</td>
                    <td>{{ $attendance->total_time_formatted }}</td>
                    <td><a href="{{ route('attendance.show', ['attendance' => $attendance->id]) }}" class="attendance-detail-link">詳細</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="attendance-table-empty">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</main>
@endsection 