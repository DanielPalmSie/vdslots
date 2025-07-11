<?php

declare(strict_types=1);

namespace Videoslots\RgEvaluationPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\HeaderData;

final class HeaderFactory
{
    /**
     * @return \Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\HeaderData
     */
    public function create(): HeaderData
    {
        return new HeaderData('rg.info.box.top.headline', true);
    }
}
