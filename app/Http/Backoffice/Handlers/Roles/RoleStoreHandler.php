<?php

namespace App\Http\Backoffice\Handlers\Roles;

use App\Http\Backoffice\Handlers\Dashboard\DashboardIndexHandler;
use App\Http\Backoffice\Handlers\Handler;
use App\Http\Backoffice\Requests\Roles\RoleStoreRequest;
use App\Http\Kernel;
use App\Http\Util\RouteDefiner;
use App\Http\Backoffice\Permission;
use Digbang\Backoffice\Exceptions\ValidationException;
use Digbang\Security\Exceptions\SecurityException;
use Digbang\Security\Permissions\Permissible;
use Digbang\Security\Roles\Role;
use Illuminate\Routing\Router;

class RoleStoreHandler extends Handler implements RouteDefiner
{
    public function __invoke(RoleStoreRequest $request)
    {
        try {
            $roles = security()->roles();

            /** @var Role|Permissible $role */
            $role = $roles->create($request->input('name'), $request->input('slug') ?: null);

            if ($request->input('permissions') && $role instanceof Permissible) {
                foreach ((array) $request->input('permissions') as $permission) {
                    $role->addPermission($permission);
                }

                $roles->save($role);
            }

            return redirect()->to(
                security()->url()->route(RoleListHandler::class)
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->getErrors());
        } catch (SecurityException $e) {
            return redirect()->to(url()->to(DashboardIndexHandler::route()))->withDanger(trans('backoffice::auth.permission_error'));
        }
    }

    public static function defineRoute(Router $router)
    {
        $backofficePrefix = config('backoffice.global_url_prefix');
        $routePrefix = config('backoffice.auth.roles.url', 'roles');

        $router
            ->post("$backofficePrefix/$routePrefix/", [
                'uses' => static::class,
                'permission' => Permission::ROLE_CREATE,
            ])
            ->name(static::class)
            ->middleware([
                Kernel::WEB,
                Kernel::BACKOFFICE,
            ]);
    }

    public static function route()
    {
        return route(static::class);
    }
}
