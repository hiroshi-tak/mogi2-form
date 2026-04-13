<header class="site-header">
    <div class="header-inner">
        <div class="header-left">
            <a href="{{ url('/') }}">
                <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" class="header-logo">
            </a>
        </div>

        @if(!Route::is('login','register'))
        <div class="header-right">
            @if(Auth::guard('admin')->check())
            <a class="header-right-item" href="{{ route('admin.attendance.index') }}">
                勤怠一覧
            </a>
            <a class="header-right-item" href="{{ route('admin.staff.list')  }}">
                スタッフ一覧
            </a>
            <a class="header-right-item" href="{{ route('admin.request.index')  }}">
                申請一覧
            </a>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button class="header-logout-btn" type="submit">ログアウト</button>
            </form>
            @elseif(Auth::guard('web')->check())
            <a class="header-right-item" href="{{ route('attendance.index') }}">
                勤怠
            </a>
            <a class="header-right-item" href="{{ route('attendance.list')  }}">
                勤怠一覧
            </a>
            <a class="header-right-item" href="{{ route('request.index')  }}">
                申請
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="header-logout-btn" type="submit">ログアウト</button>
            </form>
            @endif
        </div>
        @endif
    </div>
</header>