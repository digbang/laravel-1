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
            $router->get('export',                      ['as' => static::EXPORT,  'uses' => RoleController::class . '@export',  'permission' => 'backoffice.roles.list']);
            $router->get('/',                           ['as' => static::INDEX,   'uses' => RoleController::class . '@index',   'permission' => 'backoffice.roles.list', 'persistent' => true]);
            $router->get('create',                      ['as' => static::CREATE,  'uses' => RoleController::class . '@create',  'permission' => 'backoffice.roles.create']);
            $router->post('/',                          ['as' => static::STORE,   'uses' => RoleController::class . '@store',   'permission' => 'backoffice.roles.create']);
            $router->get('{'.Bind::SLUG.'}',      ['as' => static::SHOW,    'uses' => RoleController::class . '@show',    'permission' => 'backoffice.roles.read']);
            $router->get('{'.Bind::SLUG.'}/edit', ['as' => static::EDIT,    'uses' => RoleController::class . '@edit',    'permission' => 'backoffice.roles.update']);
            $router->match(['PUT', 'PATCH'],
                '{'.Bind::SLUG.'}',               ['as' => static::UPDATE,  'uses' => RoleController::class . '@update',  'permission' => 'backoffice.roles.update']);
            $router->delete('{'.Bind::SLUG.'}',   ['as' => static::DESTROY, 'uses' => RoleController::class . '@destroy', 'permission' => 'backoffice.roles.delete']);
        });
    }
}
