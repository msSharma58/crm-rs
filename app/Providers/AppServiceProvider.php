<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Tenancy\TenantContext;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Lead;
use App\Policies\BookingPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\LeadPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);

        $this->configureRateLimiting();

        $this->app->resolving(PermissionRegistrar::class, function (PermissionRegistrar $registrar): void {
            $registrar->setPermissionsTeamId(
                app(TenantContext::class)->getOrganizationId()
            );
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
