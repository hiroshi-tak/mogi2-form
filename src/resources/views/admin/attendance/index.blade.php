@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endsection

@section('content')
<div class="staff-page">

    <h1 class="title">{{ $date->format('Y年n月j日') }}の勤怠</h1>

    <div class="day-nav">
        <a href="{{ route('admin.attendance.index', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}">← 前日</a>
        <span class="day">
            <i class="fa-solid fa-calendar-days"></i>
            {{ $date->format('Y/m/d') }}
        </span>
        <a href="{{ route('admin.attendance.index', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}">翌日 →</a>
    </div>
    <div class="table-wrapper">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)

                @php
                $attendance = $user->attendances->first();
                @endphp

                <tr>
                    <td>{{ $user->name }}</td>

                    {{-- 出勤 --}}
                    <td>
                        {{ $attendance?->clock_in?->format('H:i') ?? '' }}
                    </td>

                    {{-- 退勤 --}}
                    <td>
                        {{ $attendance?->clock_out?->format('H:i') ?? '' }}
                    </td>

                    {{-- 休憩 --}}
                    <td>
                        {{ $attendance?->break_seconds ? gmdate('H:i', $attendance->break_seconds) : '' }}
                    </td>

                    {{-- 合計 --}}
                    <td>
                        {{ $attendance?->work_seconds ? gmdate('H:i', $attendance->work_seconds) : '' }}
                    </td>

                    {{-- 詳細 --}}
                    <td>
                        @if($date->lt(now()->startOfDay()))
                            <a href="{{ route('admin.attendance.show', ['id' => $date->format('Y-m-d'),'user_id' => $user->id]) }}">
                                詳細
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection