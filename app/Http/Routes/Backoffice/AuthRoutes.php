<?php
namespace App\Http\Routes\Backoffice;

use App\Http\Controllers\Backoffice\AuthController;
use Digbang\Security\Contracts\SecurityApi;
use Digbang\Security\SecurityContext;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Router;
use LaravelBA\RouteBinder\RouteBinder;

class AuthRoutes implements RouteBinder
{
	const LOGIN                   = 'backoffice.auth.login';
	const FORGOT_PASSWORD         = 'backoffice.auth.password.forgot';
	const RESET_PASSWORD          = 'backoffice.auth.password.reset';
	const LOGOUT                  = 'backoffice.auth.logout';
	const ACTIVATE                = 'backoffice.auth.activate';
	const ACTIVATE_RESEND         = 'backoffice.auth.resend_activation';
	const ACTIVATE_RESEND_REQUEST = 'backoffice.auth.resend_activation_request';
	const AUTHENTICATE            = 'backoffice.auth.authenticate';
	const ATTEMPT_FORGOT_PASSWORD = 'backoffice.auth.password.forgot-request';
	const ATTEMPT_RESET_PASSWORD  = 'backoffice.auth.password.reset-request';

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
		$router->bind('backoffice_user_id', function($id){
			/** @type SecurityApi $security */
			$security = app(SecurityContext::class)->getSecurity('backoffice');

			return $security->users()->findById($id) ?: abort(404);
		});
	}

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
		$router->group(['prefix' => 'backoffice/auth'], function(Router $router){
			$router->group(['middleware' => ['web', 'security:backoffice:public']], function(Router $router){
				$router->get('login',                                       ['as' => static::LOGIN,                   'uses' => AuthController::class . '@login']);
				$router->get('password/forgot',                             ['as' => static::FORGOT_PASSWORD,         'uses' => AuthController::class . '@forgotPassword']);
				$router->get('password/reset/{backoffice_user_id}/{code}',  ['as' => static::RESET_PASSWORD,          'uses' => AuthController::class . '@resetPassword']);
				$router->get('activate/{backoffice_user_id}/{code}',        ['as' => static::ACTIVATE,                'uses' => AuthController::class . '@activate']);
				$router->get('activate/resend',                             ['as' => static::ACTIVATE_RESEND,         'uses' => AuthController::class . '@resendActivationForm']);
				$router->post('activate/resend',                            ['as' => static::ACTIVATE_RESEND_REQUEST, 'uses' => AuthController::class . '@resendActivationRequest']);
				$router->post('login',                                      ['as' => static::AUTHENTICATE,            'uses' => AuthController::class . '@authenticate']);
				$router->post('password/forgot',                            ['as' => static::ATTEMPT_FORGOT_PASSWORD, 'uses' => AuthController::class . '@forgotPasswordRequest']);
				$router->post('password/reset/{backoffice_user_id}/{code}', ['as' => static::ATTEMPT_RESET_PASSWORD,  'uses' => AuthController::class . '@resetPasswordRequest']);
			});

			$router->group(['middleware' => ['web', 'security:backoffice']], function(Router $router){
				$router->get('logout', ['as' => static::LOGOUT, 'uses' => AuthController::class . '@logout']);
			});
		});
	}
}
