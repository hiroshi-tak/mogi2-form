@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request.css') }}">
@endsection

@section('content')
<div class="request-page">
    <h1 class="title">申請一覧</h1>

    {{-- タブ --}}
    <div class="tabs">
        <a href="{{ route('request.index', ['tab' => 'pending']) }}"
            class="tab {{ $tab === 'pending' ? 'active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('request.index', ['tab' => 'approved']) }}"
            class="tab {{ $tab === 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    <div class="table-wrapper">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>

            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td>
                        <span class="status {{ $req->status }}">
                            {{ $req->status === 'pending' ? '承認待ち' : '承認済み' }}
                        </span>
                    </td>

                    <td>{{ $req->attendance->user->name }}</td>

                    <td>
                        {{ $req->attendance->date->format('Y-m-d') }}
                    </td>

                    <td>
                        {{ $req->note }}
                    </td>

                    <td>
                        {{ $req->created_at->format('Y-m-d H:i') }}
                    </td>

                    <td>
                        <a href="{{ route('attendance.show', ['id' => $req->attendance->date->format('Y-m-d'),'from' => 'request']) }}">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection