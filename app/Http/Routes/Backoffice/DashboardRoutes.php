<?php
namespace App\Http\Routes\Backoffice;

use App\Http\Controllers\Backoffice\DashboardController;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Router;
use LaravelBA\RouteBinder\RouteBinder;

class DashboardRoutes implements RouteBinder
{
    const HOME = 'backoffice.index';

    /**
     * Bind all needed routes to the router.
     * You may also bind parameters, filters or anything you need to do
     * with the router here.
     *
     * @param \Illuminate\Contracts\Routing\Registrar $router
     *
     * @return void
     */
    public function addRoutes(Registrar $router)
    {
        $router->group(['prefix' => 'backoffice', 'middleware' => ['web', 'security:backoffice']], function (Registrar $router) {
            $router->get('/', ['as' => self::HOME, 'uses' => DashboardController::class . '@dashboard']);
        });
    }

    /**
     * Bind parameters, filters or anything you need to do
     * with the concrete router here.
     *
     * NOTE: If an object that's not an instance (nor an extension) of the concrete
     * \Illuminate\Routing\Router is bound as the \Illuminate\Contracts\Routing\Registrar
     * in the Container, **this method will never be called!**
     *
     * @param Router $router
     *
     * @return void
     */
    public function addBindings(Router $router)
    {
    }
}
