<?php

declare(strict_types=1);

namespace Videoslots\Services\Renderer\Extensions;

use AccountBox;

final class Common
{
    /**
     * @param $type
     * @param $id
     *
     * @return void
     */
    public static function rgRemoveLimitBtn($type, $id = null): void
    {
        (new AccountBox())->rgRemoveLimitBtn($type, $id);
    }
}
