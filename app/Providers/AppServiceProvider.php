<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Laravel merges its built-in local disk into application configuration.
        // Remove it before filesystem boot registers local /storage routes.
        $disks = config('filesystems.disks');
        unset($disks['local']);
        config(['filesystems.disks' => $disks]);

        $this->app->bind(\App\Contracts\FileUploadServiceInterface::class, \App\Services\MinioFileUploadService::class);
        $this->app->bind(\App\Contracts\Repositories\MasterRepositoryInterface::class, \App\Repositories\MasterRepository::class);
        $this->app->bind(\App\Contracts\Repositories\UserRepositoryInterface::class, \App\Repositories\UserRepository::class);
        $this->app->bind(\App\Contracts\Repositories\DocumentViewRepositoryInterface::class, \App\Repositories\DocumentViewRepository::class);
        $this->app->bind(\App\Contracts\Services\DocumentViewServiceInterface::class, \App\Services\DocumentViewService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
        \Illuminate\Support\Facades\RateLimiter::for('login', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('user'))));
        \Illuminate\Support\Facades\RateLimiter::for('api', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->session()->get('auth.user', $request->ip())));
        view()->composer(['errors.503', 'errors::503'], function ($view) {
            $view->with('maintenanceHours', app(\App\Services\MaintenanceSettings::class)->estimatedHours());
        });
    }
}
