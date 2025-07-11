<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu;

use Videoslots\Menu\Boxes\MobileMenu\Formatter\MenuElementFormatterInterface;

final class MobileMenuFormatter
{
    /**
     * @var array<string, \Videoslots\Menu\Boxes\MobileMenu\Formatter\MenuElementFormatterInterface>
     */
    private array $formatters;

    /**
     * @param array<string, string> $formatters
     */
    public function __construct(array $formatters)
    {
        $this->formatters = $formatters;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function canFormat(string $class): bool
    {
        return isset($this->formatters[$class]);
    }

    /**
     * @param string $class
     * @param object $data
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Formatter\MenuElementFormatterInterface
     */
    public function createFormatter(string $class, object $data): MenuElementFormatterInterface
    {
        return new $this->formatters[$class]($data);
    }
}
