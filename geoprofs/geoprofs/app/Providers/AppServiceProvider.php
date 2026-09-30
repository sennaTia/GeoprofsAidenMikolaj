<?php

namespace App\Providers;

use App\Models\LeaveRequest;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::define('approve-leave-request', function ($user, LeaveRequest $leaveRequest) {
            return $user->role === 'manager' && $user->id !== $leaveRequest->user_id;
        });
    }
}
