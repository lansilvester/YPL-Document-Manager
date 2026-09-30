<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'staff' => EnsureStaff::class,
            'superadmin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        // 413 dari ValidatePostSize (melebihi post_max_size server) → kembalikan ke form
        // dengan pesan Bahasa Indonesia, bukan halaman error kosong.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withErrors(['file' => 'File terlalu besar untuk server (maks 9 MB). Kecilkan atau kompres dulu PDF-nya.'])->withInput();
        });
    })->create();
