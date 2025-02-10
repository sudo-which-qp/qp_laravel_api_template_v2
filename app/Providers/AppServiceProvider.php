<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

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
        //
        $this->configureCommands();
        $this->configureModels();
        $this->configureUrl();
    }

    /**
     * Configure application commands.
     */
    private function configureCommands(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        DB::prohibitDestructiveCommands(
            $app->isProduction()
        );
    }

    /**
     * Configure application models.
     */
    private function configureModels(): void
    {
        Schema::defaultStringLength(191);

        Model::shouldBeStrict();
        Model::unguard();

        // in production, log lazy loading violation instead.

        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        if ($app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                $class = get_class($model);

                info("Attempted to lazy load [{$relation} on model [{$class}]]");
            });
        }
    }

    /**
     * Configure application models.
     */
    private function configureUrl(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        if ($app->isProduction()) {
            URL::forceScheme("https");
        }
    }
}
