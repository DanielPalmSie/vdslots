<?php

declare(strict_types=1);

namespace Videoslots\MicroGames\Factories;

use Videoslots\MicroGames\Services\BoxHandlerService;
use Videoslots\MicroGames\Services\BoxHandlerServiceInterface;

class BoxHandlerServiceFactory
{
    /**
     * @param string $boxClass
     * @param string $cachedPath
     *
     * @return \Videoslots\MicroGames\Services\BoxHandlerServiceInterface
     */
    public static function create(
        string $boxClass,
        string $cachedPath
    ): BoxHandlerServiceInterface {
        return new BoxHandlerService($boxClass, $cachedPath, phive('BoxHandler'));
    }
}
