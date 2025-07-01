@extends('layouts.admin')

@section('title', '勤怠詳細 - 管理者 - COACHTECH')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance-detail.css') }}">
@endsection

@section('content')
<main class="attendance-detail-main">
    <h1 class="attendance-detail-title">勤怠詳細</h1>
    <div class="attendance-detail-card">
        <form action="{{ route('admin.attendance.update', ['attendance' => $attendance->id]) }}" method="POST" class="attendance-detail-form">
            @csrf
            @method('PATCH')
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
                            <input type="time" name="clock_in_time" value="{{ old('clock_in_time', $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '') }}">
                            <span class="attendance-detail-time-sep">〜</span>
                            <input type="time" name="clock_out_time" value="{{ old('clock_out_time', $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '') }}">
                        </div>
                        @error('clock_in_time')<div class="attendance-error-message">{{ $message }}</div>@enderror
                        @error('clock_out_time')<div class="attendance-error-message">{{ $message }}</div>@enderror
                    </td>
                </tr>
                @foreach($breaks as $i => $break)
                <tr>
                    <th>休憩{{ $i+1 }}</th>
                    <td>
                        <div class="attendance-detail-time-row">
                            <input type="time" name="breaks[{{ $i }}][start]" value="{{ old('breaks.'.$i.'.start', $break->break_start_time ? \Carbon\Carbon::parse($break->break_start_time)->format('H:i') : '') }}">
                            <span class="attendance-detail-time-sep">〜</span>
                            <input type="time" name="breaks[{{ $i }}][end]" value="{{ old('breaks.'.$i.'.end', $break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '') }}">
                        </div>
                        @error('breaks.'.$i.'.start')<div class="attendance-error-message">{{ $message }}</div>@enderror
                        @error('breaks.'.$i.'.end')<div class="attendance-error-message">{{ $message }}</div>@enderror
                    </td>
                </tr>
                @endforeach
                <!-- 追加用の空休憩フィールド -->
                <tr>
                    <th>休憩{{ count($breaks)+1 }}</th>
                    <td>
                        <div class="attendance-detail-time-row">
                            <input type="time" name="breaks[{{ count($breaks) }}][start]" value="">
                            <span class="attendance-detail-time-sep">〜</span>
                            <input type="time" name="breaks[{{ count($breaks) }}][end]" value="">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td>
                        <textarea name="remarks" rows="2" class="attendance-detail-remarks">{{ old('remarks', $attendance->remarks) }}</textarea>
                        @error('remarks')<div class="attendance-error-message">{{ $message }}</div>@enderror
                    </td>
                </tr>
            </table>
            </section>
            <div class="attendance-form-actions">
                <button type="submit" class="attendance-submit-button">修正</button>
            </div>
            @if(session('success'))
                <div style="color: #888; margin-top: 8px; text-align: center;">{{ session('success') }}</div>
            @endif
        </form>
    </div>
</main>
@endsection 