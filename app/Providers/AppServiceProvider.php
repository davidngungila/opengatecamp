<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $user = auth()->user();
            $view->with('isCommittee', $user ? $user->hasRole('committee') : false);
            $view->with('isAdmin', $user ? in_array($user->role?->name, ['Super Administrator', 'Chairperson']) : false);
        });
    }
}
