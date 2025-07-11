<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\DataTransferObject;

final class StartExternalVerificationButtonData
{
    /**
     * @var string
     */
    private string $alias;

    /**
     * @var string
     */
    private string $image;

    /**
     * @var bool
     */
    private bool $disabled;

    /**
     * @var string
     */
    private string $disabledText;

    /**
     * @param string $alias
     * @param string $image
     * @param bool $disabled
     * @param string $disabledText
     */
    public function __construct(
        string $alias,
        string $image = "",
        bool $disabled = false,
        string $disabledText = ""
    ) {
        $this->alias = $alias;
        $this->image = $image;
        $this->disabled = $disabled;
        $this->disabledText = $disabledText;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @return string
     */
    public function getDisabledText(): string
    {
        return $this->disabledText;
    }
}
