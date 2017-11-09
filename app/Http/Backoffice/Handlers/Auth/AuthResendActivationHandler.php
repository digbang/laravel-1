<?php

namespace App\Http\Backoffice\Handlers\Auth;

use App\Http\Backoffice\Handlers\Handler;
use App\Http\Kernel;
use App\Http\Util\RouteDefiner;
use Illuminate\Contracts\View\Factory;
use Illuminate\Routing\Router;

class AuthResendActivationHandler extends Handler implements RouteDefiner
{
    protected const ROUTE_NAME = 'backoffice.auth.resend_activation';

    public function __invoke(Factory $view)
    {
        return $view->make('backoffice::auth.request-activation');
    }

    public static function defineRoute(Router $router)
    {
        $router
            ->get(config('backoffice.global_url_prefix') . '/auth/activate/resend', static::class)
            ->name(static::ROUTE_NAME)
            ->middleware([
                Kernel::WEB,
                Kernel::BACKOFFICE_PUBLIC,
            ]);
    }

    public static function route()
    {
        return route(static::ROUTE_NAME);
    }
}
