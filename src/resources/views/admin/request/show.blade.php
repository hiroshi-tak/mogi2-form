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
            <div class="value">{{ $attendanceRequest->attendance->user->name }}</div>
        </div>

        <div class="row">
            <div class="label">日付</div>
            <div class="value">
                {{ $attendanceRequest->attendance->date->format('Y年 n月j日') }}
            </div>
        </div>

        <div class="row">
            <div class="label">出勤・退勤</div>
            <div class="value">
                <div class="time-range value-inner">
                    {{ ($attendanceRequest->clock_in ?? $attendanceRequest->attendance->clock_in)?->format('H:i') }}
                    <span>〜</span>
                    {{ ($attendanceRequest->clock_out ?? $attendanceRequest->attendance->clock_out)?->format('H:i') }}
                </div>
            </div>
        </div>

        @if($attendanceRequest->breakRequests->count())
        @foreach($attendanceRequest->breakRequests as $i => $break)
        <div class="row">
            <div class="label">休憩{{ $i ? $i+1 : '' }}</div>
            <div class="value">
                <div class="time-range value-inner">
                    {{ ($break->start_time)?->format('H:i') }}
                    <span>〜</span>
                    {{ ($break->end_time)?->format('H:i') }}
                </div>
            </div>
        </div>
        @endforeach
        <div class="row">
            <div class="label">休憩{{ $attendanceRequest->breakRequests->count() + 1 }}</div>
            <div class="value">
                <div class="time-range value-inner">
                </div>
            </div>
        </div>
        @else
        @foreach($attendanceRequest->attendance->breaks as $i => $break)
        <div class="row">
            <div class="label">休憩{{ $i ? $i+1 : '' }}</div>
            <div class="value">
                <div class="time-range value-inner">
                    {{ ($break->start_time)?->format('H:i') }}
                    <span>〜</span>
                    {{ ($break->end_time)?->format('H:i') }}
                </div>
            </div>
        </div>
        @endforeach
        <div class="row">
            <div class="label">休憩{{ $attendanceRequest->attendance->breaks->count() + 1 }}</div>
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
                    {{ $attendanceRequest->note }}
                </div>
            </div>
        </div>
    </div>

    <div class="actions">
        @if($attendanceRequest->status === 'pending')
        <form method="POST" action="{{ route('admin.request.approve', $attendanceRequest->id) }}">
            @csrf
            <button class="submit-btn" type="submit">承認</button>
        </form>
        @else
        <button class="disabled-btn" disabled>承認済み</button>
        @endif
    </div>
</div>
@endsection