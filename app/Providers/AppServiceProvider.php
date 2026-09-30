<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
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
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip());
        });

        // Pendaftaran: cegah spam akun baru (per email + IP).
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(15, 5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip());
        });

        // Aksi sensitif per akun (ganti kata sandi, pengajuan peran).
        RateLimiter::for('sensitive', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        // Rute bootstrap /deploy: batasi brute-force secret.
        RateLimiter::for('deploy', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        RateLimiter::for('interactions', function (Request $request) {
            return Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(12)->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}
