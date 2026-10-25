<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        // Fuerza HTTPS cuando se usa un túnel de Cloudflare para prevenir Mixed Content.
        // Se excluye localhost/127.0.0.1 para que el servidor de desarrollo local (php artisan serve) funcione sin SSL.
        if (config('app.force_https', false) && ! $this->app->runningInConsole()) {
            $host = request()->getHost();
            if (! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                URL::forceScheme('https');
            }
        }
    }
}
