@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="detail-page">

    <h1 class="title">勤怠詳細</h1>

    <form method="POST" action="{{ route('attendance.update', ['id' => $attendance?->date?->format('Y-m-d') ?? $date->format('Y-m-d')]) }}">
        @csrf

        <input type="hidden" name="date" value="{{ $attendance?->date?->format('Y-m-d') ?? $date->format('Y-m-d') }}">

        <div class="card">
            <div class="row">
                <div class="label">名前</div>
                <div class="value">{{ $user->name}}</div>
            </div>

            <div class="row">
                <div class="label">日付</div>
                <div class="value">
                    {{ $attendance?->date?->format('Y年 n月j日') ?? $date->format('Y年 n月j日') }}
                </div>
            </div>

            <div class="row">
                <div class="label">出勤・退勤</div>
                <div class="value">
                    <div class="time-range value-inner">
                        <input type="time" name="clock_in"
                            value="{{ old('clock_in', ($pendingRequest?->clock_in ?? $attendance?->clock_in)?->format('H:i')) }}"
                            @if($pendingRequest) disabled @endif>
                        <span>〜</span>

                        <input type="time" name="clock_out"
                            value="{{ old('clock_out', ($pendingRequest?->clock_out ?? $attendance?->clock_out)?->format('H:i')) }}"
                            @if($pendingRequest) disabled @endif>
                    </div>
                    <div class="error">
                        @if ($errors->has('clock_in'))
                        {{ $errors->first('clock_in') }}
                        @elseif ($errors->has('clock_out'))
                        {{ $errors->first('clock_out') }}
                        @endif
                    </div>
                </div>
            </div>

            @php
            $breaks = $pendingRequest
            ? $pendingRequest->breakRequests
            : ($attendance?->breaks ?? collect());
            @endphp

            @forelse($breaks as $i => $break)
            <div class="row">
                <div class="label">休憩{{ $i ? $i+1 : '' }}</div>
                <div class="value">
                    <div class="time-range value-inner">
                        <input type="time" name="breaks[{{ $i }}][start]"
                            value="{{ old("breaks.$i.start", $break->start_time?->format('H:i')) }}"
                            @if($pendingRequest) disabled @endif>

                        <span>〜</span>

                        <input type="time" name="breaks[{{ $i }}][end]"
                            value="{{ old("breaks.$i.end", $break->end_time?->format('H:i')) }}"
                            @if($pendingRequest) disabled @endif>
                    </div>
                    <div class="error">
                        @if ($errors->has("breaks.$i.start"))
                        {{ $errors->first("breaks.$i.start") }}
                        @elseif ($errors->has("breaks.$i.end"))
                        {{ $errors->first("breaks.$i.end") }}
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            @if(!$pendingRequest)
            <div class="row">
                <div class="label">休憩{{ $breaks->count() + 1 }}</div>
                <div class="value">
                    <div class="time-range value-inner">
                        <input type="time" name="breaks[new][start]" value="{{ old('breaks.new.start') }}">
                        <span>〜</span>
                        <input type="time" name="breaks[new][end]" value="{{ old('breaks.new.end') }}">
                    </div>
                    <div class="error">
                        @if ($errors->has('breaks.new.start'))
                        {{ $errors->first('breaks.new.start') }}
                        @elseif ($errors->has('breaks.new.end'))
                        {{ $errors->first('breaks.new.end') }}
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <div class="row">
                <div class="label">備考</div>
                <div class="value">
                    <div class="value-inner">
                        <textarea name="note" @if($pendingRequest) disabled @endif>{{ old('note', $pendingRequest?->note ?? $attendance?->note) }}</textarea>
                    </div>
                    <div class="error">
                        @error('note')
                        {{ $message }}
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            @if($attendance && $pendingRequest)
            <p class="pending">承認待ちのため修正はできません</p>
            @else
            <button class="submit-btn">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection