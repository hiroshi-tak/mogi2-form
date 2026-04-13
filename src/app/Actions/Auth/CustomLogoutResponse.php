<?php

namespace App\Actions\Auth;

use Laravel\Fortify\Contracts\LogoutResponse;

class CustomLogoutResponse implements LogoutResponse
{
    public function toResponse($request)
    {
        $guard = session('auth_guard');

        session()->forget('auth_guard');

        if ($guard === 'admin') {
            return redirect()->route('admin.login');
        }

        return redirect('/login');
    }
}
