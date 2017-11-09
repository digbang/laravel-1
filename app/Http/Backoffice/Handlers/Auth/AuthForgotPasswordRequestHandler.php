<?php

namespace App\Http\Backoffice\Handlers\Auth;

use App\Http\Backoffice\Handlers\Handler;
use App\Http\Backoffice\Handlers\SendsEmails;
use App\Http\Kernel;
use App\Http\Util\RouteDefiner;
use Digbang\Security\Contracts\SecurityApi;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Router;

class AuthForgotPasswordRequestHandler extends Handler implements RouteDefiner
{
    use SendsEmails;

    protected const ROUTE_NAME = 'backoffice.auth.password.forgot-request';

    public function __invoke(Redirector $redirector, Request $request, SecurityApi $securityApi)
    {
        $email = trim($request->input('email'));

        /** @var \Digbang\Security\Users\User $user */
        if (! $email || ! ($user = $securityApi->users()->findByCredentials(['email' => $email]))) {
            return $redirector->back()
                ->withErrors(['email' => trans('backoffice::auth.validation.user.not-found')]);
        }

        /** @var \Digbang\Security\Reminders\Reminder $reminder */
        $reminder = $securityApi->reminders()->create($user);

        $this->sendPasswordReset(
            $user,
            AuthResetPasswordHandler::route($user->getUserId(), $reminder->getCode())
        );

        return $redirector->to(AuthLoginHandler::route())
            ->with('info', trans('backoffice::auth.reset-password.email-sent',
                ['email' => $user->getEmail()]
            ));
    }

    public static function defineRoute(Router $router)
    {
        $router
            ->post(config('backoffice.global_url_prefix') . '/auth/password/forgot', static::class)
            ->name(static::ROUTE_NAME)
            ->middleware([
                Kernel::WEB,
                Kernel::BACKOFFICE_PUBLIC,
            ]);
    }

    public static function route()
    {
        return route(static::ROUTE_NAME);
    }
}