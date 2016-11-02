<?php
namespace App\Http\Routes\Backoffice;

use App\Http\Controllers\Backoffice\DashboardController;
use Illuminate\Contracts\Routing\Registrar;
use LaravelBA\RouteBinder\Routes;

class DashboardRoutes implements Routes
{
    const HOME = 'backoffice.index';

    /**
     * {@inheritdoc}
     */
    public function addRoutes(Registrar $router)
    {
        $router->group(['prefix' => 'backoffice', 'middleware' => ['web', 'security:backoffice']], function (Registrar $router) {
            $router->get('/', ['as' => self::HOME, 'uses' => DashboardController::class . '@dashboard']);
        });
    }
}
