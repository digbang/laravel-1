<?php
namespace App\Http\Routes\Backoffice;

use App\Http\Controllers\Backoffice\AuthController;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Router;
use LaravelBA\RouteBinder\Routes;

class AuthRoutes implements Routes
{
    const LOGIN = 'backoffice.auth.login';
    const FORGOT = 'backoffice.auth.password.forgot';
    const RESET = 'backoffice.auth.password.reset';
    const LOGOUT = 'backoffice.auth.logout';
    const ACTIVATE = 'backoffice.auth.activate';
    const ACTIVATE_RESEND = 'backoffice.auth.resend_activation';
    const ACTIVATE_RESEND_REQUEST = 'backoffice.auth.resend_activation_request';
    const AUTHENTICATE = 'backoffice.auth.authenticate';
    const ATTEMPT_FORGOT = 'backoffice.auth.password.forgot-request';
    const ATTEMPT_RESET = 'backoffice.auth.password.reset-request';

    /**
     * {@inheritdoc}
     */
    public function addRoutes(Registrar $router)
    {
        $router->group(['prefix' => 'backoffice/auth', 'middleware' => ['web']], function (Router $router) {
            $router->group(['middleware' => ['security:backoffice:public']], function (Router $router) {
                $router->get('login',                                       ['as' => static::LOGIN,                   'uses' => AuthController::class . '@login']);
                $router->get('password/forgot',                             ['as' => static::FORGOT, 'uses' => AuthController::class . '@forgotPassword']);
                $router->get('password/reset/{backoffice_user_id}/{code}',  ['as' => static::RESET, 'uses' => AuthController::class . '@resetPassword']);
                $router->get('activate/{backoffice_user_id}/{code}',        ['as' => static::ACTIVATE,                'uses' => AuthController::class . '@activate']);
                $router->get('activate/resend',                             ['as' => static::ACTIVATE_RESEND,         'uses' => AuthController::class . '@resendActivationForm']);
                $router->post('activate/resend',                            ['as' => static::ACTIVATE_RESEND_REQUEST, 'uses' => AuthController::class . '@resendActivationRequest']);
                $router->post('login',                                      ['as' => static::AUTHENTICATE,            'uses' => AuthController::class . '@authenticate']);
                $router->post('password/forgot',                            ['as' => static::ATTEMPT_FORGOT, 'uses' => AuthController::class . '@forgotPasswordRequest']);
                $router->post('password/reset/{backoffice_user_id}/{code}', ['as' => static::ATTEMPT_RESET, 'uses' => AuthController::class . '@resetPasswordRequest']);
            });

            $router->group(['middleware' => ['security:backoffice']], function (Router $router) {
                $router->get('logout', ['as' => static::LOGOUT, 'uses' => AuthController::class . '@logout']);
            });
        });
    }
}
