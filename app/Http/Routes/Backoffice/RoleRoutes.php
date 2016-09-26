<?php
namespace App\Http\Routes\Backoffice;

use App\Http\Controllers\Backoffice\RoleController;
use Digbang\Security\SecurityContext;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Router;
use LaravelBA\RouteBinder\RouteBinder;
use Illuminate\Config\Repository;

class RoleRoutes implements RouteBinder
{
	const EXPORT  = "backoffice.backoffice-roles.export";
	const INDEX   = "backoffice.backoffice-roles.index";
	const CREATE  = "backoffice.backoffice-roles.create";
	const STORE   = "backoffice.backoffice-roles.store";
	const SHOW    = "backoffice.backoffice-roles.show";
	const EDIT    = "backoffice.backoffice-roles.edit";
	const UPDATE  = "backoffice.backoffice-roles.update";
	const DESTROY = "backoffice.backoffice-roles.destroy";

	/**
	 * @type Repository
	 */
	private $config;

	/**
	 * @type SecurityContext
	 */
	private $securityContext;

	/**
	 * @param Repository      $config
	 * @param SecurityContext $securityContext
     */
	public function __construct(Repository $config, SecurityContext $securityContext)
	{
		$this->config = $config;
		$this->securityContext = $securityContext;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addRoutes(Registrar $router)
	{
		$prefix = $this->config->get('backoffice.auth.groups_url', 'backoffice-roles');

		$router->group(['prefix' => "backoffice/$prefix", 'middleware' => ['web', 'security:backoffice']], function() use ($router) {
			$router->get("export",                      ['as' => static::EXPORT,  "uses" => RoleController::class . '@export',  "permission" => 'backoffice.roles.list']);
			$router->get("/",                           ["as" => static::INDEX,   "uses" => RoleController::class . '@index',   "permission" => 'backoffice.roles.list', "persistent" => true]);
			$router->get("create",                      ["as" => static::CREATE,  "uses" => RoleController::class . '@create',  "permission" => 'backoffice.roles.create']);
			$router->post("/",                          ["as" => static::STORE,   "uses" => RoleController::class . '@store',   "permission" => 'backoffice.roles.create']);
			$router->get("{backoffice_role_slug}",      ["as" => static::SHOW,    "uses" => RoleController::class . '@show',    "permission" => 'backoffice.roles.read']);
			$router->get("{backoffice_role_slug}/edit", ["as" => static::EDIT,    "uses" => RoleController::class . '@edit',    "permission" => 'backoffice.roles.update']);
			$router->match(['PUT', 'PATCH'],
				"{backoffice_role_slug}",               ["as" => static::UPDATE,  "uses" => RoleController::class . '@update',  "permission" => 'backoffice.roles.update']);
			$router->delete("{backoffice_role_slug}",   ["as" => static::DESTROY, "uses" => RoleController::class . '@destroy', "permission" => 'backoffice.roles.delete']);
		});
	}

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
		$router->bind('backoffice_role_slug', function($slug){
			return $this->securityContext->getSecurity('backoffice')->roles()->findBySlug($slug);
		});
	}
}
