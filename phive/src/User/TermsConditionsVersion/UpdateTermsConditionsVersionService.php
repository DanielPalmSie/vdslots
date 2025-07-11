<?php

declare(strict_types=1);

namespace Videoslots\User\TermsConditionsVersion;

final class UpdateTermsConditionsVersionService
{
    /**
     * @param \DBUser $user
     * @param bool $accept
     *
     * @return void
     */
    public function updateTermsConditionVersion(\DBUser $user, bool $accept): void
    {
        if ($accept) {
            $user->setTcVersion();
        } else {
            $user->setSetting('tac_block', 1);
        }
    }
}
