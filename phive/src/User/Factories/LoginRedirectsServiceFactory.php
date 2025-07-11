<?php

declare(strict_types=1);

namespace Videoslots\User\Factories;

use Videoslots\User\Services\LoginRedirectsService;
use Videoslots\User\Services\LoginRedirectsValidatorService;

class LoginRedirectsServiceFactory
{
    /**
     * @return \Videoslots\User\Services\LoginRedirectsService
     */
    public static function create(): LoginRedirectsService
    {
        return new LoginRedirectsService(new LoginRedirectsValidatorService());
    }
}
