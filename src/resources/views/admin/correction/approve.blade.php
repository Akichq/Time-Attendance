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
                    <td>{{ $correction->attendance->user->name ?? '' }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>{{ $correction->attendance->clock_in_time ? $correction->attendance->clock_in_time->format('Y年') : '' }}　{{ $correction->attendance->clock_in_time ? $correction->attendance->clock_in_time->format('n月j日') : '' }}</td>
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
                    $breaks = json_decode($correction->requested_breaks, true)['existing'] ?? [];
                @endphp
                <tr>
                    <th>休憩1</th>
                    <td>
                        <div class="attendance-detail-time-row">
                            <span>{{ isset($breaks[0]['break_start_time']) ? $breaks[0]['break_start_time'] : '--:--' }}</span>
                            <span class="attendance-detail-time-sep">〜</span>
                            <span>{{ isset($breaks[0]['break_end_time']) ? $breaks[0]['break_end_time'] : '--:--' }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>休憩2</th>
                    <td>
                        <div class="attendance-detail-time-row">
                            <span>{{ isset($breaks[1]['break_start_time']) ? $breaks[1]['break_start_time'] : '--:--' }}</span>
                            <span class="attendance-detail-time-sep">〜</span>
                            <span>{{ isset($breaks[1]['break_end_time']) ? $breaks[1]['break_end_time'] : '--:--' }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td>{{ $correction->remarks }}</td>
                </tr>
            </table>
            </section>
            <div class="attendance-detail-actions" style="text-align: right; margin-top: 24px;">
                <button type="button" id="approval-button" class="attendance-detail-submit" data-correction-id="{{ $correction->id }}">承認</button>
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
        
        // ボタンを無効化
        this.disabled = true;
        this.textContent = '処理中...';
        
        // 非同期で承認処理を実行
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
                // 成功時：ボタンを「承認済み」に変更
                this.textContent = '承認済み';
                this.style.backgroundColor = '#28a745';
                this.style.color = 'white';
                this.style.cursor = 'default';
            } else {
                // エラー時：ボタンを元に戻す
                this.disabled = false;
                this.textContent = '承認';
                alert('承認処理中にエラーが発生しました。');
            }
        })
        .catch(error => {
            // エラー時：ボタンを元に戻す
            this.disabled = false;
            this.textContent = '承認';
            alert('承認処理中にエラーが発生しました。');
            console.error('Error:', error);
        });
    });
});
</script>
@endsection 