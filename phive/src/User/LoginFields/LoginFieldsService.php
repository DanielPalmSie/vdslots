<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields;

use Videoslots\User\LoginFields\Factory\LoginFieldsFactory;
use Videoslots\User\LoginFields\Formatter\LoginFieldsFormatter;

final class LoginFieldsService
{
    /**
     * @param string $boxId
     *
     * @return array
     */
    public function getFields(string $boxId): array
    {
        $fields = (new LoginFieldsFactory())->getFields($boxId, "login");

        return (new LoginFieldsFormatter())->format($fields);
    }
}
