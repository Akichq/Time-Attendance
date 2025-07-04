@extends('layouts.admin')

@section('title', '修正申請承認 - 管理者 - COACHTECH')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance-detail.css') }}?v=1">
@endsection

@section('content')
<main class="attendance-detail-main">
    <h1 class="attendance-detail-title">勤怠詳細</h1>
    <div class="attendance-detail-card">
        <form id="approval-form" class="attendance-detail-form">
            @csrf
            @method('PATCH')
            <section class="attendance-detail-table-section">
            <table class="attendance-detail-table">
                <tr>
                    <th>名前</th>
                    <td>{{ optional(optional($correction->attendance)->user)->name ?? '' }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>{{ optional($correction->attendance)->clock_in_time ? optional($correction->attendance)->clock_in_time->format('Y年') : '' }}　{{ optional($correction->attendance)->clock_in_time ? optional($correction->attendance)->clock_in_time->format('n月j日') : '' }}</td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div class="attendance-detail-time-row">
                            <span>{{ $correction->requested_clock_in_time ? \Carbon\Carbon::parse($correction->requested_clock_in_time)->format('H:i') : '-' }}</span>
                            <span class="attendance-detail-time-sep">〜</span>
                            <span>{{ $correction->requested_clock_out_time ? \Carbon\Carbon::parse($correction->requested_clock_out_time)->format('H:i') : '-' }}</span>
                        </div>
                    </td>
                </tr>
                @php
                    $breaks = $requestedBreaks['existing'] ?? [];
                    $newBreaks = $requestedBreaks['new'] ?? [];
                @endphp
                @foreach($breaks as $i => $break)
                <tr>
                    <th>休憩{{ $i+1 }}</th>
                    <td>
                        <div class="attendance-detail-time-row">
                            <span>{{ $break['break_start_time'] ?? '--:--' }}</span>
                            <span class="attendance-detail-time-sep">〜</span>
                            <span>{{ $break['break_end_time'] ?? '--:--' }}</span>
                        </div>
                    </td>
                </tr>
                @endforeach
                @foreach($newBreaks as $i => $break)
                <tr>
                    <th>休憩{{ count($breaks) + $i + 1 }}</th>
                    <td>
                        <div class="attendance-detail-time-row">
                            <span>{{ $break['break_start_time'] ?? '--:--' }}</span>
                            <span class="attendance-detail-time-sep">〜</span>
                            <span>{{ $break['break_end_time'] ?? '--:--' }}</span>
                        </div>
                    </td>
                </tr>
                @endforeach
                <tr>
                    <th>備考</th>
                    <td>{{ $correction->remarks }}</td>
                </tr>
            </table>
            </section>
            <div class="attendance-detail-actions attendance-detail-actions-right">
                @if($correction->status === 'pending')
                    <button type="button" id="approval-button" class="attendance-detail-submit" data-correction-id="{{ $correction->id }}">承認</button>
                @elseif($correction->status === 'approved')
                    <button type="button" class="attendance-detail-submit attendance-detail-submit-approved" disabled>承認済み</button>
                @endif
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const approvalButton = document.getElementById('approval-button');
    const form = document.getElementById('approval-form');

    approvalButton.addEventListener('click', function() {
        const correctionId = this.getAttribute('data-correction-id');
        const token = document.querySelector('input[name="_token"]').value;

        this.disabled = true;
        this.textContent = '処理中...';

        fetch(`/admin/stamp_correction_request/approve/${correctionId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.textContent = '承認済み';
                this.style.backgroundColor = '#888';
                this.style.color = 'white';
                this.style.cursor = 'default';
                this.disabled = true;
            } else {
                this.disabled = false;
                this.textContent = '承認';
                alert('承認処理中にエラーが発生しました。');
            }
        })
        .catch(error => {
            this.disabled = false;
            this.textContent = '承認';
            alert('承認処理中にエラーが発生しました。');
            console.error('Error:', error);
        });
    });
});
</script>
@endsection