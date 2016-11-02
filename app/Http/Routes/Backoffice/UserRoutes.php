<?php
namespace App\Http\Routes\Backoffice;

use App\Http\Controllers\Backoffice\UserController;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Routing\Registrar;
use LaravelBA\RouteBinder\Routes;

class UserRoutes implements Routes
{
    const EXPORT = "backoffice.backoffice-users.export";
    const INDEX = "backoffice.backoffice-users.index";
    const CREATE = "backoffice.backoffice-users.create";
    const STORE = "backoffice.backoffice-users.store";
    const SHOW = "backoffice.backoffice-users.show";
    const EDIT = "backoffice.backoffice-users.edit";
    const UPDATE = "backoffice.backoffice-users.update";
    const DESTROY = "backoffice.backoffice-users.destroy";

    const RESEND_ACTIVATION = 'backoffice.backoffice-users.resend-activation';
    const RESET_PASSWORD = 'backoffice.backoffice-users.reset-password';

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
            $router->get("export",                    ['as' => static::EXPORT,  "uses" => UserController::class . '@export',  "permission" => 'backoffice.users.list']);
            $router->get("/",                         ["as" => static::INDEX,   "uses" => UserController::class . '@index',   "permission" => 'backoffice.users.list', "middleware" => "persistent"]);
            $router->get("create",                    ["as" => static::CREATE,  "uses" => UserController::class . '@create',  "permission" => 'backoffice.users.create']);
            $router->post("/",                        ["as" => static::STORE,   "uses" => UserController::class . '@store',   "permission" => 'backoffice.users.create']);
            $router->get("{backoffice_username}",      ["as" => static::SHOW,    "uses" => UserController::class . '@show',    "permission" => 'backoffice.users.read']);
            $router->get("{backoffice_username}/edit", ["as" => static::EDIT,    "uses" => UserController::class . '@edit',    "permission" => 'backoffice.users.update']);
            $router->match(['PUT', 'PATCH'],
                "{backoffice_username}",               ["as" => static::UPDATE,  "uses" => UserController::class . '@update',  "permission" => 'backoffice.users.update']);
            $router->delete("{backoffice_username}",   ["as" => static::DESTROY, "uses" => UserController::class . '@destroy', "permission" => 'backoffice.users.delete']);

            $router->post('{backoffice_username}/resend-activation', ['as' => static::RESEND_ACTIVATION, 'uses' => UserController::class . '@resendActivation', 'permission' => 'backoffice.users.update']);
            $router->post('{backoffice_username}/reset-password',    ['as' => static::RESET_PASSWORD,    'uses' => UserController::class . '@resetPassword',    'permission' => 'backoffice.users.update']);
        });
    }
}
