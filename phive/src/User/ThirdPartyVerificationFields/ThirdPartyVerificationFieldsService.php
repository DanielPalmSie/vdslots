<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields;

use Videoslots\User\ThirdPartyVerificationFields\Factory\ThirdPartyVerificationFieldsFactory;
use Videoslots\User\ThirdPartyVerificationFields\Formatter\ThirdPartyVerificationFieldsFormatter;

final class ThirdPartyVerificationFieldsService
{
    /**
     * @param string $context
     * @param string $boxId
     * @param string $country
     *
     * @return array
     */
    public function getFields(string $context, string $boxId, string $country): array
    {
        phive('Licensed')->forceCountry($country);

        $fields = (new ThirdPartyVerificationFieldsFactory())->getFields($context, $boxId);

        return (new ThirdPartyVerificationFieldsFormatter())->format($fields);
    }
}
