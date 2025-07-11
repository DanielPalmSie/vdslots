<?php

declare(strict_types=1);

namespace Videoslots\User\CustomLoginTop;

final class CustomLoginTopForSE implements CustomLoginTop
{
    /**
     * @var string
     */
    private string $loginDefaultAlias;

    /**
     * @var string
     */
    private string $thirdPartyVerificationAlias;

    /**
     * @param string $loginDefaultAlias
     * @param string $thirdPartyVerificationAlias
     */
    public function __construct(
        string $loginDefaultAlias,
        string $thirdPartyVerificationAlias
    ) {
        $this->loginDefaultAlias = $loginDefaultAlias;
        $this->thirdPartyVerificationAlias = $thirdPartyVerificationAlias;
    }

    /**
     * @return string
     */
    public function getLoginDefaultAlias(): string
    {
        return $this->loginDefaultAlias;
    }

    /**
     * @return string
     */
    public function getThirdPartyVerificationAlias(): string
    {
        return $this->thirdPartyVerificationAlias;
    }

    /**
     * @return array[]
     */
    public function toArray(): array
    {
        return [
            [
                'type' => 'button',
                'action' => "SHOW_LOGIN_DEFAULT_FORM",
                'alias' => $this->getLoginDefaultAlias(),
                'isActive' => true,
            ],
            [
                'type' => 'button',
                'action' => 'SHOW_THIRD_PARTH_VERIFICATION_FORM',
                'alias' => $this->getThirdPartyVerificationAlias(),
                'isActive' => false,
            ],
        ];
    }
}
