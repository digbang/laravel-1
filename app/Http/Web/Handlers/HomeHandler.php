<?php

namespace App\Http\Web\Handlers;

use App\Http\Kernel;
use App\Http\Utils\RouteDefiner;
use Illuminate\Routing\Router;

class HomeHandler extends Handler implements RouteDefiner
{
    public function __invoke()
    {
        return 'Hola';
    }

    public static function defineRoute(Router $router): void
    {
        $router->get('/', self::class)
            ->middleware(Kernel::WEB);
    }
}
