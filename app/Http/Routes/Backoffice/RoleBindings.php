<?php
namespace App\Http\Routes\Backoffice;

use Digbang\Security\SecurityContext;
use Illuminate\Routing\Router;
use LaravelBA\RouteBinder\Bindings;

class RoleBindings implements Bindings
{
    /**
     * {@inheritdoc}
     */
    public function addBindings(Router $router)
    {
        $router->bind('backoffice_role_slug', function ($slug) {
            $securityContext = app(SecurityContext::class);

            return $securityContext->getSecurity('backoffice')->roles()->findBySlug($slug) ?: abort(404);
        });
    }
}
