<?php

namespace App\Http\Backoffice\Requests\Auth;

use App\Http\Backoffice\Handlers\Auth\AuthResetPasswordHandler;
use App\Http\Backoffice\Requests\Request;
use Digbang\Security\Users\User;

class ResetPasswordRequest extends Request
{
    public function getUser(): User
    {
        $id = $this->route(AuthResetPasswordHandler::ROUTE_PARAM_USER);
        $user = security()->users()->findById($id);

        if (! $user) {
            abort(404);
        }

        return $user;
    }

    public function getCode(): string
    {
        return $this->route(AuthResetPasswordHandler::ROUTE_PARAM_CODE);
    }
}
