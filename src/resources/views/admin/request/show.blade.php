@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="detail-page">

    <h1 class="title">勤怠詳細</h1>

    <div class="card">
        <div class="row">
            <div class="label">名前</div>
            <div class="value">{{ $request->attendance->user->name }}</div>
        </div>

        <div class="row">
            <div class="label">日付</div>
            <div class="value">
                {{ $request->attendance->date->format('Y年 n月j日') }}
            </div>
        </div>

        <div class="row">
            <div class="label">出勤・退勤</div>
            <div class="value">
                <div class="time-range value-inner">
                    {{ optional($request->clock_in)->format('H:i') ?? optional($request->attendance->clock_in)->format('H:i') }}
                    <span>〜</span>
                    {{ optional($request->clock_out)->format('H:i') ?? optional($request->attendance->clock_out)->format('H:i') }}
                </div>
            </div>
        </div>

        @if($request->breakRequests->count())
        @foreach($request->breakRequests as $i => $break)
        <div class="row">
            <div class="label">休憩{{ $i ? $i+1 : '' }}</div>
            <div class="value">
                <div class="time-range value-inner">
                    {{ optional($break->start_time)->format('H:i') }}
                    <span>〜</span>
                    {{ optional($break->end_time)->format('H:i') }}
                </div>
            </div>
        </div>
        @endforeach
        <div class="row">
            <div class="label">休憩{{ $request->breakRequests->count() + 1 }}</div>
            <div class="value">
                <div class="time-range value-inner">
                </div>
            </div>
        </div>
        @else
        @foreach($request->attendance->breaks as $i => $break)
        <div class="row">
            <div class="label">休憩{{ $i ? $i+1 : '' }}</div>
            <div class="value">
                <div class="time-range value-inner">
                    {{ optional($break->start_time)->format('H:i') }}
                    <span>〜</span>
                    {{ optional($break->end_time)->format('H:i') }}
                </div>
            </div>
        </div>
        @endforeach
        <div class="row">
            <div class="label">休憩{{ $request->attendance->breaks->count() + 1 }}</div>
            <div class="value">
                <div class="time-range value-inner">
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="label">備考</div>
            <div class="value">
                <div class="value-inner">
                    {{ $request->note }}
                </div>
            </div>
        </div>
    </div>

    <div class="actions">
        @if($request->status === 'pending')
        <form method="POST" action="{{ route('admin.request.approve', $request->id) }}">
            @csrf
            <button class="submit-btn" type="submit">承認</button>
        </form>
        @else
        <button class="disabled-btn" disabled>承認済み</button>
        @endif
    </div>
</div>
@endsection