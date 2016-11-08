<?php
namespace App\Http\Routes\Backoffice;

use Digbang\Security\SecurityContext;
use Illuminate\Routing\Router;
use LaravelBA\RouteBinder\Bindings;

class RoleBindings implements Bindings
{
    const SLUG = 'backoffice_role_slug';

    /**
     * {@inheritdoc}
     */
    public function addBindings(Router $router)
    {
        $router->bind(static::SLUG, function ($slug) {
            $securityContext = app(SecurityContext::class);

            return $securityContext->getSecurity('backoffice')->roles()->findBySlug($slug) ?: abort(404);
        });
    }
}
