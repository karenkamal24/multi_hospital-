<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Auth\User;

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
       
        Gate::before(function ($user, $ability) {
            // التحقق من user_type أولاً
            if ($user && $user->user_type === 'super_admin') {
                return true;
            }


            if ($user && method_exists($user, 'hasRole')) {
                if ($user->hasRole('super_admin')) {
                    return true;
                }
            }

            return null; // دع الـ Gates الأخرى تتحقق
        });
    }
}
