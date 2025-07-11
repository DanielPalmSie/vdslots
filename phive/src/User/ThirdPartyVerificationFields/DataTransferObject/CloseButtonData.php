<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\DataTransferObject;

final class CloseButtonData
{
    /**
     * @var bool
     */
    private bool $doRedirect;

    /**
     * @var string
     */
    private string $redirectTo;

    /**
     * @param bool $doRedirect
     * @param string $redirectTo
     */
    public function __construct(bool $doRedirect, string $redirectTo)
    {
        $this->doRedirect = $doRedirect;
        $this->redirectTo = $redirectTo;
    }

    /**
     * @return bool
     */
    public function doRedirect(): bool
    {
        return $this->doRedirect;
    }

    /**
     * @return string
     */
    public function getRedirectTo(): string
    {
        return $this->redirectTo;
    }
}
