<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentMethodService::class, function ($app) {
            return new PaymentMethodService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->id == 1 ? true : null;
        });

        // Configure email settings from database if settings table exists
        if (Schema::hasTable('settings')) {
            try {
                $setting = Setting::first();
                if ($setting && $setting->email) {
                    Config::set('mail.from.address', $setting->email);
                    Config::set('mail.from.name', $setting->bname ?? 'Appointment System');
                }
            } catch (\Exception $e) {
                // Failed to load settings, use defaults
            }
        }
    }
}
