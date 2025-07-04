@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
<main class="attendance-detail-main">
    <h1 class="attendance-detail-title">勤怠詳細</h1>
    <div class="attendance-detail-card">
        <form action="{{ route('attendance.requestUpdate', ['attendance' => $attendance->id]) }}" method="POST" class="attendance-detail-form">
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
                            <input type="time" name="clock_in_time" value="{{ old('clock_in_time', $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '') }}" {{ $isPending ? 'disabled' : '' }}>
                            <span class="attendance-detail-time-sep">〜</span>
                            <input type="time" name="clock_out_time" value="{{ old('clock_out_time', $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '') }}" {{ $isPending ? 'disabled' : '' }}>
                        </div>
                        @error('clock_in_time')<div class="attendance-error-message">{{ $message }}</div>@enderror
                        @error('clock_out_time')<div class="attendance-error-message">{{ $message }}</div>@enderror
                    </td>
                </tr>
                @foreach($breaks as $i => $break)
                    @if($break)
                    <tr>
                        <th>休憩{{ $i+1 }}</th>
                        <td>
                            <div class="attendance-detail-time-row">
                                <input type="time" name="breaks[{{ $i }}][break_start_time]" value="{{ old('breaks.'.$i.'.break_start_time', $break->break_start_time ? \Carbon\Carbon::parse($break->break_start_time)->format('H:i') : '') }}" {{ $isPending ? 'disabled' : '' }}>
                                <span class="attendance-detail-time-sep">〜</span>
                                <input type="time" name="breaks[{{ $i }}][break_end_time]" value="{{ old('breaks.'.$i.'.break_end_time', $break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '') }}" {{ $isPending ? 'disabled' : '' }}>
                            </div>
                            @error('breaks.'.$i.'.break_start_time')<div class="attendance-error-message">{{ $message }}</div>@enderror
                            @error('breaks.'.$i.'.break_end_time')<div class="attendance-error-message">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    @endif
                @endforeach
                <tr>
                    <th>休憩{{ count($breaks)+1 }}</th>
                    <td>
                        <div class="attendance-detail-time-row">
                            <input type="time" name="new_breaks[0][break_start_time]" value="{{ old('new_breaks.0.break_start_time', '') }}" {{ $isPending ? 'disabled' : '' }}>
                            <span class="attendance-detail-time-sep">〜</span>
                            <input type="time" name="new_breaks[0][break_end_time]" value="{{ old('new_breaks.0.break_end_time', '') }}" {{ $isPending ? 'disabled' : '' }}>
                        </div>
                        @error('new_breaks.0.break_start_time')<div class="attendance-error-message">{{ $message }}</div>@enderror
                        @error('new_breaks.0.break_end_time')<div class="attendance-error-message">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td>
                        <textarea name="remarks" rows="2" class="attendance-detail-remarks" {{ $isPending ? 'disabled' : '' }}>{{ old('remarks', $attendance->remarks) }}</textarea>
                        @error('remarks')<div class="attendance-error-message">{{ $message }}</div>@enderror
                    </td>
                </tr>
            </table>
            </section>
            <div class="attendance-form-actions">
                @if($isPending)
                    <span class="attendance-pending-message attendance-pending-message-inline">
                        承認待ちのため修正はできません。
                    </span>
                @elseif($isApproved)
                    <button type="button" class="attendance-submit-button attendance-submit-button-disabled" disabled>承認済み</button>
                @else
                    <button type="submit" class="attendance-submit-button">修正</button>
                @endif
            </div>
            @if(session('success'))
                <div class="attendance-success-message">{{ session('success') }}</div>
            @endif
        </form>
    </div>
</main>
@endsection