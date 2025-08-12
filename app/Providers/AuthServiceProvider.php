<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{

    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
        $this->registerPolicies();

        Gate::define('isSuperAdmin', function ($user) {
        // Or use your custom logic here
        return isSuperAdmin();
        });

        Gate::define('isAdmin', function ($user) {
            // Or use your custom logic here
            return isAdmin();
        });

        Gate::define('isSales', function ($user) {
            // Or use your custom logic here
            return isSales();
        });

    }
}
