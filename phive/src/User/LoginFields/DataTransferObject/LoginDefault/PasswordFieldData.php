<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\DataTransferObject\LoginDefault;

final class PasswordFieldData
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
    private string $name;

    /**
     * @var string
     */
    private string $placeholder;

    /**
     * @param string $type
     * @param string $inputType
     * @param string $name
     * @param string $placeholder
     */
    public function __construct(
        string $type,
        string $inputType,
        string $name,
        string $placeholder
    ) {
        $this->type = $type;
        $this->inputType = $inputType;
        $this->name = $name;
        $this->placeholder = $placeholder;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }
}
