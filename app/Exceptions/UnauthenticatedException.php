<?php

namespace App\Exceptions;

use Flugg\Responder\Exceptions\Http\UnauthenticatedException as UnauthenticatedExceptionBase;

class UnauthenticatedException extends UnauthenticatedExceptionBase
{
    /** @var string|null */
    protected $errorCode = 'HTTP_UNAUTHORIZED';

    public function __construct()
    {
        parent::__construct(trans('errors.unauthenticated'));
    }
}
