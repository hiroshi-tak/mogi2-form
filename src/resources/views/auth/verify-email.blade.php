@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/email.css') }}">
@endsection

@section('content')
<div class="verify-container">
    <p class="verify-explanation">
        登録していただいたメールアドレスに認証メールを送付しました。
    </p>
    <p class="verify-explanation">
        メール認証を完了してください。
    </p>
    {{-- プロフィール設定へ --}}
    <a class="verify-button" href="http://localhost:8025" target="_blank">
        認証はこちらから
    </a>
    {{-- 認証メール再送 --}}
    <form method="POST" action="{{ route('verification.send') }}" class="resend-form">
        @csrf
        <button type="submit" class="resend-link">
            認証メールを再送する
        </button>
    </form>
</div>
@endsection