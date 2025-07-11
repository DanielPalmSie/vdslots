<?php

namespace Videoslots\User\Services;

use Laraphive\Domain\User\DataTransferObjects\Requests\UpdateProfileAccountRequestData;
use Laraphive\Domain\User\DataTransferObjects\Responses\UpdateProfileAccountResponseData;

interface UpdateProfileAccountServiceInterface
{
    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\Requests\UpdateProfileAccountRequestData $requestData
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\Responses\UpdateProfileAccountResponseData
     */
    public function updateProfileAccount(
        UpdateProfileAccountRequestData $requestData
    ): UpdateProfileAccountResponseData;
}
