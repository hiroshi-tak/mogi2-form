@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request.css') }}">
@endsection

@section('content')
<div class="request-page">
    <h1 class="title">申請一覧</h1>

    <div class="tabs">
        <a href="{{ route('admin.request.index', ['status' => 'pending']) }}"
            class="tab {{ $tab === 'pending' ? 'active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('admin.request.index', ['status' => 'approved']) }}"
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
                @forelse($requests as $request)
                <tr>
                    <td>
                        <span class="status {{ $request->status }}">
                            {{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}
                        </span>
                    </td>

                    <td>
                        {{ $request->attendance->user->name }}
                    </td>

                    <td>
                        {{ $request->attendance->date }}
                    </td>

                    <td>
                        {{ $request->note }}
                    </td>

                    <td>
                        {{ $request->created_at }}
                    </td>

                    <td>
                        <a href="{{ route('admin.request.show', $request->id) }}">
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