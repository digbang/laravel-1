<?php
namespace App\Http\Routes\Backoffice;

use App\Http\Controllers\Backoffice\RoleController;
use App\Http\Routes\Backoffice\RoleBindings as Bind;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\Registrar;
use LaravelBA\RouteBinder\Routes;

class RoleRoutes implements Routes
{
    const EXPORT = 'backoffice.backoffice-roles.export';
    const INDEX = 'backoffice.backoffice-roles.index';
    const CREATE = 'backoffice.backoffice-roles.create';
    const STORE = 'backoffice.backoffice-roles.store';
    const SHOW = 'backoffice.backoffice-roles.show';
    const EDIT = 'backoffice.backoffice-roles.edit';
    const UPDATE = 'backoffice.backoffice-roles.update';
    const DESTROY = 'backoffice.backoffice-roles.destroy';

    const PERMISSION_READ   = 'backoffice.roles.read';
    const PERMISSION_CREATE = 'backoffice.roles.create';
    const PERMISSION_UPDATE = 'backoffice.roles.update';
    const PERMISSION_DELETE = 'backoffice.roles.delete';

    /**
     * @var Repository
     */
    private $config;

    /**
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function addRoutes(Registrar $router)
    {
        $prefix = $this->config->get('backoffice.auth.roles.url', 'backoffice-roles');

        $router->group(['prefix' => "backoffice/$prefix", 'middleware' => ['web', 'security:backoffice']], function () use ($router) {
            $router->get('export',                ['as' => static::EXPORT,  'uses' => RoleController::class . '@export',  'permission' => self::PERMISSION_READ]);
            $router->get('/',                     ['as' => static::INDEX,   'uses' => RoleController::class . '@index',   'permission' => self::PERMISSION_READ, 'middleware' => 'persistent']);
            $router->get('create',                ['as' => static::CREATE,  'uses' => RoleController::class . '@create',  'permission' => self::PERMISSION_CREATE]);
            $router->post('/',                    ['as' => static::STORE,   'uses' => RoleController::class . '@store',   'permission' => self::PERMISSION_CREATE]);
            $router->get('{'.Bind::SLUG.'}',      ['as' => static::SHOW,    'uses' => RoleController::class . '@show',    'permission' => self::PERMISSION_READ]);
            $router->get('{'.Bind::SLUG.'}/edit', ['as' => static::EDIT,    'uses' => RoleController::class . '@edit',    'permission' => self::PERMISSION_UPDATE]);
            $router->match(['PUT', 'PATCH'],
                '{'.Bind::SLUG.'}',               ['as' => static::UPDATE,  'uses' => RoleController::class . '@update',  'permission' => self::PERMISSION_UPDATE]);
            $router->delete('{'.Bind::SLUG.'}',   ['as' => static::DESTROY, 'uses' => RoleController::class . '@destroy', 'permission' => self::PERMISSION_DELETE]);
        });
    }
}
