<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\DataTransferObject\LoginDefault;

final class SubmitFieldData
{
    /**
     * @var string
     */
    private string $type;

    /**
     * @var string
     */
    private string $buttonType;

    /**
     * @var string
     */
    private string $alias;

    /**
     * @var bool
     */
    private bool $disabled;

    /**
     * @param string $type
     * @param string $buttonType
     * @param string $alias
     * @param bool $disabled
     */
    public function __construct(
        string $type,
        string $buttonType,
        string $alias,
        bool $disabled
    ) {
        $this->type = $type;
        $this->buttonType = $buttonType;
        $this->alias = $alias;
        $this->disabled = $disabled;
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
    public function getButtonType(): string
    {
        return $this->buttonType;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}
