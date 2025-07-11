<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\DataTransferObject\LoginDefault;

final class LoginCaptchaData
{
    /**
     * @var string
     */
    private string $type;

    /**
     * @var string
     */
    private string $inputType;

    /**
     * @var string
     */
    private string $image;

    /**
     * @var bool
     */
    private bool $isVisible;

    /**
     * @var string
     */
    private string $alias;

    /**
     * @param string $type
     * @param string $inputType
     * @param string $image
     * @param string $alias
     * @param bool $isVisible
     */
    public function __construct(
        string $type,
        string $inputType,
        string $image,
        string $alias,
        bool $isVisible
    ) {
        $this->type = $type;
        $this->inputType = $inputType;
        $this->image = $image;
        $this->isVisible = $isVisible;
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getInputType(): string
    {
        return $this->inputType;
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
    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}
