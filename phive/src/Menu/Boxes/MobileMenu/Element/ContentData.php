<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

class ContentData
{
    /**
     * @var string
     */
    private string $value;

    /**
     * @var string
     */
    private string $type;

    /**
     * @param string $value
     * @param string $type
     */
    public function __construct(string $value, string $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
