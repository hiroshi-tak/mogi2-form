@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endsection

@section('content')
<div class="attendance-page">

    <h1 class="title">勤怠一覧</h1>

    <div class="month-nav">
        <a href="{{ route('attendance.list', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}">← 前月</a>
        <span class="month">
            <i class="fa-solid fa-calendar-days"></i>
            {{ $month->format('Y/m') }}
        </span>
        <a href="{{ route('attendance.list', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}">翌月 →</a>
    </div>
    <div class="table-wrapper">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dates as $date)

                @php
                $attendance = $attendances[$date->format('Y-m-d')] ?? null;
                @endphp

                <tr>
                    <td>
                        {{ $date->format('m/d') }}
                        ({{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }})
                    </td>

                    <td>{{ $attendance?->clock_in?->format('H:i') ?? '' }}</td>
                    <td>{{ $attendance?->clock_out?->format('H:i') ?? '' }}</td>

                    <td>
                        {{ $attendance?->break_seconds ? gmdate('H:i', $attendance->break_seconds) : '' }}
                    </td>

                    <td>
                        {{ $attendance?->work_seconds ? gmdate('H:i', $attendance->work_seconds) : '' }}
                    </td>

                    <td>
                        @if($date->lt(now()->startOfDay()))
                        <a href="{{ route('attendance.show', ['id' => $date->format('Y-m-d')]) }}">
                            詳細
                        </a>
                        @endif
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection