<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\Factory;

use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\CloseButtonData;

final class CloseButtonFactory
{
    /**
     * @param bool $doRedirect
     * @param string $redirectTo
     *
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\CloseButtonData
     */
    public function create(bool $doRedirect, string $redirectTo): CloseButtonData
    {
        return new CloseButtonData($doRedirect, $redirectTo);
    }

    /**
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\CloseButtonData
     */
    public function empty(): CloseButtonData
    {
        return new CloseButtonData(false, "");
    }
}
