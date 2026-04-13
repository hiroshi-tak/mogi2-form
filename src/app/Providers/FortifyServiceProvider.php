<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {

                public function toResponse($request)
                {
                    if (Auth::guard('admin')->check()) {
                        session(['auth_guard' => 'admin']);

                        return redirect('/admin/attendance');
                    }

                    if (Auth::guard('web')->check()) {
                        session(['auth_guard' => 'web']);

                        return redirect('/attendance');
                    }

                    return redirect('/');
                }
            };
        });

        $this->app->singleton(
            VerifyEmailResponse::class,
            function () {
                return new class implements VerifyEmailResponse {
                    public function toResponse($request)
                    {
                        return redirect('/attendance');
                    }
                };
            }
        );

        $this->app->singleton(VerifyEmailViewResponse::class, function () {
            return new class implements VerifyEmailViewResponse {
                public function toResponse($request)
                {
                    return view('auth.verify-email');
                }
            };
        });

    }
}
