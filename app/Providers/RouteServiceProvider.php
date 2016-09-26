<?php

namespace App\Providers;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define the routes for the application.
     *
     * @param Registrar $router
     */
    public function map(Registrar $router)
    {
        $this->mapApiRoutes($router);

        $this->mapWebRoutes($router);

        //
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param Router $router
     */
    public function bind(Router $router)
    {
        // $router->bind('user_id', function () { /* ... */ });
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param Registrar $router
     */
    protected function mapWebRoutes(Registrar $router)
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Here is where you can register API routes for your application.
     * These routes are typically stateless.
     *
     * @param Registrar $router
     */
    protected function mapApiRoutes(Registrar $router)
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->app->call([$this, 'bind']);

        parent::boot();
    }
}
