<?php

declare(strict_types=1);

namespace Videoslots\IntendedGamblingPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\ButtonData;

final class FooterFactory
{
    /**
     * @return \Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\ButtonData
     */
    public function create(): ButtonData
    {
        return new ButtonData(
            'submit',
            'intended_gambling.form.submit',
        );
    }
}
