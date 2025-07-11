<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\SeparatorData;

final class SeparatorElementFactory
{
    /**
     * @param string $alias
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\SeparatorData
     */
    public function create(string $alias): SeparatorData
    {
        return new SeparatorData("$alias.separator");
    }
}
