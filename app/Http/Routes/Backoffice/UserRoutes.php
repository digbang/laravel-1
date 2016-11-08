<?php
namespace App\Http\Routes\Backoffice;

use App\Http\Controllers\Backoffice\UserController;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Routing\Registrar;
use LaravelBA\RouteBinder\Routes;
use App\Http\Routes\Backoffice\UserBindings as Bind;

class UserRoutes implements Routes
{
    const EXPORT            = 'backoffice.backoffice-users.export';
    const INDEX             = 'backoffice.backoffice-users.index';
    const CREATE            = 'backoffice.backoffice-users.create';
    const STORE             = 'backoffice.backoffice-users.store';
    const SHOW              = 'backoffice.backoffice-users.show';
    const EDIT              = 'backoffice.backoffice-users.edit';
    const UPDATE            = 'backoffice.backoffice-users.update';
    const DESTROY           = 'backoffice.backoffice-users.destroy';
    const RESEND_ACTIVATION = 'backoffice.backoffice-users.resend-activation';
    const RESET_PASSWORD    = 'backoffice.backoffice-users.reset-password';

    const PERMISSION_CREATE = 'backoffice.users.create';
    const PERMISSION_READ   = 'backoffice.users.read';
    const PERMISSION_UPDATE = 'backoffice.users.update';
    const PERMISSION_DELETE = 'backoffice.users.delete';

    /**
     * @var Repository
     */
    private $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function addRoutes(Registrar $router)
    {
        $prefix = $this->config->get('backoffice.auth.users_url', 'backoffice-users');

        $router->group(['prefix' => "backoffice/$prefix", 'middleware' => ['web', 'security:backoffice']], function (Registrar $router) {
            $router->get('export',                    ['as' => static::EXPORT,  'uses' => UserController::class . '@export',  'permission' => static::PERMISSION_READ]);
            $router->get('/',                         ['as' => static::INDEX,   'uses' => UserController::class . '@index',   'permission' => static::PERMISSION_READ, "middleware" => "persistent"]);
            $router->get('create',                    ['as' => static::CREATE,  'uses' => UserController::class . '@create',  'permission' => static::PERMISSION_CREATE]);
            $router->post('/',                        ['as' => static::STORE,   'uses' => UserController::class . '@store',   'permission' => static::PERMISSION_CREATE]);
            $router->get('{'.Bind::USERNAME.'}',      ['as' => static::SHOW,    'uses' => UserController::class . '@show',    'permission' => static::PERMISSION_READ]);
            $router->get('{'.Bind::USERNAME.'}/edit', ['as' => static::EDIT,    'uses' => UserController::class . '@edit',    'permission' => static::PERMISSION_UPDATE]);
            $router->match(['PUT', 'PATCH'],
                '{'.Bind::USERNAME.'}',               ['as' => static::UPDATE,  'uses' => UserController::class . '@update',  'permission' => static::PERMISSION_UPDATE]);
            $router->delete('{'.Bind::USERNAME.'}',   ['as' => static::DESTROY, 'uses' => UserController::class . '@destroy', 'permission' => static::PERMISSION_DELETE]);

            $router->post('{'.Bind::USERNAME.'}/resend-activation', ['as' => static::RESEND_ACTIVATION, 'uses' => UserController::class . '@resendActivation', 'permission' => static::PERMISSION_UPDATE]);
            $router->post('{'.Bind::USERNAME.'}/reset-password',    ['as' => static::RESET_PASSWORD,    'uses' => UserController::class . '@resetPassword',    'permission' => static::PERMISSION_UPDATE]);
        });
    }
}
