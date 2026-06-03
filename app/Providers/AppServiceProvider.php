<?php

namespace App\Providers;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Anonymous visitors are "authenticated" for the board presence channel
        // by their session id, so we can count who's watching the pass without
        // requiring a login.
        Auth::viaRequest('visitor', fn (Request $request): GenericUser => new GenericUser([
            'id' => $request->session()->getId(),
        ]));
    }
}
