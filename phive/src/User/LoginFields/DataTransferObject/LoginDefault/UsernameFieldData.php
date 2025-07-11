<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\DataTransferObject\LoginDefault;

final class UsernameFieldData
{
    /**
     * @var string
     */
    private string $name;

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
    private string $placeholder;

    /**
     * @param string $name
     * @param string $type
     * @param string $inputType
     * @param string $placeholder
     */
    public function __construct(
        string $name,
        string $type,
        string $inputType,
        string $placeholder
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->placeholder = $placeholder;
        $this->inputType = $inputType;
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
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }
}
