<?php

namespace App\Providers;

use App\Infrastructure\Doctrine\Repositories as Doctrine;
use Illuminate\Support\ServiceProvider;
use ProjectName\Repositories;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Implementation bindings.
     *
     * @var string[]
     */
    private $bindings = [
        //Generic Repositories
        Repositories\PersistRepository::class => Doctrine\DoctrinePersistRepository::class,

        //Read Repositories
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }

        if (config('app.debug')) {
            $this->app->register(\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class);
            $this->app->register(\PrettyRoutes\ServiceProvider::class);
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }
}
