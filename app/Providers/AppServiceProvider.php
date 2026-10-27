<?php

namespace App\Providers;

use App\Contracts\FileUploadServiceInterface;
use App\Contracts\Repositories\DocumentViewRepositoryInterface;
use App\Contracts\Repositories\MasterRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\DocumentViewServiceInterface;
use App\Repositories\DocumentViewRepository;
use App\Repositories\MasterRepository;
use App\Repositories\UserRepository;
use App\Services\DocumentViewService;
use App\Services\MaintenanceSettings;
use App\Services\MinioFileUploadService;
use App\Services\RoleAccess;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
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

        $this->app->bind(FileUploadServiceInterface::class, MinioFileUploadService::class);
        $this->app->bind(MasterRepositoryInterface::class, MasterRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(DocumentViewRepositoryInterface::class, DocumentViewRepository::class);
        $this->app->bind(DocumentViewServiceInterface::class, DocumentViewService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('DOCKER_DEV', false)) {
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }

        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('user'))));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->session()->get('auth.user', $request->ip())));

        view()->composer(['errors.503', 'errors::503'], function ($view) {
            $view->with('maintenanceHours', app(MaintenanceSettings::class)->estimatedHours());
        });

        // Request macros for clean role & authorization checks
        Request::macro('role', fn () => RoleAccess::role($this));
        Request::macro('isAdmin', fn () => RoleAccess::isAdmin($this));
        Request::macro('canManageLearning', fn () => RoleAccess::canManageLearning($this));
        Request::macro('canManageDocuments', fn () => RoleAccess::canManageDocuments($this));
    }
}
