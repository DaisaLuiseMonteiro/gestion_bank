<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\AuditLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('auditlog', function($app){
            return new AuditLogger();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forcer l'URL racine et le schéma HTTPS en environnement non local,
        // même si la config a été mise en cache pendant le build.
        $appEnv = config('app.env');
        $appUrl = env('APP_URL', config('app.url'));

        if ($appUrl) {
            // S'assure que l'URL racine utilisée par les générateurs de liens vient de APP_URL runtime
            URL::forceRootUrl($appUrl);
        }

        if ($appEnv !== 'local') {
            URL::forceScheme('https');
        }
    }
}
