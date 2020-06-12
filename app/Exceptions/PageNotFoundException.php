<?php

namespace App\Exceptions;

use Flugg\Responder\Exceptions\Http\PageNotFoundException as PageNotFoundExceptionBase;

class PageNotFoundException extends PageNotFoundExceptionBase
{
    public function __construct()
    {
        parent::__construct(trans('errors.unauthenticated'));
    }
}
