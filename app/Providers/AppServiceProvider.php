<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Batasi percobaan menebak kunci/password: 5 kali per menit per halaman.
        RateLimiter::for('kunci', fn (Request $request) => Limit::perMinute(5)->by($request->path().'|'.$request->ip()));
    }
}
