@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="detail-page">
    <h1 class="title">勤怠詳細</h1>

    @if(session('success'))
    <p class="success">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.attendance.update', ['id' => $date,'user_id' => $user->id]) }}">
        @csrf

        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <div class="card">
            <div class="row">
                <div class="label">名前</div>
                <div class="value">{{ $user->name }}</div>
            </div>

            <div class="row">
                <div class="label">日付</div>
                <div class="value">
                    {{ $attendance?->date?->format('Y年 n月j日') ?? \Carbon\Carbon::parse($date)->format('Y年 n月j日') }}
                </div>
            </div>

            <div class="row">
                <div class="label">出勤・退勤</div>
                <div class="value">
                    <div class="time-range value-inner">
                        <input type="time" name="clock_in"
                            value="{{ old('clock_in', optional($attendance?->clock_in)->format('H:i')) }}">
                        <span>〜</span>
                        <input type="time" name="clock_out"
                            value="{{ old('clock_out', optional($attendance?->clock_out)->format('H:i')) }}">
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

            @foreach($attendance?->breaks ?? [] as $i => $break)
            <div class="row">
                <div class="label">休憩{{ $i ? $i+1 : '' }}</div>
                <div class="value">
                    <div class="time-range value-inner">
                        <input type="time"
                            name="breaks[{{ $break->id }}][start]"
                            value="{{ old("breaks.$break->id.start", $break->start_time?->format('H:i')) }}">
                        <span>〜</span>
                        <input type="time"
                            name="breaks[{{ $break->id }}][end]"
                            value="{{ old("breaks.$break->id.end", $break->end_time?->format('H:i')) }}">
                    </div>
                    <div class="error">
                        @if ($errors->has("breaks.$break->id.start"))
                        {{ $errors->first("breaks.$break->id.start") }}
                        @elseif ($errors->has("breaks.$break->id.end"))
                        {{ $errors->first("breaks.$break->id.end") }}
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            <div class="row">
                <div class="label">休憩{{ ($attendance?->breaks?->count() ?? 0) + 1 }}</div>
                <div class="value">
                    <div class="time-range value-inner">
                        <input type="time" name="breaks[new_1][start]"
                            value="{{ old('breaks.new_1.start') }}">
                        <span>〜</span>
                        <input type="time" name="breaks[new_1][end]"
                            value="{{ old('breaks.new_1.end') }}">
                    </div>
                    <div class="error">
                        @if ($errors->has('breaks.new_1.start'))
                        {{ $errors->first('breaks.new_1.start') }}
                        @elseif ($errors->has('breaks.new_1.end'))
                        {{ $errors->first('breaks.new_1.end') }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="label">備考</div>
                <div class="value">
                    <div class="value-inner">
                        <textarea name="note">{{ old('note', $attendance?->note) }}</textarea>
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
            @if($isPending)
            <p class="pending">承認待ちのため修正はできません</p>
            @else
            <button class="submit-btn" type="submit">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection