@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/stamp.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="inner">
        <p class="status">{{$status}}</p>
        <div class="date" id="date">
            <span class="test">
                {{ now()->format('Y年n月j日') }}
            </span>
        </div>
        <div class="time" id="clock">
            <span class="test">
                {{ now()->format('H:i') }}
            </span>
        </div>

        @switch($status)

        @case('勤務外')
        <form method="POST" action="{{ route('clock.in') }}">
            @csrf
            <button class="form_button">出勤</button>
        </form>
        @break

        @case('出勤中')
        <div class="button-row">
            <form method="POST" action="{{ route('clock.out') }}">
                @csrf
                <button class="form_button">退勤</button>
            </form>

            <form method="POST" action="{{ route('break.start') }}">
                @csrf
                <button class="form_break_button">休憩入</button>
            </form>
        </div>
        @break

        @case('休憩中')
        <form method="POST" action="{{ route('break.end') }}">
            @csrf
            <button class="form_break_button">休憩戻</button>
        </form>
        @break

        @case('退勤済')
        <p class="leave">お疲れ様でした。</p>
        @break

        @endswitch
    </div>
</div>

<script>
    setInterval(() => {
        const now = new Date();

        document.getElementById('clock').innerText =
            now.toLocaleTimeString('ja-JP', {
                hour: '2-digit',
                minute: '2-digit'
            });

        const parts = now.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            weekday: 'short'
        }).split('(');

        const ymd = parts[0].split('/');

        document.getElementById('date').innerText =
            `${ymd[0]}年${ymd[1]}月${ymd[2]}日(${parts[1]}`;
    }, 1000);
</script>
@endsection