<?php
namespace App\Http\Routes;

use Illuminate\Contracts\Routing\Registrar;
use LaravelBA\RouteBinder\Routes;

class WelcomeRoutes implements Routes
{
    /**
     * Add all needed routes to the router.
     *
     * NOTE: This methods will NOT be called if the routes are cached,
     * so any binding logic should be done in `addBindings` and not here.
     *
     * @param Registrar $router
     *
     * @return void
     */
    public function addRoutes(Registrar $router)
    {
        $router->group(['middleware' => 'web'], function (Registrar $router) {
            $router->get('/', function () {
                return view('welcome');
            });
        });

        $router->group(['middleware' => 'api', 'prefix' => 'api'], function (Registrar $router) {
            $router->get('/user', function (\Illuminate\Http\Request $request) {
                return $request->user();
            })->middleware('auth:api');
        });
    }
}
