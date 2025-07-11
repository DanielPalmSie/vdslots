<?php

declare(strict_types=1);

namespace Videoslots\IntendedGamblingPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\HeaderData;

final class HeaderFactory
{
    /**
     * @return \Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\HeaderData
     */
    public function create(): HeaderData
    {
        return new HeaderData(
            lic('imgUri', ['money-gambling.png']),
            'intended_gambling.headline',
            'intended_gambling.form.title',
            'intended_gambling.title',
            'intended_gambling.description'
        );
    }
}
