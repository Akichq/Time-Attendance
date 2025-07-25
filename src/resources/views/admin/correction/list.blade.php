@extends('layouts.admin')

@section('title', '申請一覧 - 管理者 - COACHTECH')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/correction-list.css') }}?v=2">
@endsection

@section('content')
<main class="request-list-container">
    <h1 class="request-list-title">申請一覧</h1>
    <nav class="request-list-tabs" aria-label="申請一覧タブ">
        <button type="button" class="request-tab active" data-tab="pending">承認待ち</button>
        <button type="button" class="request-tab" data-tab="approved">承認済み</button>
    </nav>
    <hr class="request-list-divider">
    <section class="request-list-table-section">
        <table class="request-list-table" id="pending-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日付</th>
                    <th>申請理由</th>
                    <th>申請日付</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pending as $correction)
                <tr>
                    <td>承認待ち</td>
                    <td>{{ $correction->attendance->user->name ?? '' }}</td>
                    <td>{{ $correction->attendance->clock_in_time ? $correction->attendance->clock_in_time->format('Y/m/d') : '' }}</td>
                    <td>{{ $correction->remarks }}</td>
                    <td>{{ $correction->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('admin.correction.approve', ['correction' => $correction->id]) }}" class="request-detail-link">詳細</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="request-list-empty">承認待ちの申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <table class="request-list-table" id="approved-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日付</th>
                    <th>申請理由</th>
                    <th>申請日付</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($approved as $correction)
                <tr>
                    <td>承認済み</td>
                    <td>{{ $correction->attendance->user->name ?? '' }}</td>
                    <td>{{ $correction->attendance->clock_in_time ? $correction->attendance->clock_in_time->format('Y/m/d') : '' }}</td>
                    <td>{{ $correction->remarks }}</td>
                    <td>{{ $correction->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('admin.correction.approve', ['correction' => $correction->id]) }}" class="request-detail-link request-detail-link-approved">承認済み</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="request-list-empty">承認済みの申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.request-tab');
        const pendingTable = document.getElementById('pending-table');
        const approvedTable = document.getElementById('approved-table');
        approvedTable.style.display = 'none'; // 初期状態で非表示
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                if (this.dataset.tab === 'pending') {
                    pendingTable.style.display = '';
                    approvedTable.style.display = 'none';
                } else {
                    pendingTable.style.display = 'none';
                    approvedTable.style.display = '';
                }
            });
        });
    });
</script>
@endsection 